@extends('layouts.app')
@section('title', 'Messages')
@section('heading', 'Communication Portal')

@section('content')
@php $user = auth()->user(); @endphp

<div class="grid lg:grid-cols-3 gap-6">
    <section class="lg:col-span-2 bg-white rounded-xl border border-slate-200 divide-y divide-slate-100">
        @forelse ($threads as $t)
            <a href="{{ route('messages.show', $t) }}" class="block p-4 hover:bg-slate-50">
                <div class="flex items-center justify-between gap-2">
                    <div class="font-medium">
                        @if ($t->is_announcement)<span class="text-xs bg-indigo-100 text-indigo-700 rounded px-1.5 py-0.5 mr-1">Announcement</span>@endif
                        {{ $t->subject }}
                    </div>
                    <div class="text-xs text-slate-400">{{ $t->created_at->diffForHumans() }}</div>
                </div>
                <div class="text-sm text-slate-600 mt-1 line-clamp-2">{{ \Illuminate\Support\Str::limit($t->body, 140) }}</div>
                <div class="text-xs text-slate-400 mt-1">{{ $t->sender->name }} · {{ $t->location?->name ?? 'All locations' }} · {{ $t->replies_count }} {{ \Illuminate\Support\Str::plural('reply', $t->replies_count) }}</div>
            </a>
        @empty
            <p class="p-6 text-center text-slate-500 text-sm">No messages yet.</p>
        @endforelse
        <div class="p-4">{{ $threads->links() }}</div>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 p-5 text-sm h-fit">
        <h2 class="font-semibold mb-3">{{ $user->isFranchisee() ? 'Contact head office' : 'New message' }}</h2>
        <form method="POST" action="{{ route('messages.store') }}" class="space-y-3">
            @csrf
            @unless ($user->isFranchisee())
                <label class="block">To
                    <select name="location_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                        <option value="">All locations (announcement)</option>
                        @foreach ($locations as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                    </select>
                </label>
            @endunless
            <label class="block">Subject <input name="subject" required value="{{ old('subject') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
            <label class="block">Message <textarea name="body" rows="5" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">{{ old('body') }}</textarea></label>
            <button class="w-full bg-indigo-600 text-white rounded-md py-2">Send</button>
        </form>
    </section>
</div>
@endsection
