<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Account extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'business_id',
        'branch_id',
        'name',
        'type',
        'balance',
        'currency',
        'provider',
        'account_number',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    const TYPE_CASH = 'cash';
    const TYPE_MOBILE_MONEY = 'mobile_money';
    const TYPE_BANK = 'bank';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'balance', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function incomeTransactions(): HasMany
    {
        return $this->hasMany(IncomeTransaction::class);
    }

    public function expenseTransactions(): HasMany
    {
        return $this->hasMany(ExpenseTransaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeForBusiness($query, $businessId, $branchId = null)
    {
        $query->where('business_id', $businessId);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query;
    }

    public function credit(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    public function debit(float $amount): void
    {
        $this->decrement('balance', $amount);
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_CASH => 'Cash',
            self::TYPE_MOBILE_MONEY => 'Mobile Money',
            self::TYPE_BANK => 'Bank',
            default => ucfirst($this->type),
        };
    }
}
