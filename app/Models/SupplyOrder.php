<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplyOrder extends Model
{
    public const STATUSES = ['submitted', 'approved', 'shipped', 'delivered', 'cancelled'];

    protected $fillable = ['location_id', 'user_id', 'status', 'total', 'notes'];

    protected function casts(): array
    {
        return ['total' => 'decimal:2'];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplyOrderItem::class);
    }
}
