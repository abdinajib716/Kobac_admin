<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PaymentTransaction extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'reference_id',
        'invoice_id',
        'subscription_id',
        'plan_id',
        'waafi_transaction_id',
        'payment_method',
        'payment_type',
        'wallet_type',
        'phone_number',
        'customer_name',
        'amount',
        'currency',
        'description',
        'status',
        'status_code',
        'status_message',
        'request_payload',
        'response_payload',
        'error_message',
        'channel',
        'environment',
        'ip_address',
        'user_agent',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'admin_notes',
        'proof_of_payment',
        'initiated_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount', 'waafi_transaction_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeOffline($query)
    {
        return $query->where('payment_type', 'offline');
    }

    public function scopeOnline($query)
    {
        return $query->where('payment_type', 'online');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function isOffline(): bool
    {
        return $this->payment_type === 'offline';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function markAsSuccess(array $response = []): bool
    {
        $this->update([
            'status' => 'success',
            'completed_at' => now(),
            'response_payload' => $response,
            'status_code' => $response['responseCode'] ?? null,
            'status_message' => $response['responseMsg'] ?? null,
            'waafi_transaction_id' => $response['params']['transactionId'] ?? $this->waafi_transaction_id,
        ]);

        return true;
    }

    public function markAsFailed(string $error, array $response = []): bool
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $error,
            'response_payload' => $response,
            'status_code' => $response['responseCode'] ?? null,
            'status_message' => $response['responseMsg'] ?? null,
        ]);

        return true;
    }

    public function markAsProcessing(array $response = []): bool
    {
        $this->update([
            'status' => 'processing',
            'response_payload' => $response,
            'status_code' => $response['responseCode'] ?? null,
            'status_message' => $response['responseMsg'] ?? null,
            'waafi_transaction_id' => $response['params']['transactionId'] ?? null,
        ]);

        return true;
    }
}
