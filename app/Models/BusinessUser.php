<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessUser extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'role',
        'branch_id',
        'permissions',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    const ROLE_OWNER = 'owner';
    const ROLE_ADMIN = 'admin';
    const ROLE_STAFF = 'staff';

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isOwner() || $this->isAdmin()) {
            return true;
        }
        
        return isset($this->permissions[$permission]) && $this->permissions[$permission] === true;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
