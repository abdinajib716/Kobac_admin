<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\PushNotification;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected ?string $projectId = null;
    protected ?string $accessToken = null;

    // ─── Configuration ───────────────────────────────────────

    /**
     * Check if Firebase is enabled and configured
     */
    public function isEnabled(): bool
    {
        return (bool) Setting::get('firebase_enabled', false);
    }

    /**
     * Check if all required credentials are set
     */
    public function isConfigured(): bool
    {
        return $this->isEnabled()
            && !empty(Setting::get('firebase_project_id'))
            && !empty(Setting::get('firebase_client_email'))
            && !empty($this->getDecryptedPrivateKey());
    }

    /**
     * Get decrypted private key
     */
    protected function getDecryptedPrivateKey(): ?string
    {
        $encrypted = Setting::get('firebase_private_key');
        if (empty($encrypted)) {
            return null;
        }

        try {
            $key = decrypt($encrypted);
        } catch (\Exception $e) {
            // If decryption fails, try using the value as-is (backward compatibility)
            $key = $encrypted;
        }

        // Convert literal \n strings to actual newlines (from JSON paste)
        $key = str_replace('\n', "\n", $key);

        return $key;
    }

    /**
     * Get the Firebase project ID
     */
    protected function getProjectId(): string
    {
        if (!$this->projectId) {
            $this->projectId = Setting::get('firebase_project_id');
        }
        return $this->projectId;
    }

    /**
     * Get OAuth2 access token using service account credentials
     */
    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $clientEmail = Setting::get('firebase_client_email');
        $privateKey = $this->getDecryptedPrivateKey();

        $now = time();
        $header = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64url_encode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signatureInput = "{$header}.{$payload}";
        openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $jwt = "{$signatureInput}." . base64url_encode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to get Firebase access token: ' . $response->body());
        }

        $this->accessToken = $response->json('access_token');
        return $this->accessToken;
    }

    // ─── Send Methods ────────────────────────────────────────

    /**
     * Send notification to a single device token
     */
    public function sendToToken(string $token, string $title, string $body, array $data = [], ?string $imageUrl = null): array
    {
        $message = $this->buildMessage($title, $body, $data, $imageUrl);
        $message['message']['token'] = $token;

        return $this->sendFcmRequest($message);
    }

    /**
     * Send notification to multiple device tokens (batched)
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = [], ?string $imageUrl = null): array
    {
        if (empty($tokens)) {
            return ['success' => true, 'success_count' => 0, 'failure_count' => 0, 'failed_tokens' => []];
        }

        $successCount = 0;
        $failureCount = 0;
        $failedTokens = [];

        // FCM HTTP v1 API sends one message at a time, batch in chunks
        foreach (array_chunk($tokens, 500) as $chunk) {
            foreach ($chunk as $token) {
                try {
                    $result = $this->sendToToken($token, $title, $body, $data, $imageUrl);
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $failureCount++;
                        $failedTokens[] = ['token' => $token, 'error' => $result['error'] ?? 'Unknown error'];
                    }
                } catch (\Exception $e) {
                    $failureCount++;
                    $failedTokens[] = ['token' => $token, 'error' => $e->getMessage()];
                }
            }
        }

        return [
            'success' => $successCount > 0,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'failed_tokens' => $failedTokens,
        ];
    }

    /**
     * Send notification to a topic
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = [], ?string $imageUrl = null): array
    {
        $message = $this->buildMessage($title, $body, $data, $imageUrl);
        $message['message']['topic'] = $topic;

        return $this->sendFcmRequest($message);
    }

    /**
     * Send push notification to a specific user (all their active devices)
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [], ?string $imageUrl = null): array
    {
        if (!$user->is_active) {
            return ['success' => false, 'error' => 'User is inactive', 'success_count' => 0, 'failure_count' => 0];
        }

        $tokens = $user->activeDeviceTokens()->pluck('device_token')->toArray();

        if (empty($tokens)) {
            return ['success' => false, 'error' => 'No active device tokens', 'success_count' => 0, 'failure_count' => 0];
        }

        return $this->sendToTokens($tokens, $title, $body, $data, $imageUrl);
    }

    /**
     * Send push notification to multiple users
     */
    public function sendToUsers(Collection $users, string $title, string $body, array $data = [], ?string $imageUrl = null): array
    {
        $allTokens = DeviceToken::whereIn('user_id', $users->pluck('id'))
            ->where('is_active', true)
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->pluck('device_token')
            ->toArray();

        if (empty($allTokens)) {
            return ['success' => false, 'error' => 'No active device tokens found', 'success_count' => 0, 'failure_count' => 0];
        }

        return $this->sendToTokens($allTokens, $title, $body, $data, $imageUrl);
    }

    // ─── High-Level Send with Logging ────────────────────────

    /**
     * Send and log a push notification to an audience
     */
    public function sendNotification(
        string $title,
        string $body,
        string $audience = 'all',
        ?int $targetUserId = null,
        array $data = [],
        ?string $imageUrl = null,
        ?int $sentBy = null
    ): PushNotification {
        if (!$this->isConfigured()) {
            $record = PushNotification::create([
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'image_url' => $imageUrl,
                'audience' => $audience,
                'target_user_id' => $targetUserId,
                'status' => 'failed',
                'error_message' => 'Firebase is not configured',
                'sent_by' => $sentBy,
                'completed_at' => now(),
            ]);
            return $record;
        }

        // Resolve recipients
        $users = $this->resolveAudience($audience, $targetUserId);
        $totalRecipients = DeviceToken::whereIn('user_id', $users->pluck('id'))
            ->where('is_active', true)
            ->count();

        // Create log record
        $record = PushNotification::create([
            'title' => $title,
            'body' => $body,
            'data' => !empty($data) ? $data : null,
            'image_url' => $imageUrl,
            'audience' => $audience,
            'target_user_id' => $targetUserId,
            'total_recipients' => $totalRecipients,
            'status' => 'sending',
            'sent_by' => $sentBy,
            'sent_at' => now(),
        ]);

        try {
            $result = $this->sendToUsers($users, $title, $body, $data, $imageUrl);

            $record->markAsSent(
                $result['success_count'] ?? 0,
                $result['failure_count'] ?? 0,
                $result['failed_tokens'] ?? null
            );

            // Deactivate invalid tokens
            $this->cleanupFailedTokens($result['failed_tokens'] ?? []);

        } catch (\Exception $e) {
            Log::error('Push notification failed', [
                'notification_id' => $record->id,
                'error' => $e->getMessage(),
            ]);
            $record->markAsFailed($e->getMessage());
        }

        return $record;
    }

    // ─── Helpers ─────────────────────────────────────────────

    /**
     * Build the FCM v1 message payload
     */
    protected function buildMessage(string $title, string $body, array $data = [], ?string $imageUrl = null): array
    {
        $notification = [
            'title' => $title,
            'body' => $body,
        ];

        if ($imageUrl) {
            $notification['image'] = $imageUrl;
        }

        $message = [
            'message' => [
                'notification' => $notification,
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'kobac_notifications',
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
            ],
        ];

        if (!empty($data)) {
            // FCM data values must be strings
            $message['message']['data'] = array_map('strval', $data);
        }

        return $message;
    }

    /**
     * Send HTTP request to FCM v1 API
     */
    protected function sendFcmRequest(array $message): array
    {
        try {
            $projectId = $this->getProjectId();
            $accessToken = $this->getAccessToken();

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->post($url, $message);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('name'),
                ];
            }

            $error = $response->json('error.message', 'Unknown FCM error');
            $errorCode = $response->json('error.status', 'UNKNOWN');

            Log::warning('FCM send failed', [
                'status' => $response->status(),
                'error' => $error,
                'error_code' => $errorCode,
            ]);

            return [
                'success' => false,
                'error' => $error,
                'error_code' => $errorCode,
            ];
        } catch (\Exception $e) {
            Log::error('FCM request exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve audience to a collection of users
     */
    protected function resolveAudience(string $audience, ?int $targetUserId = null): Collection
    {
        return match ($audience) {
            'all' => User::active()->mobileUsers()->get(),
            'individual' => User::active()->individuals()->get(),
            'business' => User::active()->businessUsers()->get(),
            'specific' => $targetUserId ? User::active()->where('id', $targetUserId)->get() : collect(),
            default => collect(),
        };
    }

    /**
     * Deactivate device tokens that consistently fail
     */
    protected function cleanupFailedTokens(array $failedTokens): void
    {
        if (empty($failedTokens)) {
            return;
        }

        $invalidErrors = ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'];

        foreach ($failedTokens as $failed) {
            $token = $failed['token'] ?? null;
            $error = $failed['error'] ?? '';

            if ($token && $this->shouldDeactivateToken($error, $invalidErrors)) {
                DeviceToken::where('device_token', $token)->update(['is_active' => false]);
            }
        }
    }

    /**
     * Determine if a token should be deactivated based on the error
     */
    protected function shouldDeactivateToken(string $error, array $invalidErrors): bool
    {
        foreach ($invalidErrors as $invalidError) {
            if (str_contains(strtoupper($error), $invalidError)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the default topic name
     */
    public function getDefaultTopic(): string
    {
        return Setting::get('firebase_default_topic', 'kobac_all');
    }

    /**
     * Test Firebase connection by sending a dry-run message
     */
    public function testConnection(): array
    {
        try {
            if (!$this->isConfigured()) {
                return ['success' => false, 'message' => 'Firebase is not configured. Please fill all required fields.'];
            }

            $accessToken = $this->getAccessToken();
            $projectId = $this->getProjectId();

            // Validate by sending a dry-run to a topic
            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post($url, [
                    'validate_only' => true,
                    'message' => [
                        'topic' => 'test_connection',
                        'notification' => [
                            'title' => 'Connection Test',
                            'body' => 'Testing Firebase configuration',
                        ],
                    ],
                ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Firebase connection successful! Project: ' . $projectId];
            }

            return [
                'success' => false,
                'message' => 'Firebase returned error: ' . $response->json('error.message', $response->body()),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }
}

// ─── Helper function for base64url encoding ──────────────
if (!function_exists('base64url_encode')) {
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
