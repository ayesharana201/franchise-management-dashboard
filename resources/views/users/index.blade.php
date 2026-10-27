@extends('layouts.app')
@section('title', 'Users & Roles')
@section('heading', 'Users & Roles')

@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <section class="lg:col-span-2 bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-slate-500 bg-slate-50">
                <tr><th class="px-4 py-2">Name</th><th class="px-4 py-2">Role</th><th class="px-4 py-2">Scope</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $u)
                    <tr>
                        <td class="px-4 py-2">{{ $u->name }}<div class="text-xs text-slate-400">{{ $u->email }}</div></td>
                        <td class="px-4 py-2">{{ $u->roleLabel() }}</td>
                        <td class="px-4 py-2">{{ $u->location?->name ?? $u->region ?? 'Entire network' }}</td>
                        <td class="px-4 py-2 text-right">
                            @if ($u->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return confirm('Delete this user?')">
                                    @csrf @method('DELETE')<button class="text-red-600 hover:underline">Delete</button>
                                </form>
                            @else <span class="text-xs text-slate-400">You</span> @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $users->links() }}</div>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 p-5 text-sm h-fit">
        <h2 class="font-semibold mb-3">Add user</h2>
        <form method="POST" action="{{ route('users.store') }}" class="space-y-3">
            @csrf
            <label class="block">Name <input name="name" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
            <label class="block">Email <input type="email" name="email" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
            <label class="block">Password <input type="password" name="password" minlength="8" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
            <label class="block">Role
                <select name="role" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    <option value="franchisee">Franchisee (one location)</option>
                    <option value="regional_manager">Regional manager (one region)</option>
                    <option value="franchisor">Franchisor (all access)</option>
                </select>
            </label>
            <label class="block">Location <span class="text-slate-400">(franchisee)</span>
                <select name="location_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    <option value="">—</option>
                    @foreach ($locations as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                </select>
            </label>
            <label class="block">Region <span class="text-slate-400">(regional manager)</span>
                <select name="region" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    <option value="">—</option>
                    @foreach ($regions as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                </select>
            </label>
            <button class="w-full bg-indigo-600 text-white rounded-md py-2">Create user</button>
        </form>
    </section>
</div>
@endsection
