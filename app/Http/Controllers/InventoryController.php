<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Models\SupplyOrderItem;

/** Master supply catalogue. Everyone can browse; only the franchisor edits it. */
class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $items = InventoryItem::query()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$request->q}%")->orWhere('sku', 'like', "%{$request->q}%")))
            ->when($request->boolean('low'), fn ($q) => $q->whereColumn('stock', '<=', 'reorder_level'))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('inventory.index', ['items' => $items]);
    }

    public function store(Request $request): RedirectResponse
    {
        InventoryItem::create($this->validated($request));

        return back()->with('status', 'Item added to catalogue.');
    }

    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        $item->update($this->validated($request, $item));

        return back()->with('status', 'Item updated.');
    }

    public function destroy(InventoryItem $item): RedirectResponse
{
    $isUsedInOrders = SupplyOrderItem::where('inventory_item_id', $item->id)
        ->exists();

    if ($isUsedInOrders) {
        return back()->with(
            'error',
            'This item cannot be deleted because it is referenced by existing supply orders.'
        );
    }

    $item->delete();

    return back()->with('status', 'Item removed.');
}

    private function validated(Request $request, ?InventoryItem $item = null): array
    {
        return $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique('inventory_items', 'sku')->ignore($item?->id)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:30'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
        ]);
    }
}
