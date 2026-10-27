<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToUser;
use App\Models\InventoryItem;
use App\Models\SupplyOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SupplyOrderController extends Controller
{
    use ScopesToUser;

    public function index(Request $request): View
    {
        $orders = SupplyOrder::with(['location', 'user'])->withCount('items')
            ->whereIn('location_id', $this->visibleLocationIds())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(15)->withQueryString();

        return view('inventory.orders', ['orders' => $orders]);
    }

    public function create(): View
    {
        return view('inventory.order-form', [
            'items' => InventoryItem::where('stock', '>', 0)->orderBy('name')->get(),
            'locations' => $this->visibleLocations(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:inventory_items,id', 'distinct'],
            'lines.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        $this->authorizeLocation((int) $data['location_id']);

        $lines = collect($data['lines'])->filter(fn ($l) => (int) $l['quantity'] > 0);
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => 'Add a quantity to at least one item.']);
        }

        $order = DB::transaction(function () use ($data, $lines, $request) {
            $order = SupplyOrder::create([
                'location_id' => $data['location_id'], 'user_id' => $request->user()->id,
                'status' => 'submitted', 'notes' => $data['notes'] ?? null, 'total' => 0,
            ]);

            $total = 0;
            foreach ($lines as $line) {
                $item = InventoryItem::lockForUpdate()->find($line['item_id']);
                $qty = (int) $line['quantity'];

                if ($item->stock < $qty) {
                    throw ValidationException::withMessages(['lines' => "Only {$item->stock} of {$item->name} in stock."]);
                }

                $item->decrement('stock', $qty);
                $order->items()->create(['inventory_item_id' => $item->id, 'quantity' => $qty, 'unit_price' => $item->unit_price]);
                $total += $qty * (float) $item->unit_price;
            }

            $order->update(['total' => round($total, 2)]);

            return $order;
        });

        return redirect()->route('orders.show', $order)->with('status', 'Order submitted.');
    }

    public function show(SupplyOrder $order): View
    {
        $this->authorizeLocation($order->location_id);

        return view('inventory.order-show', ['order' => $order->load(['location', 'user', 'items.item'])]);
    }

    /** Network staff move an order through its lifecycle. Cancelling returns stock. */
    public function updateStatus(Request $request, SupplyOrder $order): RedirectResponse
    {
        $this->authorizeLocation($order->location_id);

        $data = $request->validate(['status' => ['required', Rule::in(SupplyOrder::STATUSES)]]);

        abort_if($order->status === 'cancelled', 422, 'Cancelled orders cannot be changed.');

        DB::transaction(function () use ($order, $data) {
            if ($data['status'] === 'cancelled' && $order->status !== 'cancelled') {
                foreach ($order->items as $line) {
                    $line->item->increment('stock', $line->quantity);
                }
            }
            $order->update(['status' => $data['status']]);
        });

        return back()->with('status', 'Order status updated.');
    }
}
