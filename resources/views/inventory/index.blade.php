@extends('layouts.app')
@section('title', 'Inventory')
@section('heading', 'Supply Catalogue')

@section('actions')
    <a href="{{ route('orders.create') }}" class="bg-indigo-600 text-white rounded-md px-4 py-2 text-sm">Place supply order</a>
@endsection

@section('content')
@php $isFranchisor = auth()->user()->isFranchisor(); @endphp

<form method="GET" class="flex flex-wrap items-center gap-2 mb-4 text-sm">
    <input name="q" value="{{ request('q') }}" placeholder="Search name or SKU" class="rounded-md border border-slate-300 px-3 py-2">
    <label class="flex items-center gap-1"><input type="checkbox" name="low" value="1" @checked(request()->boolean('low'))> Low stock only</label>
    <button class="bg-slate-800 text-white rounded-md px-4 py-2">Filter</button>
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto mb-6">
    <table class="w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 bg-slate-50">
            <tr><th class="px-4 py-2">SKU</th><th class="px-4 py-2">Item</th><th class="px-4 py-2">Category</th><th class="px-4 py-2 text-right">Price</th><th class="px-4 py-2 text-right">Stock</th>@if ($isFranchisor)<th></th>@endif</tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($items as $i)
                <tr>
                    <td class="px-4 py-2 font-mono text-xs">{{ $i->sku }}</td>
                    <td class="px-4 py-2">{{ $i->name }} <span class="text-slate-400">/ {{ $i->unit }}</span></td>
                    <td class="px-4 py-2">{{ $i->category }}</td>
                    <td class="px-4 py-2 text-right">${{ number_format($i->unit_price, 2) }}</td>
                    <td class="px-4 py-2 text-right {{ $i->isLowStock() ? 'text-red-600 font-medium' : '' }}">{{ $i->stock }}@if ($i->isLowStock()) <span class="text-xs">(low)</span>@endif</td>
                    @if ($isFranchisor)
                        <td class="px-4 py-2 text-right">
                            <form method="POST" action="{{ route('inventory.destroy', $i) }}" onsubmit="return confirm('Remove this item?')">
                                @csrf @method('DELETE')<button class="text-red-600 hover:underline text-xs">Remove</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No items found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mb-6">{{ $items->links() }}</div>

@if ($isFranchisor)
<section class="bg-white rounded-xl border border-slate-200 p-5 text-sm max-w-3xl">
    <h2 class="font-semibold mb-3">Add catalogue item</h2>
    <form method="POST" action="{{ route('inventory.store') }}" class="grid grid-cols-2 md:grid-cols-3 gap-3">
        @csrf
        <label>SKU <input name="sku" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
        <label class="md:col-span-2">Name <input name="name" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
        <label>Category <input name="category" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
        <label>Unit <input name="unit" value="each" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
        <label>Unit price <input type="number" step="0.01" min="0" name="unit_price" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
        <label>Stock <input type="number" min="0" name="stock" value="0" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
        <label>Reorder level <input type="number" min="0" name="reorder_level" value="10" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
        <button class="bg-indigo-600 text-white rounded-md py-2 self-end">Add item</button>
    </form>
</section>
@endif
@endsection
