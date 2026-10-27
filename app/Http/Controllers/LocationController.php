<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToUser;
use App\Models\Location;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationController extends Controller
{
    use ScopesToUser;

    public function index(Request $request): View
    {
        $locations = Location::visibleTo($request->user())
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->region))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$request->q}%")->orWhere('code', 'like', "%{$request->q}%")->orWhere('city', 'like', "%{$request->q}%")))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('locations.index', [
            'locations' => $locations,
            'regions' => Location::visibleTo($request->user())->distinct()->pluck('region'),
        ]);
    }

    public function show(Location $location, ReportService $reports): View
    {
        $this->authorizeLocation($location);

        return view('locations.show', [
            'location' => $location,
            'financials' => $reports->locationFinancials($location, null, null),
            'checks' => $location->complianceChecks()->with('item')->latest('due_date')->limit(8)->get(),
            'statements' => $location->royaltyStatements()->latest('period')->limit(6)->get(),
        ]);
    }

    public function create(): View
    {
        return view('locations.form', ['location' => new Location(['royalty_rate' => 6, 'marketing_fee_rate' => 2, 'tech_fee' => 150, 'status' => 'onboarding'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $location = Location::create($this->validated($request));

        return redirect()->route('locations.show', $location)->with('status', 'Location created.');
    }

    public function edit(Location $location): View
    {
        return view('locations.form', compact('location'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $location->update($this->validated($request, $location));

        return redirect()->route('locations.show', $location)->with('status', 'Location updated.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();

        return redirect()->route('locations.index')->with('status', 'Location removed.');
    }

    private function validated(Request $request, ?Location $location = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique('locations', 'code')->ignore($location?->id)],
            'region' => ['required', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'onboarding', 'suspended', 'closed'])],
            'opened_on' => ['nullable', 'date'],
            'royalty_rate' => ['required', 'numeric', 'between:0,100'],
            'marketing_fee_rate' => ['required', 'numeric', 'between:0,100'],
            'tech_fee' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
