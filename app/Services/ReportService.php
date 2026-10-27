<?php

namespace App\Services;

use App\Models\ComplianceCheck;
use App\Models\Location;
use App\Models\RoyaltyStatement;
use App\Models\SalesRecord;
use App\Models\User;
use Illuminate\Support\Collection;

class ReportService
{
    /** Network-level KPIs for the dashboard, scoped to what the user can see. */
    public function dashboard(User $user, ?string $from = null, ?string $to = null): array
    {
        $locationIds = Location::visibleTo($user)->pluck('id');
        $from = $from ?: now()->subMonths(5)->startOfMonth()->toDateString();
        $to = $to ?: now()->endOfMonth()->toDateString();

        $sales = SalesRecord::whereIn('location_id', $locationIds)
            ->whereBetween('period', [$from, $to]);

        $totalSales = (float) (clone $sales)->sum('gross_sales');
        $totalExpenses = (float) (clone $sales)->selectRaw('SUM(cost_of_goods + labor_cost + other_expenses) as e')->value('e');

        $royaltiesOutstanding = (float) RoyaltyStatement::whereIn('location_id', $locationIds)
            ->whereIn('status', ['pending', 'overdue'])->sum('total_due');

        $checks = ComplianceCheck::whereIn('location_id', $locationIds);
        $totalChecks = (clone $checks)->count();
        $passed = (clone $checks)->where('status', 'passed')->count();

        return [
            'location_count' => $locationIds->count(),
            'total_sales' => $totalSales,
            'net_profit' => $totalSales - $totalExpenses,
            'royalties_outstanding' => $royaltiesOutstanding,
            'overdue_statements' => RoyaltyStatement::whereIn('location_id', $locationIds)->where('status', 'overdue')->count(),
            'compliance_rate' => $totalChecks > 0 ? round($passed / $totalChecks * 100, 1) : null,
            'failed_checks' => (clone $checks)->where('status', 'failed')->count(),
            'monthly_sales' => $this->monthlySales($locationIds, $from, $to),
            'leaderboard' => $this->leaderboard($locationIds, $from, $to),
        ];
    }

    /** @return Collection<int, array{period: string, gross_sales: float}> */
    public function monthlySales(Collection $locationIds, string $from, string $to): Collection
    {
        return SalesRecord::whereIn('location_id', $locationIds)
            ->whereBetween('period', [$from, $to])
            ->get()
            ->groupBy(fn ($r) => $r->period->format('Y-m'))
            ->map(fn ($rows, $month) => ['period' => $month, 'gross_sales' => round((float) $rows->sum('gross_sales'), 2)])
            ->sortKeys()
            ->values();
    }

    /** Locations ranked by sales in the window. */
    public function leaderboard(Collection $locationIds, string $from, string $to, int $limit = 10): Collection
    {
        return SalesRecord::with('location')
            ->whereIn('location_id', $locationIds)
            ->whereBetween('period', [$from, $to])
            ->get()
            ->groupBy('location_id')
            ->map(fn ($rows) => [
                'location_id' => $rows->first()->location_id,
                'name' => $rows->first()->location->name,
                'region' => $rows->first()->location->region,
                'gross_sales' => round((float) $rows->sum('gross_sales'), 2),
                'net_profit' => round($rows->sum(fn ($r) => $r->netProfit()), 2),
                'avg_rating' => round((float) $rows->avg('customer_rating'), 2),
            ])
            ->sortByDesc('gross_sales')
            ->take($limit)
            ->values();
    }

    /** Per-location P&L over a date range. */
    public function locationFinancials(Location $location, ?string $from = null, ?string $to = null): array
    {
        $from = $from ?: now()->subMonths(11)->startOfMonth()->toDateString();
        $to = $to ?: now()->endOfMonth()->toDateString();

        $rows = SalesRecord::where('location_id', $location->id)
            ->whereBetween('period', [$from, $to])->orderBy('period')->get();

        $months = $rows->map(fn ($r) => [
            'period' => $r->period->format('Y-m'),
            'gross_sales' => (float) $r->gross_sales,
            'cost_of_goods' => (float) $r->cost_of_goods,
            'labor_cost' => (float) $r->labor_cost,
            'other_expenses' => (float) $r->other_expenses,
            'net_profit' => round($r->netProfit(), 2),
            'transactions' => $r->transactions,
        ]);

        $fees = RoyaltyStatement::where('location_id', $location->id)
            ->whereBetween('period', [$from, $to])->get();

        return [
            'location' => $location,
            'from' => $from,
            'to' => $to,
            'months' => $months,
            'totals' => [
                'gross_sales' => round($months->sum('gross_sales'), 2),
                'expenses' => round($months->sum(fn ($m) => $m['cost_of_goods'] + $m['labor_cost'] + $m['other_expenses']), 2),
                'net_profit' => round($months->sum('net_profit'), 2),
                'fees_paid' => round((float) $fees->where('status', 'paid')->sum('total_due'), 2),
                'fees_outstanding' => round((float) $fees->whereIn('status', ['pending', 'overdue'])->sum('total_due'), 2),
            ],
        ];
    }
}
