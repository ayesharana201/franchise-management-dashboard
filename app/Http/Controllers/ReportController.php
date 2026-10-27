<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToUser;
use App\Models\Location;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ScopesToUser;

    public function index(Request $request, ReportService $reports): View
    {
        $locations = $this->visibleLocations();
        $request->validate([
            'location_id' => ['nullable', 'exists:locations,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $location = $request->filled('location_id')
            ? Location::findOrFail($request->location_id)
            : $locations->first();

        abort_if($location === null, 404, 'No locations available.');
        $this->authorizeLocation($location);

        return view('reports.index', [
            'locations' => $locations,
            'report' => $reports->locationFinancials($location, $request->from, $request->to),
        ]);
    }

    public function export(Request $request, Location $location, ReportService $reports): StreamedResponse
    {
        $this->authorizeLocation($location);
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);

        $report = $reports->locationFinancials($location, $request->from, $request->to);

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Month', 'Gross sales', 'Cost of goods', 'Labor', 'Other expenses', 'Net profit', 'Transactions']);
            foreach ($report['months'] as $m) {
                fputcsv($out, [$m['period'], $m['gross_sales'], $m['cost_of_goods'], $m['labor_cost'], $m['other_expenses'], $m['net_profit'], $m['transactions']]);
            }
            fclose($out);
        }, "financials-{$location->code}.csv", ['Content-Type' => 'text/csv']);
    }
}
