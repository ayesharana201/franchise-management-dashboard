@extends('layouts.app')
@section('title', 'Compliance')
@section('heading', 'Compliance Checklist')

@section('content')
@php $staff = auth()->user()->isNetworkStaff(); @endphp

@if ($staff)
<div class="grid lg:grid-cols-2 gap-6 mb-6 text-sm">
    <section class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold mb-3">New checklist item</h2>
        <form method="POST" action="{{ route('compliance.items.store') }}" class="grid grid-cols-2 gap-3">
            @csrf
            <label class="col-span-2">Title <input name="title" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
            <label>Category
                <select name="category" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    @foreach (['safety', 'brand', 'financial', 'operations'] as $c)<option value="{{ $c }}">{{ ucfirst($c) }}</option>@endforeach
                </select>
            </label>
            <label>Frequency
                <select name="frequency" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    @foreach (['weekly', 'monthly', 'quarterly', 'annual'] as $f)<option value="{{ $f }}">{{ ucfirst($f) }}</option>@endforeach
                </select>
            </label>
            <button class="col-span-2 bg-indigo-600 text-white rounded-md py-2">Create item</button>
        </form>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold mb-3">Assign to locations</h2>
        <form method="POST" action="{{ route('compliance.assign') }}" class="grid grid-cols-2 gap-3">
            @csrf
            <label class="col-span-2">Item
                <select name="compliance_item_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    @foreach ($items as $i)<option value="{{ $i->id }}">{{ $i->title }}</option>@endforeach
                </select>
            </label>
            <label>Location
                <select name="location_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    <option value="">All my locations</option>
                    @foreach ($locations as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                </select>
            </label>
            <label>Due date <input type="date" name="due_date" value="{{ now()->addWeeks(2)->toDateString() }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
            <button class="col-span-2 bg-slate-800 text-white rounded-md py-2">Assign</button>
        </form>
    </section>
</div>
@endif

<form method="GET" class="flex flex-wrap gap-2 mb-4 text-sm">
    <select name="location_id" class="rounded-md border border-slate-300 px-3 py-2">
        <option value="">All locations</option>
        @foreach ($locations as $l)<option value="{{ $l->id }}" @selected(request('location_id') == $l->id)>{{ $l->name }}</option>@endforeach
    </select>
    <select name="status" class="rounded-md border border-slate-300 px-3 py-2">
        <option value="">Any status</option>
        @foreach (['pending', 'passed', 'failed'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
    </select>
    <button class="bg-slate-800 text-white rounded-md px-4 py-2">Filter</button>
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 bg-slate-50">
            <tr><th class="px-4 py-2">Location</th><th class="px-4 py-2">Item</th><th class="px-4 py-2">Category</th><th class="px-4 py-2">Due</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Update</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($checks as $c)
                <tr>
                    <td class="px-4 py-2">{{ $c->location->name }}</td>
                    <td class="px-4 py-2">{{ $c->item->title }}@if ($c->notes)<div class="text-xs text-slate-500">{{ $c->notes }}</div>@endif</td>
                    <td class="px-4 py-2">{{ ucfirst($c->item->category) }}</td>
                    <td class="px-4 py-2 {{ $c->isOverdue() ? 'text-red-600 font-medium' : '' }}">{{ $c->due_date->format('M j, Y') }}</td>
                    <td class="px-4 py-2"><x-badge :status="$c->status" /></td>
                    <td class="px-4 py-2">
                        <form method="POST" action="{{ route('compliance.update', $c) }}" class="flex gap-1">
                            @csrf @method('PUT')
                            <select name="status" class="rounded border border-slate-300 px-2 py-1 text-xs">
                                @foreach (['pending', 'passed', 'failed'] as $s)<option value="{{ $s }}" @selected($c->status === $s)>{{ ucfirst($s) }}</option>@endforeach
                            </select>
                            <input name="notes" placeholder="Notes" value="{{ $c->notes }}" class="rounded border border-slate-300 px-2 py-1 text-xs w-28">
                            <button class="text-indigo-600 text-xs hover:underline">Save</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No checks found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $checks->links() }}</div>
@endsection
