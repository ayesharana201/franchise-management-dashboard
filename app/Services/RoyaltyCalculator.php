<?php

namespace App\Services;

use App\Models\Location;
use App\Models\RoyaltyStatement;
use App\Models\SalesRecord;
use Carbon\Carbon;

class RoyaltyCalculator
{
    /**
     * Calculate fees owed on a given gross sales amount.
     * Royalty and marketing fee are percentages of gross sales; tech fee is flat monthly.
     *
     * @return array{gross_sales: float, royalty_amount: float, marketing_fee: float, tech_fee: float, total_due: float}
     */
    public function calculate(Location $location, float $grossSales): array
    {
        $grossSales = max(0.0, $grossSales);
        $royalty = round($grossSales * ((float) $location->royalty_rate / 100), 2);
        $marketing = round($grossSales * ((float) $location->marketing_fee_rate / 100), 2);
        $tech = round((float) $location->tech_fee, 2);

        return [
            'gross_sales' => round($grossSales, 2),
            'royalty_amount' => $royalty,
            'marketing_fee' => $marketing,
            'tech_fee' => $tech,
            'total_due' => round($royalty + $marketing + $tech, 2),
        ];
    }

    /** Create (or refresh, if still unpaid) the statement for one location and month. */
    public function generateStatement(SalesRecord $record): RoyaltyStatement
    {
        $location = $record->location;
        $period = Carbon::parse($record->period)->startOfMonth();
        $amounts = $this->calculate($location, (float) $record->gross_sales);

        $existing = RoyaltyStatement::where('location_id', $location->id)
            ->whereDate('period', $period)->first();

        if ($existing && $existing->status === 'paid') {
            return $existing; // never rewrite a paid statement
        }

        return RoyaltyStatement::updateOrCreate(
            ['location_id' => $location->id, 'period' => $period->toDateString()],
            $amounts + [
                'status' => 'pending',
                'due_date' => $period->copy()->addMonth()->day(15)->toDateString(),
            ]
        );
    }

    /** Flag unpaid statements past their due date. Returns number updated. */
    /** Keep unpaid statement statuses consistent with their due dates. */
public function markOverdue(): int
{
    $today = now()->toDateString();

    // Unpaid statements whose due dates have passed.
    $markedOverdue = RoyaltyStatement::where('status', 'pending')
        ->whereDate('due_date', '<', $today)
        ->update(['status' => 'overdue']);

    // Statements incorrectly marked overdue when not yet past due.
    $restoredToPending = RoyaltyStatement::where('status', 'overdue')
        ->whereDate('due_date', '>=', $today)
        ->update(['status' => 'pending']);

    return $markedOverdue + $restoredToPending;
}
}
