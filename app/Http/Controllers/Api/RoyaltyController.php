<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ScopesToUser;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\RoyaltyStatement;
use App\Services\RoyaltyCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoyaltyController extends Controller
{
    use ScopesToUser;

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            RoyaltyStatement::with('location:id,name,code')
                ->whereIn('location_id', $this->visibleLocationIds())
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->latest('period')->paginate(25)
        );
    }

    public function calculate(Request $request, RoyaltyCalculator $calculator): JsonResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'gross_sales' => ['required', 'numeric', 'min:0'],
        ]);

        $location = Location::findOrFail($data['location_id']);
        $this->authorizeLocation($location);

        return response()->json($calculator->calculate($location, (float) $data['gross_sales']));
    }

    public function markPaid(RoyaltyStatement $statement): JsonResponse
    {
        $this->authorizeLocation($statement->location_id);
        $statement->update(['status' => 'paid', 'paid_at' => now()]);

        return response()->json($statement);
    }
}
