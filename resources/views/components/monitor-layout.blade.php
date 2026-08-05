@props([
    'title' => 'Dashboard',
    'header' => null,
    'subheader' => null,
    'breadcrumbs' => [],
    'status' => null,
    'meta' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ sidebarOpen: false }" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — {{ config('app.name', 'SGR SNMP Test Kit') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $styles ?? '' }}
</head>
<body class="h-full bg-[#f4f7fb] font-sans antialiased text-slate-900">
    @include('partials.sidebar')

    {{-- Mobile overlay --}}
    <div
        x-show="sidebarOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"
        @click="sidebarOpen = false"
    ></div>

    {{-- Main column: always offset for fixed sidebar on desktop --}}
    <div class="flex min-h-full flex-col lg:pl-[280px]">
        @include('partials.topbar', [
            'header' => $header ?? $title,
            'subheader' => $subheader,
            'breadcrumbs' => $breadcrumbs,
            'status' => $status,
            'meta' => $meta,
            'actions' => $actions ?? null,
        ])

        <main class="flex-1 px-4 py-5 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    {{ $scripts ?? '' }}
    @stack('scripts')
</body>
</html>
