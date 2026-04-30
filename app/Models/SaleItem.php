<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'stock_item_id',
        'product_name_snapshot',
        'sku_snapshot',
        'unit_snapshot',
        'quantity',
        'cost_price_snapshot',
        'unit_price',
        'line_discount',
        'line_tax',
        'line_total',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_price_snapshot' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_discount' => 'decimal:2',
        'line_tax' => 'decimal:2',
        'line_total' => 'decimal:2',
        'meta' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }
}
