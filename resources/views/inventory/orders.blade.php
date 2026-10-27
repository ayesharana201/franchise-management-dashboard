@extends('layouts.app')
@section('title', 'Supply Orders')
@section('heading', 'Supply Orders')

@section('actions')
    <a href="{{ route('orders.create') }}" class="bg-indigo-600 text-white rounded-md px-4 py-2 text-sm">New order</a>
@endsection

@section('content')
<form method="GET" class="flex gap-2 mb-4 text-sm">
    <select name="status" class="rounded-md border border-slate-300 px-3 py-2">
        <option value="">Any status</option>
        @foreach (\App\Models\SupplyOrder::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
    </select>
    <button class="bg-slate-800 text-white rounded-md px-4 py-2">Filter</button>
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 bg-slate-50">
            <tr><th class="px-4 py-2">Order</th><th class="px-4 py-2">Location</th><th class="px-4 py-2">Placed by</th><th class="px-4 py-2">Date</th><th class="px-4 py-2 text-right">Lines</th><th class="px-4 py-2 text-right">Total</th><th class="px-4 py-2">Status</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($orders as $o)
                <tr>
                    <td class="px-4 py-2"><a class="text-indigo-600 hover:underline" href="{{ route('orders.show', $o) }}">#{{ $o->id }}</a></td>
                    <td class="px-4 py-2">{{ $o->location->name }}</td>
                    <td class="px-4 py-2">{{ $o->user->name }}</td>
                    <td class="px-4 py-2">{{ $o->created_at->format('M j, Y') }}</td>
                    <td class="px-4 py-2 text-right">{{ $o->items_count }}</td>
                    <td class="px-4 py-2 text-right">${{ number_format($o->total, 2) }}</td>
                    <td class="px-4 py-2"><x-badge :status="$o->status" /></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No orders yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
