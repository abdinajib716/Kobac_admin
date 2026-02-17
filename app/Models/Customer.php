<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use LogsActivity;

    protected $fillable = [
        'business_id',
        'branch_id',
        'name',
        'phone',
        'email',
        'address',
        'balance',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'balance', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerTransaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBusiness($query, $businessId, $branchId = null)
    {
        $query->where('business_id', $businessId);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query;
    }

    public function debit(float $amount, string $description, $createdBy): CustomerTransaction
    {
        $this->increment('balance', $amount);
        
        return $this->transactions()->create([
            'branch_id' => $this->branch_id,
            'type' => 'debit',
            'amount' => $amount,
            'description' => $description,
            'balance_after' => $this->balance,
            'transaction_date' => now()->toDateString(),
            'created_by' => $createdBy,
        ]);
    }

    public function credit(float $amount, string $description, $createdBy): CustomerTransaction
    {
        $this->decrement('balance', $amount);
        
        return $this->transactions()->create([
            'branch_id' => $this->branch_id,
            'type' => 'credit',
            'amount' => $amount,
            'description' => $description,
            'balance_after' => $this->balance,
            'transaction_date' => now()->toDateString(),
            'created_by' => $createdBy,
        ]);
    }

    public function getStatusAttribute(): string
    {
        return $this->balance > 0 ? 'owes_us' : ($this->balance < 0 ? 'we_owe' : 'settled');
    }
}
