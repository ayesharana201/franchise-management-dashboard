@extends('layouts.app')
@section('title', 'Documents')
@section('heading', 'Documents & Contracts')

@section('content')
@php $user = auth()->user(); @endphp

<div class="grid lg:grid-cols-3 gap-6">
    <section class="lg:col-span-2">
        <form method="GET" class="flex gap-2 mb-4 text-sm">
            <select name="type" class="rounded-md border border-slate-300 px-3 py-2">
                <option value="">All types</option>
                @foreach (\App\Models\Document::TYPES as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst($t) }}</option>@endforeach
            </select>
            <button class="bg-slate-800 text-white rounded-md px-4 py-2">Filter</button>
        </form>

        <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase text-slate-500 bg-slate-50">
                    <tr><th class="px-4 py-2">Title</th><th class="px-4 py-2">Type</th><th class="px-4 py-2">Location</th><th class="px-4 py-2">Expires</th><th></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($documents as $d)
                        <tr>
                            <td class="px-4 py-2">{{ $d->title }}<div class="text-xs text-slate-400">{{ $d->uploader->name }} · {{ $d->created_at->format('M j, Y') }}</div></td>
                            <td class="px-4 py-2">{{ ucfirst($d->type) }}</td>
                            <td class="px-4 py-2">{{ $d->location?->name ?? 'Network-wide' }}</td>
                            <td class="px-4 py-2 {{ $d->isExpiringSoon() ? 'text-amber-600 font-medium' : '' }}">{{ $d->expires_on?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <a href="{{ route('documents.download', $d) }}" class="text-indigo-600 hover:underline">Download</a>
                                @if ($user->isNetworkStaff() || $d->uploaded_by === $user->id)
                                    <form method="POST" action="{{ route('documents.destroy', $d) }}" class="inline" onsubmit="return confirm('Delete this document?')">
                                        @csrf @method('DELETE')<button class="text-red-600 hover:underline ml-2">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No documents.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $documents->links() }}</div>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 p-5 text-sm h-fit">
        <h2 class="font-semibold mb-3">Upload document</h2>
        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <label class="block">Title <input name="title" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
            <label class="block">Type
                <select name="type" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    @foreach (\App\Models\Document::TYPES as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach
                </select>
            </label>
            @unless ($user->isFranchisee())
                <label class="block">Location
                    <select name="location_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                        <option value="">Network-wide</option>
                        @foreach ($locations as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                    </select>
                </label>
            @endunless
            <label class="block">Expiry date <input type="date" name="expires_on" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
            <label class="block">File (PDF, Word, image · max 10 MB) <input type="file" name="file" required class="mt-1 w-full"></label>
            <button class="w-full bg-indigo-600 text-white rounded-md py-2">Upload</button>
        </form>
    </section>
</div>
@endsection
