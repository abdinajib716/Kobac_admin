<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = [
        'name',
        'code',
        'code_alpha2',
        'phone_code',
        'currency',
        'flag',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function activeRegions(): HasMany
    {
        return $this->hasMany(Region::class)->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Load country data from JSON configuration
     */
    public static function loadFromJson(string $jsonPath): array
    {
        if (!file_exists($jsonPath)) {
            return ['success' => false, 'message' => 'JSON file not found'];
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Invalid JSON format'];
        }

        return ['success' => true, 'data' => $data];
    }
}
