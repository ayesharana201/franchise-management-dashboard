<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToUser;
use App\Models\Location;
use App\Models\RoyaltyStatement;
use App\Models\SalesRecord;
use App\Services\RoyaltyCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoyaltyController extends Controller
{
    use ScopesToUser;

    public function index(Request $request, RoyaltyCalculator $calculator): View
    {
        $calculator->markOverdue();

        $statements = RoyaltyStatement::with('location')
            ->whereIn('location_id', $this->visibleLocationIds())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->location_id))
            ->latest('period')->paginate(20)->withQueryString();

        return view('royalties.index', [
            'statements' => $statements,
            'locations' => $this->visibleLocations(),
            'preview' => session('preview'),
        ]);
    }

    /** Submit monthly sales for a location; automatically (re)generates that month's statement. */
    public function storeSales(Request $request, RoyaltyCalculator $calculator): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'period' => ['required', 'date_format:Y-m'],
            'gross_sales' => ['required', 'numeric', 'min:0'],
            'cost_of_goods' => ['nullable', 'numeric', 'min:0'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'other_expenses' => ['nullable', 'numeric', 'min:0'],
            'transactions' => ['nullable', 'integer', 'min:0'],
            'customer_rating' => ['nullable', 'numeric', 'between:0,5'],
        ]);

        $this->authorizeLocation((int) $data['location_id']);
        
$periodDate = $data['period'] . '-01';

$paidStatementExists = RoyaltyStatement::where(
    'location_id',
    $data['location_id']
)
    ->whereDate('period', $periodDate)
    ->where('status', 'paid')
    ->exists();

if ($paidStatementExists) {
    return back()
        ->withErrors([
            'period' => 'Sales cannot be changed because the royalty statement for this month has already been paid.',
        ])
        ->withInput();
}


        $record = SalesRecord::updateOrCreate(
            ['location_id' => $data['location_id'], 'period' => $data['period'] . '-01'],
            [
                'gross_sales' => $data['gross_sales'],
                'cost_of_goods' => $data['cost_of_goods'] ?? 0,
                'labor_cost' => $data['labor_cost'] ?? 0,
                'other_expenses' => $data['other_expenses'] ?? 0,
                'transactions' => $data['transactions'] ?? 0,
                'customer_rating' => $data['customer_rating'] ?? null,
            ]
        );

        $statement = $calculator->generateStatement($record->load('location'));

        return redirect()->route('royalties.index')
            ->with('status', 'Sales saved. Statement total due: ' . number_format((float) $statement->total_due, 2));
    }

    /** Stateless calculator: show what a given sales figure would cost in fees. */
    public function preview(Request $request, RoyaltyCalculator $calculator): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'gross_sales' => ['required', 'numeric', 'min:0'],
        ]);
        $location = Location::findOrFail($data['location_id']);
        $this->authorizeLocation($location);

        return redirect()->route('royalties.index')
            ->with('preview', ['location' => $location->name] + $calculator->calculate($location, (float) $data['gross_sales']));
    }

    public function markPaid(RoyaltyStatement $statement): RedirectResponse
{
    $this->authorizeLocation($statement->location_id);

    if ($statement->status === 'paid') {
        return back()->with('status', 'This statement is already marked as paid.');
    }

    $statement->update([
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    return back()->with('status', 'Statement marked as paid.');
}
}
