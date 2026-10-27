@extends('layouts.app')
@section('title', 'Locations')
@section('heading', 'Locations')

@section('actions')
    @if (auth()->user()->isFranchisor())
        <a href="{{ route('locations.create') }}" class="bg-indigo-600 text-white rounded-md px-4 py-2 text-sm">Add location</a>
    @endif
@endsection

@section('content')
<form method="GET" class="flex flex-wrap gap-2 mb-4 text-sm">
    <input name="q" value="{{ request('q') }}" placeholder="Search name, code, city" class="rounded-md border border-slate-300 px-3 py-2">
    <select name="region" class="rounded-md border border-slate-300 px-3 py-2">
        <option value="">All regions</option>
        @foreach ($regions as $r)<option value="{{ $r }}" @selected(request('region') === $r)>{{ $r }}</option>@endforeach
    </select>
    <button class="bg-slate-800 text-white rounded-md px-4 py-2">Filter</button>
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 bg-slate-50">
            <tr><th class="px-4 py-2">Code</th><th class="px-4 py-2">Name</th><th class="px-4 py-2">Region</th><th class="px-4 py-2">City</th><th class="px-4 py-2">Manager</th><th class="px-4 py-2">Royalty</th><th class="px-4 py-2">Status</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($locations as $l)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-2 font-mono text-xs">{{ $l->code }}</td>
                    <td class="px-4 py-2"><a class="text-indigo-600 hover:underline" href="{{ route('locations.show', $l) }}">{{ $l->name }}</a></td>
                    <td class="px-4 py-2">{{ $l->region }}</td>
                    <td class="px-4 py-2">{{ $l->city }}</td>
                    <td class="px-4 py-2">{{ $l->manager_name }}</td>
                    <td class="px-4 py-2">{{ $l->royalty_rate }}%</td>
                    <td class="px-4 py-2"><x-badge :status="$l->status" /></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No locations found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $locations->links() }}</div>
@endsection
