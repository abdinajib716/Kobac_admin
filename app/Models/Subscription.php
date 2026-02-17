<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class Subscription extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'payment_method',
        'payment_reference',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    const STATUS_TRIAL = 'trial';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PENDING_PAYMENT = 'pending_payment';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'plan_id', 'trial_ends_at', 'ends_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isOnTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL && 
               $this->trial_ends_at && 
               $this->trial_ends_at->isFuture();
    }

    public function isTrialExpired(): bool
    {
        return $this->status === self::STATUS_TRIAL && 
               $this->trial_ends_at && 
               $this->trial_ends_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && 
               ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
               ($this->ends_at && $this->ends_at->isPast());
    }

    public function canWrite(): bool
    {
        return $this->isOnTrial() || $this->isActive();
    }

    public function canRead(): bool
    {
        return true;
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->isOnTrial() && $this->trial_ends_at) {
            return max(0, Carbon::now()->diffInDays($this->trial_ends_at, false));
        }
        
        if ($this->isActive() && $this->ends_at) {
            return max(0, Carbon::now()->diffInDays($this->ends_at, false));
        }
        
        return 0;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->isPendingPayment()) {
            return 'Pending Payment Approval';
        }
        
        if ($this->isOnTrial()) {
            return 'Trial (' . $this->days_remaining . ' days left)';
        }
        
        if ($this->isTrialExpired()) {
            return 'Trial Expired';
        }
        
        if ($this->isActive()) {
            return 'Active';
        }
        
        return ucfirst($this->status);
    }

    public function isPendingPayment(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT;
    }

    public static function createTrialForUser(User $user, Plan $plan): self
    {
        return static::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => self::STATUS_TRIAL,
            'trial_ends_at' => $plan->trial_enabled 
                ? Carbon::now()->addDays($plan->trial_days) 
                : null,
            'starts_at' => now(),
        ]);
    }
}
