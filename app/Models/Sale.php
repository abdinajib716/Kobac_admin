<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'business_id',
        'branch_id',
        'customer_id',
        'idempotency_key',
        'sale_number',
        'receipt_number',
        'status',
        'sale_type',
        'payment_status',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'amount_paid',
        'amount_due',
        'cost_total',
        'profit_total',
        'notes',
        'sold_at',
        'created_by',
        'completed_at',
        'voided_at',
        'voided_by',
        'void_reason',
        'receipt_pdf_path',
        'meta',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'cost_total' => 'decimal:2',
        'profit_total' => 'decimal:2',
        'sold_at' => 'datetime',
        'completed_at' => 'datetime',
        'voided_at' => 'datetime',
        'meta' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function scopeForBusiness($query, int $businessId, ?int $branchId = null)
    {
        $query->where('business_id', $businessId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeDateRange($query, $from = null, $to = null)
    {
        if ($from) {
            $query->whereDate('sold_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('sold_at', '<=', $to);
        }

        return $query;
    }
}
