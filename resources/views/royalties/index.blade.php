@extends('layouts.app')
@section('title', 'Royalties & Fees')
@section('heading', 'Royalties & Fees')

@section('content')
@php
    $user = auth()->user();
    $rates = $locations->mapWithKeys(fn ($l) => [$l->id => [
        'royalty' => (float) $l->royalty_rate, 'marketing' => (float) $l->marketing_fee_rate, 'tech' => (float) $l->tech_fee,
    ]]);
@endphp

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    {{-- Live fee calculator (Vue) --}}
    <section id="calc" class="bg-white rounded-xl border border-slate-200 p-5 text-sm">
        <h2 class="font-semibold mb-3">Fee calculator</h2>
        <div class="grid grid-cols-2 gap-3">
            <label>Location
                <select v-model="locationId" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    @foreach ($locations as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                </select>
            </label>
            <label>Gross sales ($)
                <input type="number" min="0" step="0.01" v-model.number="gross" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
            </label>
        </div>
        <dl class="mt-4 space-y-1">
            <div class="flex justify-between"><dt>Royalty (@{{ rate.royalty }}%)</dt><dd>$@{{ fmt(royalty) }}</dd></div>
            <div class="flex justify-between"><dt>Marketing fee (@{{ rate.marketing }}%)</dt><dd>$@{{ fmt(marketing) }}</dd></div>
            <div class="flex justify-between"><dt>Technology fee</dt><dd>$@{{ fmt(rate.tech) }}</dd></div>
            <div class="flex justify-between border-t border-slate-200 pt-1 font-semibold"><dt>Total due</dt><dd>$@{{ fmt(total) }}</dd></div>
        </dl>
    </section>

    {{-- Monthly sales entry → generates statement --}}
    <section class="bg-white rounded-xl border border-slate-200 p-5 text-sm">
        <h2 class="font-semibold mb-3">Submit monthly sales</h2>
        <form method="POST" action="{{ route('royalties.sales') }}" class="grid grid-cols-2 gap-3">
            @csrf
            <label>Location
                <select name="location_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    @foreach ($locations as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                </select>
            </label>
            <label>Month <input type="month" name="period" value="{{ now()->subMonth()->format('Y-m') }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
            <label>Gross sales <input type="number" step="0.01" min="0" name="gross_sales" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
            <label>Cost of goods <input type="number" step="0.01" min="0" name="cost_of_goods" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
            <label>Labor cost <input type="number" step="0.01" min="0" name="labor_cost" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
            <label>Other expenses <input type="number" step="0.01" min="0" name="other_expenses" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
            <button class="col-span-2 bg-indigo-600 text-white rounded-md py-2">Save & generate statement</button>
        </form>
    </section>
</div>

<form method="GET" class="flex flex-wrap gap-2 mb-4 text-sm">
    <select name="location_id" class="rounded-md border border-slate-300 px-3 py-2">
        <option value="">All locations</option>
        @foreach ($locations as $l)<option value="{{ $l->id }}" @selected(request('location_id') == $l->id)>{{ $l->name }}</option>@endforeach
    </select>
    <select name="status" class="rounded-md border border-slate-300 px-3 py-2">
        <option value="">Any status</option>
        @foreach (['pending', 'paid', 'overdue'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
    </select>
    <button class="bg-slate-800 text-white rounded-md px-4 py-2">Filter</button>
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 bg-slate-50">
            <tr><th class="px-4 py-2">Period</th><th class="px-4 py-2">Location</th><th class="px-4 py-2 text-right">Sales</th><th class="px-4 py-2 text-right">Royalty</th><th class="px-4 py-2 text-right">Marketing</th><th class="px-4 py-2 text-right">Tech</th><th class="px-4 py-2 text-right">Total due</th><th class="px-4 py-2">Due</th><th class="px-4 py-2">Status</th><th></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($statements as $s)
                <tr>
                    <td class="px-4 py-2">{{ $s->period->format('M Y') }}</td>
                    <td class="px-4 py-2">{{ $s->location->name }}</td>
                    <td class="px-4 py-2 text-right">${{ number_format($s->gross_sales, 2) }}</td>
                    <td class="px-4 py-2 text-right">${{ number_format($s->royalty_amount, 2) }}</td>
                    <td class="px-4 py-2 text-right">${{ number_format($s->marketing_fee, 2) }}</td>
                    <td class="px-4 py-2 text-right">${{ number_format($s->tech_fee, 2) }}</td>
                    <td class="px-4 py-2 text-right font-medium">${{ number_format($s->total_due, 2) }}</td>
                    <td class="px-4 py-2">{{ $s->due_date->format('M j') }}</td>
                    <td class="px-4 py-2"><x-badge :status="$s->status" /></td>
                    <td class="px-4 py-2 text-right">
                        @if ($user->isNetworkStaff() && $s->status !== 'paid')
                            <form method="POST" action="{{ route('royalties.paid', $s) }}">@csrf<button class="text-indigo-600 hover:underline">Mark paid</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="px-4 py-6 text-center text-slate-500">No statements yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $statements->links() }}</div>
@endsection

@push('scripts')
<script>
    const RATES = @json($rates);
    Vue.createApp({
        data: () => ({ locationId: Object.keys(RATES)[0] ?? null, gross: 0 }),
        computed: {
            rate() { return RATES[this.locationId] ?? { royalty: 0, marketing: 0, tech: 0 }; },
            royalty() { return (this.gross || 0) * this.rate.royalty / 100; },
            marketing() { return (this.gross || 0) * this.rate.marketing / 100; },
            total() { return this.royalty + this.marketing + this.rate.tech; },
        },
        methods: { fmt: (n) => Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
    }).mount('#calc');
</script>
@endpush
