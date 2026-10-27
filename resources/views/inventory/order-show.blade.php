@extends('layouts.app')
@section('title', 'Order #' . $order->id)
@section('heading', 'Order #' . $order->id)

@section('actions')
    <a href="{{ route('orders.index') }}" class="text-sm text-indigo-600 hover:underline">← All orders</a>
@endsection

@section('content')
<div class="max-w-3xl space-y-4">
    <section class="bg-white rounded-xl border border-slate-200 p-5 text-sm grid grid-cols-2 gap-2">
        <div><span class="text-slate-500">Location:</span> {{ $order->location->name }}</div>
        <div><span class="text-slate-500">Status:</span> <x-badge :status="$order->status" /></div>
        <div><span class="text-slate-500">Placed by:</span> {{ $order->user->name }}</div>
        <div><span class="text-slate-500">Date:</span> {{ $order->created_at->format('M j, Y g:i A') }}</div>
        @if ($order->notes)<div class="col-span-2"><span class="text-slate-500">Notes:</span> {{ $order->notes }}</div>@endif
    </section>

    <section class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-slate-500 bg-slate-50">
                <tr><th class="px-4 py-2">Item</th><th class="px-4 py-2 text-right">Qty</th><th class="px-4 py-2 text-right">Unit price</th><th class="px-4 py-2 text-right">Total</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($order->items as $line)
                    <tr>
                        <td class="px-4 py-2">{{ $line->item->name }}</td>
                        <td class="px-4 py-2 text-right">{{ $line->quantity }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($line->unit_price, 2) }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($line->lineTotal(), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot><tr class="border-t border-slate-200 font-semibold"><td colspan="3" class="px-4 py-3 text-right">Total</td><td class="px-4 py-3 text-right">${{ number_format($order->total, 2) }}</td></tr></tfoot>
        </table>
    </section>

    @if (auth()->user()->isNetworkStaff() && $order->status !== 'cancelled')
        <form method="POST" action="{{ route('orders.status', $order) }}" class="bg-white rounded-xl border border-slate-200 p-5 flex items-end gap-3 text-sm">
            @csrf @method('PUT')
            <label>Update status
                <select name="status" class="mt-1 block rounded-md border border-slate-300 px-3 py-2">
                    @foreach (\App\Models\SupplyOrder::STATUSES as $s)<option value="{{ $s }}" @selected($order->status === $s)>{{ ucfirst($s) }}</option>@endforeach
                </select>
            </label>
            <button class="bg-indigo-600 text-white rounded-md px-5 py-2">Update</button>
            <span class="text-xs text-slate-500">Cancelling returns stock to the catalogue.</span>
        </form>
    @endif
</div>
@endsection
