<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PushNotification extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'title',
        'body',
        'data',
        'image_url',
        'audience',
        'target_user_id',
        'topic',
        'total_recipients',
        'success_count',
        'failure_count',
        'status',
        'error_message',
        'failed_tokens',
        'sent_by',
        'sent_at',
        'completed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'failed_tokens' => 'array',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ─── Activity Log ────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'audience', 'status', 'total_recipients'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Push notification {$eventName}");
    }

    // ─── Relationships ───────────────────────────────────────

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    // ─── Scopes ──────────────────────────────────────────────

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ─── Methods ─────────────────────────────────────────────

    public function markAsSending(): void
    {
        $this->update([
            'status' => 'sending',
            'sent_at' => now(),
        ]);
    }

    public function markAsSent(int $successCount, int $failureCount, ?array $failedTokens = null): void
    {
        $status = 'sent';
        if ($failureCount > 0 && $successCount === 0) {
            $status = 'failed';
        } elseif ($failureCount > 0 && $successCount > 0) {
            $status = 'partial';
        }

        $this->update([
            'status' => $status,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'failed_tokens' => $failedTokens,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    // ─── Computed Attributes ─────────────────────────────────

    public function getDeliveryRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }
        return round(($this->success_count / $this->total_recipients) * 100, 1);
    }

    public function getAudienceLabelAttribute(): string
    {
        return match ($this->audience) {
            'all' => 'All Users',
            'individual' => 'Individual Users',
            'business' => 'Business Users',
            'specific' => $this->targetUser?->name ?? 'Specific User',
            default => ucfirst($this->audience),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'sent' => 'success',
            'partial' => 'warning',
            'failed' => 'danger',
            'sending' => 'info',
            'pending' => 'gray',
            default => 'gray',
        };
    }
}
