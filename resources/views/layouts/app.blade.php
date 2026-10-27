<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · Franchise Manager</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        {{-- Fallback so the app renders before `npm run build` has been run. --}}
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
@php
    $u = auth()->user();
    $nav = [
        ['dashboard', 'Dashboard', 'dashboard'],
        ['locations.index', 'Locations', 'locations*'],
        ['royalties.index', 'Royalties & Fees', 'royalties*'],
        ['compliance.index', 'Compliance', 'compliance*'],
        ['messages.index', 'Messages', 'messages*'],
        ['inventory.index', 'Inventory', 'inventory*'],
        ['orders.index', 'Supply Orders', 'orders*'],
        ['documents.index', 'Documents', 'documents*'],
        ['reports.index', 'Financial Reports', 'reports*'],
    ];
    if ($u?->isFranchisor()) {
        $nav[] = ['users.index', 'Users & Roles', 'users*'];
    }
@endphp

<div class="min-h-screen md:flex">
    <aside class="bg-slate-900 text-slate-200 md:w-64 md:min-h-screen shrink-0">
        <div class="px-5 py-5 border-b border-slate-800">
            <div class="text-lg font-semibold text-white">Franchise Manager</div>
            <div class="text-xs text-slate-400 mt-0.5">{{ $u?->roleLabel() }}@if($u?->location) · {{ $u->location->name }}@endif</div>
        </div>
        <nav class="p-3 flex md:block gap-1 overflow-x-auto">
            @foreach ($nav as [$route, $label, $pattern])
                <a href="{{ route($route) }}"
                   class="block whitespace-nowrap px-3 py-2 rounded-md text-sm {{ request()->is($pattern) ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="p-3 md:mt-6">
            @csrf
            <button class="w-full text-left px-3 py-2 rounded-md text-sm text-slate-400 hover:bg-slate-800 hover:text-white">
                Sign out ({{ $u?->name }})
            </button>
        </form>
    </aside>

    <main class="flex-1 p-4 md:p-8 min-w-0">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold">@yield('heading', 'Dashboard')</h1>
            <div>@yield('actions')</div>
        </header>

        @if (session('status'))
            <div class="mb-4 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
