<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Services\OfflinePaymentService;
use App\Services\WaafiPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $waafiPay;
    protected $offlinePayment;

    public function __construct(WaafiPayService $waafiPay, OfflinePaymentService $offlinePayment)
    {
        $this->waafiPay = $waafiPay;
        $this->offlinePayment = $offlinePayment;
    }

    public function methods(): JsonResponse
    {
        $methods = $this->offlinePayment->getAvailablePaymentMethods();

        if (empty($methods)) {
            return response()->json([
                'success' => false,
                'message' => 'No payment methods available. Please contact support.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Initiate offline payment request
     * POST /api/v1/payment/offline
     */
    public function initiateOffline(Request $request): JsonResponse
    {
        if (!$this->offlinePayment->isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Offline payment is not available.',
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
            'proof_of_payment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();

        if (!$user->isBusiness()) {
            return response()->json([
                'success' => false,
                'message' => 'Only business users can subscribe to paid plans.',
            ], 403);
        }

        $result = $this->offlinePayment->initiatePayment([
            'user' => $user,
            'plan_id' => $request->plan_id,
            'proof_of_payment' => $request->proof_of_payment,
            'channel' => 'MOBILE',
        ]);

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    /**
     * Check offline payment status
     * POST /api/v1/payment/offline/status
     */
    public function offlineStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->offlinePayment->checkStatus($request->reference_id);

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 404);
    }

    /**
     * Get offline payment instructions
     * GET /api/v1/payment/offline/instructions
     */
    public function offlineInstructions(): JsonResponse
    {
        if (!$this->offlinePayment->isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Offline payment is not available.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'instructions' => $this->offlinePayment->getInstructions(),
        ]);
    }

    public function initiate(Request $request): JsonResponse
    {
        if (!$this->waafiPay->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment gateway is not configured. Please contact support.',
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'string', 'regex:/^(61|62|63|65|68|71|90)\d{7}$/'],
            'amount' => 'required|numeric|min:0.01|max:10000',
            'wallet_type' => 'nullable|in:evc_plus,zaad,jeeb,sahal',
            'customer_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'invoice_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['customer_id'] = auth()->id();
        $data['channel'] = 'MOBILE';

        $result = $this->waafiPay->purchase($data);

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    public function status(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->waafiPay->checkStatus($request->reference_id);

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 404);
    }

    public function history(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);

        $transactions = PaymentTransaction::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        Log::info('WaafiPay Webhook Received', $request->all());

        $referenceId = $request->input('referenceId');
        $transactionId = $request->input('transactionId');
        $responseCode = $request->input('responseCode');
        $state = $request->input('state');

        if (!$referenceId) {
            return response()->json([
                'success' => false,
                'message' => 'Missing reference ID',
            ], 400);
        }

        $transaction = PaymentTransaction::where('reference_id', $referenceId)->first();

        if (!$transaction) {
            Log::warning('WaafiPay Webhook: Transaction not found', [
                'reference_id' => $referenceId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        if ($responseCode === '2001' || $state === 'APPROVED') {
            $transaction->markAsSuccess($request->all());

            Log::info('WaafiPay Webhook: Payment confirmed', [
                'reference_id' => $referenceId,
                'transaction_id' => $transactionId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed',
                'reference_id' => $referenceId,
            ]);
        } elseif ($state === 'REJECTED' || $state === 'FAILED') {
            $errorMessage = $request->input('responseMsg', 'Payment failed');
            $transaction->markAsFailed($errorMessage, $request->all());

            Log::info('WaafiPay Webhook: Payment failed', [
                'reference_id' => $referenceId,
                'error' => $errorMessage,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment status updated',
                'reference_id' => $referenceId,
            ]);
        }

        $transaction->update([
            'response_payload' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed',
            'reference_id' => $referenceId,
        ]);
    }
}
