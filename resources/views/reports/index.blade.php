@extends('layouts.app')
@section('title', 'Financial Reports')
@section('heading', 'Financial Report')

@section('actions')
    <a href="{{ route('reports.export', ['location' => $report['location']->id, 'from' => $report['from'], 'to' => $report['to']]) }}"
       class="border border-slate-300 rounded-md px-4 py-2 text-sm">Export CSV</a>
@endsection

@section('content')
@php $t = $report['totals']; $money = fn ($v) => '$' . number_format((float) $v, 2); @endphp

<form method="GET" class="flex flex-wrap items-end gap-2 mb-6 text-sm">
    <label>Location
        <select name="location_id" class="block rounded-md border border-slate-300 px-3 py-2">
            @foreach ($locations as $l)<option value="{{ $l->id }}" @selected($report['location']->id === $l->id)>{{ $l->name }}</option>@endforeach
        </select>
    </label>
    <label>From <input type="date" name="from" value="{{ $report['from'] }}" class="block rounded-md border border-slate-300 px-3 py-2"></label>
    <label>To <input type="date" name="to" value="{{ $report['to'] }}" class="block rounded-md border border-slate-300 px-3 py-2"></label>
    <button class="bg-indigo-600 text-white rounded-md px-4 py-2">Run report</button>
</form>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach ([['Gross sales', $t['gross_sales']], ['Expenses', $t['expenses']], ['Net profit', $t['net_profit']], ['Fees paid', $t['fees_paid']], ['Fees outstanding', $t['fees_outstanding']]] as [$l, $v])
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs uppercase text-slate-500">{{ $l }}</div>
            <div class="text-xl font-semibold mt-1 {{ $l === 'Net profit' && $v < 0 ? 'text-red-600' : '' }}">{{ $money($v) }}</div>
        </div>
    @endforeach
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 bg-slate-50">
            <tr><th class="px-4 py-2">Month</th><th class="px-4 py-2 text-right">Gross sales</th><th class="px-4 py-2 text-right">COGS</th><th class="px-4 py-2 text-right">Labor</th><th class="px-4 py-2 text-right">Other</th><th class="px-4 py-2 text-right">Net profit</th><th class="px-4 py-2 text-right">Transactions</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($report['months'] as $m)
                <tr>
                    <td class="px-4 py-2">{{ \Carbon\Carbon::parse($m['period'] . '-01')->format('M Y') }}</td>
                    <td class="px-4 py-2 text-right">{{ $money($m['gross_sales']) }}</td>
                    <td class="px-4 py-2 text-right">{{ $money($m['cost_of_goods']) }}</td>
                    <td class="px-4 py-2 text-right">{{ $money($m['labor_cost']) }}</td>
                    <td class="px-4 py-2 text-right">{{ $money($m['other_expenses']) }}</td>
                    <td class="px-4 py-2 text-right {{ $m['net_profit'] < 0 ? 'text-red-600' : '' }}">{{ $money($m['net_profit']) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($m['transactions']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No sales data in this range.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
