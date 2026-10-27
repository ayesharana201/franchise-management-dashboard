<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_FRANCHISOR = 'franchisor';
    public const ROLE_REGIONAL = 'regional_manager';
    public const ROLE_FRANCHISEE = 'franchisee';

    protected $fillable = ['name', 'email', 'password', 'role', 'location_id', 'region'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function isFranchisor(): bool
    {
        return $this->role === self::ROLE_FRANCHISOR;
    }

    public function isRegionalManager(): bool
    {
        return $this->role === self::ROLE_REGIONAL;
    }

    public function isFranchisee(): bool
    {
        return $this->role === self::ROLE_FRANCHISEE;
    }

    /** Franchisors and regional managers manage the network; franchisees only their own location. */
    public function isNetworkStaff(): bool
    {
        return $this->isFranchisor() || $this->isRegionalManager();
    }

    public function canAccessLocation(Location $location): bool
    {
        return match (true) {
            $this->isFranchisor() => true,
            $this->isRegionalManager() => $location->region === $this->region,
            default => $this->location_id === $location->id,
        };
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_FRANCHISOR => 'Franchisor',
            self::ROLE_REGIONAL => 'Regional Manager',
            default => 'Franchisee',
        };
    }
}
