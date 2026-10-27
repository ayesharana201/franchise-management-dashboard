<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'region', 'address', 'city', 'manager_name', 'status',
        'opened_on', 'royalty_rate', 'marketing_fee_rate', 'tech_fee',
    ];

    protected function casts(): array
    {
        return [
            'opened_on' => 'date',
            'royalty_rate' => 'decimal:2',
            'marketing_fee_rate' => 'decimal:2',
            'tech_fee' => 'decimal:2',
        ];
    }

    public function salesRecords(): HasMany
    {
        return $this->hasMany(SalesRecord::class);
    }

    public function royaltyStatements(): HasMany
    {
        return $this->hasMany(RoyaltyStatement::class);
    }

    public function complianceChecks(): HasMany
    {
        return $this->hasMany(ComplianceCheck::class);
    }

    public function supplyOrders(): HasMany
    {
        return $this->hasMany(SupplyOrder::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Restrict a query to the locations the given user may see. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->isFranchisor() => $query,
            $user->isRegionalManager() => $query->where('region', $user->region),
            default => $query->where('id', $user->location_id),
        };
    }
}
