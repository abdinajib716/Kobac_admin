<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Setting;
use App\Models\BusinessUser;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class NewEndpointsTest extends TestCase
{
    protected ?string $token = null;
    protected ?int $branchId = null;
    protected ?int $staffBusinessUserId = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Login with test user
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'najiibkarshe81@gmail.com',
            'password' => 'Hnajiib12345$',
        ]);

        if ($response->status() === 200) {
            $this->token = $response->json('data.token');
            $this->branchId = $response->json('data.user.current_branch_id') ?? 26;
        }

        // Get a staff user ID for testing
        if ($this->token) {
            $usersResponse = $this->withHeaders($this->getAuthHeaders())
                ->getJson('/api/v1/business/users');
            
            $users = $usersResponse->json('data.users') ?? [];
            foreach ($users as $user) {
                if ($user['role'] !== 'owner') {
                    $this->staffBusinessUserId = $user['id'];
                    break;
                }
            }
        }
    }

    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'X-Branch-ID' => $this->branchId,
            'Accept' => 'application/json',
        ];
    }

    public function test_whatsapp_support_endpoint_returns_config(): void
    {
        $response = $this->getJson('/api/v1/support/whatsapp');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['enabled'],
            ]);

        echo "\n✅ WhatsApp support endpoint works";
    }

    public function test_whatsapp_support_returns_full_config_when_enabled(): void
    {
        // Temporarily enable WhatsApp
        Setting::set('whatsapp_enabled', true);
        Setting::set('whatsapp_phone_number', '252613954330');
        Setting::set('whatsapp_agent_name', 'Test Agent');
        Setting::set('whatsapp_agent_title', 'Test Title');
        Setting::set('whatsapp_greeting_message', 'Hello!');
        Setting::set('whatsapp_default_message', 'Hi there');

        $response = $this->getJson('/api/v1/support/whatsapp');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'enabled' => true,
                    'phone_number' => '252613954330',
                    'agent_name' => 'Test Agent',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'enabled',
                    'phone_number',
                    'agent_name',
                    'agent_title',
                    'greeting_message',
                    'default_message',
                    'whatsapp_url',
                ],
            ]);

        // Clean up
        Setting::set('whatsapp_enabled', false);

        echo "\n✅ WhatsApp returns full config when enabled";
    }

    public function test_resend_invitation_endpoint(): void
    {
        if (!$this->token || !$this->staffBusinessUserId) {
            $this->markTestSkipped('No auth token or staff user');
        }

        Mail::fake();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson("/api/v1/business/users/{$this->staffBusinessUserId}/resend-invitation");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'email',
                    'resent_at',
                ],
            ]);

        echo "\n✅ Resend invitation endpoint works";
    }

    public function test_resend_invitation_fails_for_owner(): void
    {
        if (!$this->token) {
            $this->markTestSkipped('No auth token');
        }

        // Get owner's business user ID
        $usersResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/business/users');
        
        $users = $usersResponse->json('data.users') ?? [];
        $ownerBusinessUserId = null;
        foreach ($users as $user) {
            if ($user['role'] === 'owner') {
                $ownerBusinessUserId = $user['id'];
                break;
            }
        }

        if (!$ownerBusinessUserId) {
            $this->markTestSkipped('No owner found');
        }

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson("/api/v1/business/users/{$ownerBusinessUserId}/resend-invitation");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'CANNOT_RESEND_TO_OWNER',
            ]);

        echo "\n✅ Resend invitation correctly rejects owner";
    }

    public function test_reset_password_endpoint(): void
    {
        if (!$this->token || !$this->staffBusinessUserId) {
            $this->markTestSkipped('No auth token or staff user');
        }

        Mail::fake();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson("/api/v1/business/users/{$this->staffBusinessUserId}/reset-password", [
                'send_email' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user_id',
                    'email',
                    'password_reset_at',
                    'email_sent',
                    'temporary_password',
                ],
            ]);

        // Verify temporary password is returned when send_email is false
        $this->assertNotNull($response->json('data.temporary_password'));
        $this->assertFalse($response->json('data.email_sent'));

        echo "\n✅ Reset password endpoint works (without email)";
    }

    public function test_reset_password_with_custom_password(): void
    {
        if (!$this->token || !$this->staffBusinessUserId) {
            $this->markTestSkipped('No auth token or staff user');
        }

        Mail::fake();

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson("/api/v1/business/users/{$this->staffBusinessUserId}/reset-password", [
                'new_password' => 'CustomPass123!',
                'send_email' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        echo "\n✅ Reset password with custom password works";
    }

    public function test_reset_password_fails_for_owner(): void
    {
        if (!$this->token) {
            $this->markTestSkipped('No auth token');
        }

        // Get owner's business user ID
        $usersResponse = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/business/users');
        
        $users = $usersResponse->json('data.users') ?? [];
        $ownerBusinessUserId = null;
        foreach ($users as $user) {
            if ($user['role'] === 'owner') {
                $ownerBusinessUserId = $user['id'];
                break;
            }
        }

        if (!$ownerBusinessUserId) {
            $this->markTestSkipped('No owner found');
        }

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson("/api/v1/business/users/{$ownerBusinessUserId}/reset-password");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'CANNOT_RESET_OWNER',
            ]);

        echo "\n✅ Reset password correctly rejects owner";
    }

    public function test_forgot_password_stores_code_in_cache(): void
    {
        $testEmail = 'najiibkarshe81@gmail.com';

        Mail::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $testEmail,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => $testEmail,
                    'expires_in' => 900,
                ],
            ]);

        // Verify code was stored in cache
        $cached = Cache::get('password_reset_' . $testEmail);
        $this->assertNotNull($cached);
        $this->assertArrayHasKey('code', $cached);
        $this->assertEquals(6, strlen($cached['code']));

        echo "\n✅ Forgot password stores OTP code";
    }

    public function test_forgot_password_with_invalid_email_returns_success(): void
    {
        // Security: Should return success even for non-existent emails
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        echo "\n✅ Forgot password returns success for security";
    }
}
