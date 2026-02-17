<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaafiPayService
{
    protected $config;
    protected $isEnabled;

    public function __construct()
    {
        $this->config = [
            'merchant_uid' => Setting::get('waafipay_merchant_uid'),
            'api_user_id' => Setting::get('waafipay_api_user_id'),
            'api_key' => Setting::get('waafipay_api_key'),
            'merchant_no' => Setting::get('waafipay_merchant_no'),
            'api_url' => Setting::get('waafipay_api_url', 'https://api.waafipay.net/asm'),
            'environment' => Setting::get('waafipay_environment', 'LIVE'),
        ];

        $this->isEnabled = Setting::get('waafipay_enabled', false);
    }

    public function isConfigured(): bool
    {
        return $this->isEnabled &&
               !empty($this->config['merchant_uid']) &&
               !empty($this->config['api_user_id']) &&
               !empty($this->config['api_key']) &&
               !empty($this->config['merchant_no']);
    }

    public function purchase(array $params): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Payment gateway is not configured. Please contact support.',
            ];
        }

        $phone = $this->formatPhoneNumber($params['phone_number']);
        $walletType = $params['wallet_type'] ?? $this->detectWalletType($phone);
        $referenceId = 'TXN-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -6));

        $transaction = PaymentTransaction::create([
            'user_id' => $params['customer_id'] ?? auth()->id(),
            'reference_id' => $referenceId,
            'invoice_id' => $params['invoice_id'] ?? null,
            'payment_method' => 'WALLET_ACCOUNT',
            'wallet_type' => $walletType,
            'phone_number' => $phone,
            'customer_name' => $params['customer_name'] ?? null,
            'amount' => $params['amount'],
            'currency' => $params['currency'] ?? 'USD',
            'description' => $params['description'] ?? 'Payment transaction',
            'status' => 'pending',
            'channel' => $params['channel'] ?? 'WEB',
            'environment' => $this->config['environment'],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'initiated_at' => now(),
        ]);

        $payload = [
            'schemaVersion' => '1.0',
            'requestId' => $referenceId,
            'timestamp' => time(),
            'channelName' => 'WEB',
            'serviceName' => 'API_PURCHASE',
            'serviceParams' => [
                'merchantUid' => (string) $this->config['merchant_uid'],
                'apiUserId' => (string) $this->config['api_user_id'],
                'apiKey' => (string) $this->config['api_key'],
                'paymentMethod' => 'MWALLET_ACCOUNT',
                'payerInfo' => [
                    'accountNo' => (string) $phone,
                ],
                'transactionInfo' => [
                    'referenceId' => $referenceId,
                    'invoiceId' => $params['invoice_id'] ?? $referenceId,
                    'amount' => (string) $params['amount'],
                    'currency' => $params['currency'] ?? 'USD',
                    'description' => $params['description'] ?? 'Payment transaction',
                    'merchantNo' => (string) $this->config['merchant_no'],
                ],
            ],
        ];

        $transaction->update(['request_payload' => $payload]);

        Log::info('WaafiPay Request', [
            'reference_id' => $referenceId,
            'api_url' => $this->config['api_url'],
            'environment' => $this->config['environment'],
            'merchant_uid' => $this->config['merchant_uid'],
            'merchant_no' => $this->config['merchant_no'],
            'phone' => $phone,
            'amount' => $params['amount'],
            'payload' => $payload,
        ]);

        try {
            $response = Http::timeout(30)
                ->post($this->config['api_url'], $payload);

            $responseData = $response->json();

            Log::info('WaafiPay Response', [
                'reference_id' => $referenceId,
                'status_code' => $response->status(),
                'response' => $responseData,
            ]);

            $responseCode = $responseData['responseCode'] ?? null;

            if ($responseCode === '2001') {
                $transaction->markAsSuccess($responseData);

                return [
                    'success' => true,
                    'status' => 'success',
                    'message' => '✅ Payment completed successfully!',
                    'transaction_id' => $transaction->id,
                    'reference_id' => $referenceId,
                    'waafi_transaction_id' => $responseData['params']['transactionId'] ?? null,
                    'data' => $responseData,
                ];
            } elseif ($responseCode === '2002') {
                $transaction->markAsProcessing($responseData);

                return [
                    'success' => true,
                    'status' => 'processing',
                    'message' => '📱 Payment request sent. Waiting for customer approval...',
                    'transaction_id' => $transaction->id,
                    'reference_id' => $referenceId,
                    'data' => $responseData,
                ];
            } else {
                $errorMessage = $this->mapErrorMessage($responseCode, $responseData['responseMsg'] ?? 'Payment failed');
                $transaction->markAsFailed($errorMessage, $responseData);

                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => $errorMessage,
                    'error_code' => $responseData['errorCode'] ?? $responseCode,
                    'response_code' => $responseCode,
                    'waafipay_message' => $responseData['responseMsg'] ?? 'Unknown error',
                    'transaction_id' => $transaction->id,
                    'reference_id' => $referenceId,
                ];
            }
        } catch (\Exception $e) {
            Log::error('WaafiPay API Error', [
                'reference_id' => $referenceId,
                'error' => $e->getMessage(),
            ]);

            $transaction->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Payment processing failed. Please try again.',
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
                'reference_id' => $referenceId,
            ];
        }
    }

    public function checkStatus(string $referenceId): array
    {
        $transaction = PaymentTransaction::where('reference_id', $referenceId)->first();

        if (!$transaction) {
            return [
                'success' => false,
                'message' => 'Transaction not found',
            ];
        }

        return [
            'success' => true,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'phone_number' => $transaction->phone_number,
            'transaction' => [
                'id' => $transaction->id,
                'reference_id' => $transaction->reference_id,
                'waafi_transaction_id' => $transaction->waafi_transaction_id,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'wallet_type' => $transaction->wallet_type,
                'phone_number' => $transaction->phone_number,
                'customer_name' => $transaction->customer_name,
                'description' => $transaction->description,
                'created_at' => $transaction->created_at,
                'completed_at' => $transaction->completed_at,
            ],
        ];
    }

    public function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 9) {
            return '252' . $phone;
        }

        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return '252' . substr($phone, 1);
        }

        if (strlen($phone) === 12 && substr($phone, 0, 3) === '252') {
            return $phone;
        }

        if (strlen($phone) > 12 && substr($phone, 0, 4) === '+252') {
            return substr($phone, 1);
        }

        return $phone;
    }

    public function detectWalletType(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $prefix = substr($phone, -9, 2);

        $walletMap = [
            '61' => 'EVC Plus',
            '62' => 'EVC Plus',
            '63' => 'Zaad Service',
            '65' => 'Sahal',
            '68' => 'Jeeb',
            '71' => 'Zaad Service',
            '90' => 'Jeeb',
        ];

        return $walletMap[$prefix] ?? 'EVC Plus';
    }

    protected function mapErrorMessage(string $code, string $defaultMessage): string
    {
        $errorMap = [
            '5102' => '💰 Haraaga xisaabtaadu kuguma filna. (Insufficient balance)',
            '5310' => '❌ Lacag bixinta waa la diiday. (Transaction rejected)',
            '5001' => '⚠️ Macluumaadka qalad ah. (Invalid parameters)',
            '5002' => '🔐 Xisaabta lama helin. (Account not found)',
            '5003' => '⏰ Waqtiga wuu dhamaaday. (Transaction timeout)',
            '5004' => '🚫 Xisaabta ma firna. (Account not active)',
            '4001' => '🔑 Aqoonsiga qalad ah. (Authentication failed)',
            '4002' => '🚷 Fasax ma lihid. (Unauthorized access)',
        ];

        return $errorMap[$code] ?? $defaultMessage;
    }

    public function getPaymentMethods(): array
    {
        $baseUrl = url('/images/payment-gateways/providers-telecome');

        return [
            [
                'id' => 'evc_plus',
                'name' => 'EVC Plus',
                'logo' => $baseUrl . '/evc-plus.png',
            ],
            [
                'id' => 'zaad',
                'name' => 'Zaad Service',
                'logo' => $baseUrl . '/zaad.png',
            ],
            [
                'id' => 'jeeb',
                'name' => 'Jeeb',
                'logo' => $baseUrl . '/jeeb.png',
            ],
            [
                'id' => 'sahal',
                'name' => 'Sahal',
                'logo' => $baseUrl . '/sahal.png',
            ],
        ];
    }
}
