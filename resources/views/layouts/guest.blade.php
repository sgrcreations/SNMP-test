<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'SGR SNMP Test Kit') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="relative min-h-screen overflow-hidden bg-[#f4f7fb]">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(8,145,178,0.12),_transparent_40%),radial-gradient(circle_at_bottom_right,_rgba(14,165,233,0.10),_transparent_35%)]"></div>

            <div class="relative mx-auto flex min-h-screen max-w-6xl flex-col justify-center px-4 py-10 sm:px-6 lg:px-8">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8 text-center">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 to-cyan-700 text-sm font-bold text-white shadow-lg shadow-cyan-200">
                            SGR
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900">SGR SNMP Test Kit</h1>
                        <p class="mt-2 text-sm text-slate-500">Sign in to manage lab SNMP endpoints</p>
                    </div>

                    <div class="sgr-card px-6 py-7">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
