<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Business extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'name',
        'legal_name',
        'phone',
        'email',
        'address',
        'logo',
        'currency',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'legal_name', 'currency'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function mainBranch()
    {
        return $this->branches()->where('is_main', true)->first();
    }

    public function users(): HasMany
    {
        return $this->hasMany(BusinessUser::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }

    public function incomeTransactions(): HasMany
    {
        return $this->hasMany(IncomeTransaction::class);
    }

    public function expenseTransactions(): HasMany
    {
        return $this->hasMany(ExpenseTransaction::class);
    }

    public function getTotalIncomeAttribute(): float
    {
        return $this->incomeTransactions()->sum('amount');
    }

    public function getTotalExpenseAttribute(): float
    {
        return $this->expenseTransactions()->sum('amount');
    }

    public function getProfitLossAttribute(): float
    {
        return $this->total_income - $this->total_expense;
    }

    public function getTotalReceivablesAttribute(): float
    {
        return $this->customers()->where('balance', '>', 0)->sum('balance');
    }

    public function getTotalPayablesAttribute(): float
    {
        return $this->vendors()->where('balance', '>', 0)->sum('balance');
    }
}
