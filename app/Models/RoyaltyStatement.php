<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoyaltyStatement extends Model
{
    protected $fillable = [
        'location_id', 'period', 'gross_sales', 'royalty_amount', 'marketing_fee',
        'tech_fee', 'total_due', 'status', 'due_date', 'paid_at',
    ];

    /** Plain Y-m-d string storage; see SalesRecord::period(). */
    protected function period(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value) : null,
            set: fn ($value) => Carbon::parse($value)->toDateString(),
        );
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'gross_sales' => 'decimal:2',
            'royalty_amount' => 'decimal:2',
            'marketing_fee' => 'decimal:2',
            'tech_fee' => 'decimal:2',
            'total_due' => 'decimal:2',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function isOverdue(): bool
{
    return $this->status !== 'paid'
        && $this->due_date !== null
        && $this->due_date->lt(today());
}
}
