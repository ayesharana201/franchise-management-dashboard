@extends('layouts.app')
@section('title', $message->subject)
@section('heading', $message->subject)

@section('actions')
    <a href="{{ route('messages.index') }}" class="text-sm text-indigo-600 hover:underline">← Back to messages</a>
@endsection

@section('content')
<div class="max-w-3xl space-y-4">
    @foreach ([$message, ...$message->replies] as $m)
        <article class="bg-white rounded-xl border border-slate-200 p-5 {{ $m->sender_id === auth()->id() ? 'border-indigo-200' : '' }}">
            <div class="flex justify-between text-xs text-slate-500 mb-2">
                <span class="font-medium text-slate-700">{{ $m->sender->name }} <span class="font-normal">({{ $m->sender->roleLabel() }})</span></span>
                <span>{{ $m->created_at->format('M j, Y g:i A') }}</span>
            </div>
            <p class="text-sm whitespace-pre-line">{{ $m->body }}</p>
        </article>
    @endforeach

    <form method="POST" action="{{ route('messages.reply', $message) }}" class="bg-white rounded-xl border border-slate-200 p-5 space-y-3 text-sm">
        @csrf
        <label class="block">Reply <textarea name="body" rows="4" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></textarea></label>
        <button class="bg-indigo-600 text-white rounded-md px-5 py-2">Post reply</button>
    </form>
</div>
@endsection
