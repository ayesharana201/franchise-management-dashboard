<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ScopesToUser;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    use ScopesToUser;

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Location::visibleTo($request->user())
                ->when($request->filled('region'), fn ($q) => $q->where('region', $request->region))
                ->orderBy('name')->paginate(25)
        );
    }

    public function show(Location $location, ReportService $reports): JsonResponse
    {
        $this->authorizeLocation($location);

        return response()->json(['location' => $location, 'financials' => $reports->locationFinancials($location)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:locations,code'],
            'region' => ['required', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'onboarding', 'suspended', 'closed'])],
            'royalty_rate' => ['required', 'numeric', 'between:0,100'],
            'marketing_fee_rate' => ['required', 'numeric', 'between:0,100'],
            'tech_fee' => ['required', 'numeric', 'min:0'],
        ]);

        return response()->json(Location::create($data), 201);
    }
}
