<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class IncomeTransaction extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'business_id',
        'branch_id',
        'account_id',
        'amount',
        'description',
        'category',
        'reference',
        'transaction_date',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount', 'description', 'category', 'transaction_date'])
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function scopeDateRange($query, $from = null, $to = null)
    {
        if ($from) {
            $query->whereDate('transaction_date', '>=', $from);
        }
        
        if ($to) {
            $query->whereDate('transaction_date', '<=', $to);
        }
        
        return $query;
    }
}
