@props(['status'])
@php
    $map = [
        'paid' => 'bg-emerald-100 text-emerald-800', 'passed' => 'bg-emerald-100 text-emerald-800',
        'active' => 'bg-emerald-100 text-emerald-800', 'delivered' => 'bg-emerald-100 text-emerald-800',
        'pending' => 'bg-amber-100 text-amber-800', 'submitted' => 'bg-amber-100 text-amber-800',
        'onboarding' => 'bg-sky-100 text-sky-800', 'approved' => 'bg-sky-100 text-sky-800', 'shipped' => 'bg-indigo-100 text-indigo-800',
        'overdue' => 'bg-red-100 text-red-800', 'failed' => 'bg-red-100 text-red-800',
        'suspended' => 'bg-red-100 text-red-800', 'cancelled' => 'bg-slate-200 text-slate-700', 'closed' => 'bg-slate-200 text-slate-700',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex px-2 py-0.5 rounded-full text-xs font-medium ' . ($map[$status] ?? 'bg-slate-100 text-slate-700')]) }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
