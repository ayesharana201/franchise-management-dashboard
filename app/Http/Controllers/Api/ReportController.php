<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ScopesToUser;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ScopesToUser;

    public function financials(Request $request, Location $location, ReportService $reports): JsonResponse
    {
        $this->authorizeLocation($location);
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);

        $report = $reports->locationFinancials($location, $request->from, $request->to);
        unset($report['location']);

        return response()->json(['location_id' => $location->id] + $report);
    }
}
