@extends('layouts.app')
@php $editing = $location->exists; @endphp
@section('title', $editing ? 'Edit location' : 'New location')
@section('heading', $editing ? 'Edit ' . $location->name : 'New location')

@section('content')
<form method="POST" action="{{ $editing ? route('locations.update', $location) : route('locations.store') }}"
      class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl grid md:grid-cols-2 gap-4 text-sm">
    @csrf
    @if ($editing) @method('PUT') @endif

    @foreach ([['name', 'Name', 'text'], ['code', 'Code (e.g. FR-010)', 'text'], ['region', 'Region', 'text'], ['city', 'City', 'text'], ['address', 'Address', 'text'], ['manager_name', 'Manager name', 'text'], ['opened_on', 'Opened on', 'date'], ['royalty_rate', 'Royalty rate (%)', 'number'], ['marketing_fee_rate', 'Marketing fee (%)', 'number'], ['tech_fee', 'Tech fee ($/month)', 'number']] as [$field, $label, $type])
        <label class="block">{{ $label }}
            <input type="{{ $type }}" name="{{ $field }}" step="{{ $type === 'number' ? '0.01' : '' }}"
                   value="{{ old($field, $field === 'opened_on' ? $location->opened_on?->toDateString() : $location->$field) }}"
                   class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
        </label>
    @endforeach

    <label class="block">Status
        <select name="status" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
            @foreach (['active', 'onboarding', 'suspended', 'closed'] as $s)
                <option value="{{ $s }}" @selected(old('status', $location->status) === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </label>

    <div class="md:col-span-2 flex gap-2 pt-2">
        <button class="bg-indigo-600 text-white rounded-md px-5 py-2">Save</button>
        <a href="{{ route('locations.index') }}" class="border border-slate-300 rounded-md px-5 py-2">Cancel</a>
    </div>
</form>
@endsection
