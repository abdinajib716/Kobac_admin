<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StockItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'business_id',
        'branch_id',
        'name',
        'sku',
        'description',
        'quantity',
        'alert_threshold',
        'unit',
        'cost_price',
        'selling_price',
        'image',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'alert_threshold' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'quantity', 'cost_price', 'selling_price', 'is_active'])
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

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
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

    public function increase(float $quantity, string $reason, $createdBy, ?string $reference = null): StockMovement
    {
        $quantityBefore = $this->quantity;
        $this->increment('quantity', $quantity);
        $this->refresh();
        
        return $this->movements()->create([
            'branch_id' => $this->branch_id,
            'type' => 'increase',
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->quantity,
            'reason' => $reason,
            'reference' => $reference,
            'created_by' => $createdBy,
        ]);
    }

    public function decrease(float $quantity, string $reason, $createdBy, ?string $reference = null): StockMovement
    {
        $quantityBefore = $this->quantity;
        $this->decrement('quantity', $quantity);
        $this->refresh();
        
        return $this->movements()->create([
            'branch_id' => $this->branch_id,
            'type' => 'decrease',
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->quantity,
            'reason' => $reason,
            'reference' => $reference,
            'created_by' => $createdBy,
        ]);
    }

    public function getCostValueAttribute(): float
    {
        return $this->quantity * ($this->cost_price ?? 0);
    }

    public function getSellingValueAttribute(): float
    {
        return $this->quantity * ($this->selling_price ?? 0);
    }

    public function getIsLowStockAttribute(): bool
    {
        if ($this->alert_threshold === null) {
            return false;
        }
        return $this->quantity <= $this->alert_threshold;
    }

    public function scopeLowStock($query)
    {
        return $query->whereNotNull('alert_threshold')
            ->whereColumn('quantity', '<=', 'alert_threshold');
    }
}
