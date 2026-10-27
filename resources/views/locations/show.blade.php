@extends('layouts.app')
@section('title', $location->name)
@section('heading', $location->name)

@section('actions')
    <div class="flex gap-2 text-sm">
        <a href="{{ route('reports.index', ['location_id' => $location->id]) }}" class="border border-slate-300 rounded-md px-3 py-2">Full report</a>
        @if (auth()->user()->isFranchisor())
            <a href="{{ route('locations.edit', $location) }}" class="bg-indigo-600 text-white rounded-md px-3 py-2">Edit</a>
            <form method="POST" action="{{ route('locations.destroy', $location) }}" onsubmit="return confirm('Delete this location and all its data?')">
                @csrf @method('DELETE')
                <button class="border border-red-300 text-red-600 rounded-md px-3 py-2">Delete</button>
            </form>
        @endif
    </div>
@endsection

@section('content')
@php $t = $financials['totals']; $money = fn ($v) => '$' . number_format((float) $v, 0); @endphp

<div class="grid md:grid-cols-3 gap-6 mb-6">
    <section class="bg-white rounded-xl border border-slate-200 p-5 text-sm space-y-1">
        <h2 class="font-semibold mb-2">Details</h2>
        <div><span class="text-slate-500">Code:</span> {{ $location->code }}</div>
        <div><span class="text-slate-500">Status:</span> <x-badge :status="$location->status" /></div>
        <div><span class="text-slate-500">Region:</span> {{ $location->region }}</div>
        <div><span class="text-slate-500">Address:</span> {{ $location->address }}, {{ $location->city }}</div>
        <div><span class="text-slate-500">Manager:</span> {{ $location->manager_name ?: '—' }}</div>
        <div><span class="text-slate-500">Opened:</span> {{ $location->opened_on?->format('M j, Y') ?? '—' }}</div>
        <div class="pt-2 border-t border-slate-100 mt-2"><span class="text-slate-500">Fees:</span> {{ $location->royalty_rate }}% royalty · {{ $location->marketing_fee_rate }}% marketing · ${{ number_format($location->tech_fee, 2) }}/mo tech</div>
    </section>

    <section class="md:col-span-2 grid grid-cols-2 gap-4 content-start">
        @foreach ([['Sales (12 mo)', $money($t['gross_sales'])], ['Net profit', $money($t['net_profit'])], ['Fees paid', $money($t['fees_paid'])], ['Fees outstanding', $money($t['fees_outstanding'])]] as [$l, $v])
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="text-xs uppercase text-slate-500">{{ $l }}</div>
                <div class="text-2xl font-semibold mt-1">{{ $v }}</div>
            </div>
        @endforeach
    </section>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <section class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold mb-3">Recent royalty statements</h2>
        <table class="w-full text-sm">
            @forelse ($statements as $s)
                <tr class="border-t border-slate-100">
                    <td class="py-2">{{ $s->period->format('M Y') }}</td>
                    <td class="py-2 text-right">${{ number_format($s->total_due, 2) }}</td>
                    <td class="py-2 text-right"><x-badge :status="$s->status" /></td>
                </tr>
            @empty <tr><td class="py-2 text-slate-500">No statements.</td></tr> @endforelse
        </table>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold mb-3">Compliance</h2>
        <table class="w-full text-sm">
            @forelse ($checks as $c)
                <tr class="border-t border-slate-100">
                    <td class="py-2">{{ $c->item->title }}</td>
                    <td class="py-2 text-slate-500">{{ $c->due_date->format('M j') }}</td>
                    <td class="py-2 text-right"><x-badge :status="$c->status" /></td>
                </tr>
            @empty <tr><td class="py-2 text-slate-500">No checks assigned.</td></tr> @endforelse
        </table>
    </section>
</div>
@endsection
