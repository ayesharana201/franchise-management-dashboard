@extends('layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Network Performance')

@section('actions')
    <form method="GET" class="flex flex-wrap items-end gap-2 text-sm">
        <label>From <input type="date" name="from" value="{{ $from }}" class="rounded-md border border-slate-300 px-2 py-1"></label>
        <label>To <input type="date" name="to" value="{{ $to }}" class="rounded-md border border-slate-300 px-2 py-1"></label>
        <button class="bg-indigo-600 text-white rounded-md px-3 py-1.5">Apply</button>
    </form>
@endsection

@section('content')
@php $money = fn ($v) => '$' . number_format((float) $v, 0); @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    @foreach ([
        ['Locations', $stats['location_count'], 'text-sky-400', 'border-t-sky-400'],
        ['Total sales', $money($stats['total_sales']), 'text-blue-400', 'border-t-blue-400'],
        ['Net profit', $money($stats['net_profit']), $stats['net_profit'] < 0 ? 'text-rose-400' : 'text-emerald-400', $stats['net_profit'] < 0 ? 'border-t-rose-400' : 'border-t-emerald-400'],
        ['Royalties outstanding', $money($stats['royalties_outstanding']), $stats['overdue_statements'] ? 'text-rose-400' : 'text-violet-400', $stats['overdue_statements'] ? 'border-t-rose-400' : 'border-t-violet-400'],
        ['Overdue statements', $stats['overdue_statements'], $stats['overdue_statements'] ? 'text-rose-400' : 'text-sky-400', $stats['overdue_statements'] ? 'border-t-rose-400' : 'border-t-sky-400'],
        ['Compliance rate', $stats['compliance_rate'] !== null ? $stats['compliance_rate'] . '%' : '—', 'text-emerald-400', 'border-t-emerald-400'],
        ['Failed checks', $stats['failed_checks'], $stats['failed_checks'] ? 'text-rose-400' : 'text-emerald-400', $stats['failed_checks'] ? 'border-t-rose-400' : 'border-t-emerald-400'],
    ] as [$label, $value, $color, $accent])
        <div class="bg-white rounded-xl border border-slate-200 border-t-2 {{ $accent }} p-5">
            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ $label }}</div>
            <div class="mt-3 text-3xl font-bold tracking-tight {{ $color }}">{{ $value }}</div>
        </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 items-start gap-6 mb-6">
     <section class="dashboard-sales-panel bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="font-semibold text-gray-900">Monthly sales</h2>
                <p class="text-xs text-slate-500 mt-1">Gross sales by month</p>
            </div>
            <span class="inline-flex items-center gap-2 text-xs text-slate-500">
                <span class="inline-block w-2 h-2 rounded-full bg-sky-400"></span>
                Sales
            </span>
        </div>
        @php $max = max(1, $stats['monthly_sales']->max('gross_sales')); @endphp
        <div class="dashboard-sales-chart flex items-end gap-3 pt-3">
            @forelse ($stats['monthly_sales'] as $m)
                <div class="flex-1 min-w-0 flex flex-col items-center justify-end h-full group">
                    <div class="text-xs text-slate-500 mb-2 text-center truncate max-w-full" title="{{ $money($m['gross_sales']) }}">{{ $money($m['gross_sales']) }}</div>
                    <div class="dashboard-sales-bar w-full rounded-t-md bg-gradient-to-t from-blue-600 to-sky-400 transition-all duration-200 group-hover:from-violet-600 group-hover:to-violet-400" style="height: {{ max(2, round($m['gross_sales'] / $max * 100)) }}%; min-height: 4px"></div>
                    <div class="text-xs text-slate-500 mt-2 text-center">{{ \Carbon\Carbon::parse($m['period'] . '-01')->format('M y') }}</div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No sales recorded for this period.</p>
            @endforelse
        </div>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-5">
            <h2 class="font-semibold text-gray-900">Needs attention</h2>
            <span class="text-xs text-slate-500">Action items</span>
        </div>

        <div class="mb-5">
            <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">
                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                Overdue compliance
            </h3>
            <ul class="text-sm space-y-2">
                @forelse ($overdueChecks as $c)
                    <li class="rounded-lg border border-rose-500/20 bg-rose-500/5 p-3">
                        <a class="font-medium text-rose-300 hover:text-rose-200 hover:underline" href="{{ route('compliance.index', ['location_id' => $c->location_id]) }}">{{ $c->location->name }}</a>
                        <div class="mt-1 text-slate-400">{{ $c->item->title }}</div>
                        <div class="mt-2 text-xs font-medium text-rose-300">{{ $c->due_date->diffForHumans() }}</div>
                    </li>
                @empty
                    <li class="text-sm text-emerald-400">All clear.</li>
                @endforelse
            </ul>
        </div>

        <div class="mb-5">
            <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">
                <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                Orders awaiting approval
            </h3>
            <ul class="text-sm space-y-2">
                @forelse ($pendingOrders as $o)
                    <li class="rounded-lg border border-sky-500/20 bg-sky-500/5 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <a class="font-medium text-sky-300 hover:text-sky-200 hover:underline" href="{{ route('orders.show', $o) }}">Order #{{ $o->id }}</a>
                            <span class="text-sm font-semibold text-slate-200">${{ number_format($o->total, 2) }}</span>
                        </div>
                        <div class="mt-1 text-slate-400">{{ $o->location->name }}</div>
                    </li>
                @empty
                    <li class="text-sm text-emerald-400">No orders awaiting approval.</li>
                @endforelse
            </ul>
        </div>

        <div>
            <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                Documents expiring in 60 days
            </h3>
            <ul class="text-sm space-y-2">
                @forelse ($expiringDocs as $d)
                    <li class="rounded-lg border border-amber-500/20 bg-amber-500/5 p-3">
                        <div class="font-medium text-slate-200">{{ $d->title }}</div>
                        <div class="mt-1 text-xs text-amber-300">Expires {{ $d->expires_on->format('M j, Y') }}</div>
                    </li>
                @empty
                    <li class="text-sm text-emerald-400">No documents expiring soon.</li>
                @endforelse
            </ul>
        </div>
    </section>
</div>

<section class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="font-semibold text-gray-900">Recent Activity</h2>
            <p class="text-xs text-slate-500 mt-1">Latest supply orders and document uploads</p>
        </div>
        <span class="text-xs text-slate-500">Latest 8</span>
    </div>

    <div class="space-y-1">
        @forelse ($recentActivity as $activity)
            <div class="flex items-start gap-3 rounded-lg p-3 hover:bg-slate-50 transition-colors">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg
                    {{ $activity['type'] === 'order'
                        ? 'bg-sky-100 text-sky-700'
                        : 'bg-violet-100 text-violet-700' }}">
                    @if ($activity['type'] === 'order')
                        <span class="text-xs font-bold">SO</span>
                    @else
                        <span class="text-xs font-bold">DOC</span>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <a href="{{ $activity['url'] }}"
                       class="text-sm font-medium text-indigo-600 hover:underline">
                        {{ $activity['title'] }}
                    </a>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $activity['description'] }}
                    </p>
                </div>

                <time class="shrink-0 text-xs text-slate-500"
                      datetime="{{ $activity['created_at']?->toIso8601String() }}">
                    {{ $activity['created_at']?->diffForHumans() ?? 'Date unavailable' }}
                </time>
            </div>
        @empty
            <p class="py-6 text-center text-sm text-slate-500">
                No recent activity yet.
            </p>
        @endforelse
    </div>
</section>

<section class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <h2 class="font-semibold p-5 pb-3">Location leaderboard</h2>
    <table class="w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 bg-slate-50">
            <tr><th class="px-5 py-2">#</th><th class="px-5 py-2">Location</th><th class="px-5 py-2">Region</th><th class="px-5 py-2 text-right">Sales</th><th class="px-5 py-2 text-right">Net profit</th><th class="px-5 py-2 text-right">Rating</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($stats['leaderboard'] as $i => $row)
                <tr>
                    <td class="px-5 py-2 text-slate-400">{{ $i + 1 }}</td>
                    <td class="px-5 py-2"><a class="text-indigo-600 hover:underline" href="{{ route('locations.show', $row['location_id']) }}">{{ $row['name'] }}</a></td>
                    <td class="px-5 py-2">{{ $row['region'] }}</td>
                    <td class="px-5 py-2 text-right">{{ $money($row['gross_sales']) }}</td>
                    <td class="px-5 py-2 text-right {{ $row['net_profit'] < 0 ? 'text-red-600' : '' }}">{{ $money($row['net_profit']) }}</td>
                    <td class="px-5 py-2 text-right">{{ $row['avg_rating'] ?: 'â€”' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-6 text-center text-slate-500">No data yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection





