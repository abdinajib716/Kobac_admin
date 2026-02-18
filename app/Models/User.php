<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'username',
        'display_name',
        'avatar',
        'email',
        'phone',
        'country_id',
        'region_id',
        'district_id',
        'address',
        'password',
        'user_type',
        'is_active',
        'deactivated_at',
        'deactivated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }

    const TYPE_CLIENT = 'client';
    const TYPE_INDIVIDUAL = 'individual';
    const TYPE_BUSINESS = 'business';

    public function subscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Subscription::class)->latest();
    }

    public function business(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Business::class);
    }

    /**
     * Get all business memberships (as owner or staff)
     */
    public function businessMemberships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessUser::class);
    }

    /**
     * Get the current business (as owner OR staff member)
     * Returns owned business first, then staff membership
     */
    public function currentBusiness(): ?Business
    {
        // First check if user owns a business
        if ($this->business) {
            return $this->business;
        }

        // Then check if user is a staff member of any business
        $membership = $this->businessMemberships()->with('business')->where('is_active', true)->first();
        return $membership?->business;
    }

     public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function getLocationAttribute(): ?string
    {
        $parts = array_filter([
            $this->district?->name,
            $this->region?->name,
            $this->country?->name,
        ]);
        
        return !empty($parts) ? implode(', ', $parts) : null;
    }

    public function accounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function incomeTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(IncomeTransaction::class);
    }

    public function expenseTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExpenseTransaction::class);
    }

    public function deviceTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function activeDeviceTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DeviceToken::class)->where('is_active', true);
    }

    public function isIndividual(): bool
    {
        return $this->user_type === self::TYPE_INDIVIDUAL;
    }

    public function isBusiness(): bool
    {
        return $this->user_type === self::TYPE_BUSINESS;
    }

    public function isClient(): bool
    {
        return $this->user_type === self::TYPE_CLIENT;
    }

    public function isMobileUser(): bool
    {
        return $this->isIndividual() || $this->isBusiness();
    }

    public function canWrite(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->isIndividual()) {
            return true;
        }

        if ($this->isBusiness() && $this->subscription) {
            return $this->subscription->canWrite();
        }

        return $this->isClient();
    }

    public function canRead(): bool
    {
        return $this->is_active;
    }

    public function deactivate($deactivatedBy = null, ?string $reason = null): void
    {
        $this->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_by' => $deactivatedBy,
        ]);

        // Send deactivation email
        \App\Services\NotificationService::sendAccountDeactivated($this, $reason);
    }

    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'deactivated_at' => null,
            'deactivated_by' => null,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMobileUsers($query)
    {
        return $query->whereIn('user_type', [self::TYPE_INDIVIDUAL, self::TYPE_BUSINESS]);
    }

    public function scopeIndividuals($query)
    {
        return $query->where('user_type', self::TYPE_INDIVIDUAL);
    }

    public function scopeBusinessUsers($query)
    {
        return $query->where('user_type', self::TYPE_BUSINESS);
    }

    /**
     * Configure activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'first_name', 'last_name', 'username'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the URL for the user's avatar in Filament
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar ? \Storage::url($this->avatar) : null;
    }

    /**
     * Determine if user can access the Filament panel
     * Only 'client' (admin) users can access the admin panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isClient() && $this->is_active;
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token)
    {
        \App\Services\NotificationService::sendPasswordReset($this, $token);
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set 'name' when creating or updating
        static::saving(function ($user) {
            if ($user->first_name && $user->last_name) {
                $user->name = $user->first_name . ' ' . $user->last_name;
            }
        });
    }
}
