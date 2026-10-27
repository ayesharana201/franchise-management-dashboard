<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToUser;
use App\Models\ComplianceCheck;
use App\Models\ComplianceItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComplianceController extends Controller
{
    use ScopesToUser;

    public function index(Request $request): View
    {
        $checks = ComplianceCheck::with(['location', 'item', 'checker'])
            ->whereIn('location_id', $this->visibleLocationIds())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->location_id))
            ->orderByRaw("CASE status WHEN 'failed' THEN 0 WHEN 'pending' THEN 1 ELSE 2 END")
            ->orderBy('due_date')->paginate(20)->withQueryString();

        return view('compliance.index', [
            'checks' => $checks,
            'items' => ComplianceItem::where('is_active', true)->orderBy('title')->get(),
            'locations' => $this->visibleLocations(),
        ]);
    }

    /** Network staff: define a checklist item. */
    public function storeItem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['safety', 'brand', 'financial', 'operations'])],
            'frequency' => ['required', Rule::in(['weekly', 'monthly', 'quarterly', 'annual'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        ComplianceItem::create($data);

        return back()->with('status', 'Checklist item created.');
    }

    /** Network staff: assign a checklist item to one location, or all visible locations. */
    public function assign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'compliance_item_id' => ['required', 'exists:compliance_items,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $ids = $data['location_id'] ?? null
            ? collect([(int) $data['location_id']])
            : $this->visibleLocationIds();

        if (! empty($data['location_id'])) {
            $this->authorizeLocation((int) $data['location_id']);
        }

        foreach ($ids as $id) {
            ComplianceCheck::create([
                'location_id' => $id, 'compliance_item_id' => $data['compliance_item_id'],
                'due_date' => $data['due_date'], 'status' => 'pending',
            ]);
        }

        return back()->with('status', 'Assigned to ' . $ids->count() . ' location(s).');
    }

    /** Record the outcome of a check. Franchisees may complete checks for their own location. */
    public function update(Request $request, ComplianceCheck $check): RedirectResponse
    {
        $this->authorizeLocation($check->location_id);

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'passed', 'failed'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $check->update($data + [
            'checked_on' => $data['status'] === 'pending' ? null : now()->toDateString(),
            'checked_by' => $data['status'] === 'pending' ? null : $request->user()->id,
        ]);

        return back()->with('status', 'Check updated.');
    }
}
