<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Plan extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_cycle',
        'billing_days',
        'trial_enabled',
        'trial_days',
        'features',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'trial_enabled' => 'boolean',
        'trial_days' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'price', 'is_active', 'is_default', 'trial_enabled', 'trial_days'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public static function getDefault()
    {
        return static::active()->default()->first();
    }

    public function hasFeature(string $feature): bool
    {
        return isset($this->features[$feature]) && $this->features[$feature] === true;
    }

    public function normalizedFeatures(): array
    {
        $raw = $this->features;

        $known = [
            'accounts',
            'income',
            'expense',
            'customers',
            'vendors',
            'stock',
            'multi_branch',
            'profit_loss',
            'dashboard',
            'users',
        ];

        // If stored as list of enabled feature keys, convert to map.
        if (is_array($raw) && array_is_list($raw)) {
            $map = array_fill_keys($known, false);
            foreach ($raw as $key) {
                if (is_string($key) && in_array($key, $known, true)) {
                    $map[$key] = true;
                }
            }

            return $map;
        }

        $raw = is_array($raw) ? $raw : [];

        // Accept legacy/alternate keys.
        if (!array_key_exists('multi_branch', $raw) && array_key_exists('branches', $raw)) {
            $raw['multi_branch'] = $raw['branches'];
        }
        if (!array_key_exists('profit_loss', $raw) && array_key_exists('profit_loss_reports', $raw)) {
            $raw['profit_loss'] = $raw['profit_loss_reports'];
        }
        if (!array_key_exists('stock', $raw) && array_key_exists('stock_management', $raw)) {
            $raw['stock'] = $raw['stock_management'];
        }

        // Default behavior matches the rest of the product: missing feature keys are treated as enabled.
        $normalized = [];
        foreach ($known as $key) {
            $value = $raw[$key] ?? true;

            if (is_string($value)) {
                $value = $value === 'true';
            }

            $normalized[$key] = (bool) $value;
        }

        return $normalized;
    }
}
