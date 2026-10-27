<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesRecord extends Model
{
    protected $fillable = [
        'location_id', 'period', 'gross_sales', 'cost_of_goods', 'labor_cost',
        'other_expenses', 'transactions', 'customer_rating',
    ];

    /**
     * Stored as a plain Y-m-d string (not a `date` cast) so lookups like
     * updateOrCreate(['period' => '2026-08-01']) match on every database.
     */
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
            'gross_sales' => 'decimal:2',
            'cost_of_goods' => 'decimal:2',
            'labor_cost' => 'decimal:2',
            'other_expenses' => 'decimal:2',
            'customer_rating' => 'decimal:2',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function totalExpenses(): float
    {
        return (float) $this->cost_of_goods + (float) $this->labor_cost + (float) $this->other_expenses;
    }

    public function netProfit(): float
    {
        return (float) $this->gross_sales - $this->totalExpenses();
    }
}
