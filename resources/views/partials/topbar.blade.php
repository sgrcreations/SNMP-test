{{-- Classic sticky utility navbar --}}
<header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white">
    <div class="flex h-14 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800 lg:hidden" @click="sidebarOpen = true" title="Open menu">
                <span class="sr-only">Open sidebar</span>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <form method="GET" action="{{ route('devices.index') }}" class="relative hidden w-full max-w-md sm:block">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.3-4.3M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/>
                </svg>
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search devices, IP, hostname..."
                    class="h-9 w-full rounded-xl border-slate-200 bg-slate-50 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-cyan-500 focus:bg-white focus:ring-cyan-500"
                >
            </form>
        </div>

        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
            <a href="{{ route('devices.index') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800 sm:hidden" title="Search devices">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.3-4.3M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/></svg>
            </a>

            <button type="button" onclick="window.location.reload()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800" title="Refresh">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/>
                </svg>
            </button>

            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800" title="Notifications">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1h6z"/>
                    </svg>
                    <span class="absolute right-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-cyan-500"></span>
                </button>
                <div
                    x-show="open"
                    x-cloak
                    @click.outside="open = false"
                    class="absolute right-0 mt-2 w-72 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg shadow-slate-200/60"
                >
                    <div class="border-b border-slate-100 px-4 py-3">
                        <div class="text-sm font-semibold text-slate-900">Notifications</div>
                        <div class="text-xs text-slate-400">System and alert updates</div>
                    </div>
                    <div class="px-4 py-6 text-center text-sm text-slate-400">No new notifications</div>
                </div>
            </div>

            @can('settings.view')
                <a href="{{ route('settings.edit') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800" title="Settings">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317a1 1 0 011.35-.936l.094.04a1 1 0 00.74 0l.094-.04a1 1 0 011.35.936l.04.094a1 1 0 00.632.632l.094.04a1 1 0 01.65 1.212l-.04.1a1 1 0 000 .74l.04.094a1 1 0 01-.65 1.212l-.094.04a1 1 0 00-.632.632l-.04.094a1 1 0 01-1.35.936l-.094-.04a1 1 0 00-.74 0l-.094.04a1 1 0 01-1.35-.936l-.04-.094a1 1 0 00-.632-.632l-.094-.04a1 1 0 01-.65-1.212l.04-.094a1 1 0 000-.74l-.04-.1a1 1 0 01.65-1.212l.094-.04a1 1 0 00.632-.632l.04-.094z"/>
                        <circle cx="12" cy="12" r="3" stroke-width="1.8"/>
                    </svg>
                </a>
            @endcan

            <div class="mx-1 hidden h-6 w-px bg-slate-200 sm:block"></div>

            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white py-1 pl-1 pr-2.5 transition hover:bg-slate-50">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-cyan-600 text-xs font-bold text-white">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </span>
                    <span class="hidden text-left sm:block">
                        <span class="block max-w-[120px] truncate text-xs font-semibold text-slate-800">{{ Auth::user()->name }}</span>
                        <span class="block max-w-[120px] truncate text-[10px] text-slate-400">{{ Auth::user()->getRoleNames()->first() ?? 'User' }}</span>
                    </span>
                    <svg class="hidden h-4 w-4 text-slate-400 sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div
                    x-show="open"
                    x-cloak
                    @click.outside="open = false"
                    class="absolute right-0 mt-2 w-52 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 shadow-lg shadow-slate-200/60"
                >
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">Profile</a>
                    @can('settings.view')
                        <a href="{{ route('settings.edit') }}" class="block px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">Settings</a>
                    @endcan
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full px-4 py-2.5 text-left text-sm text-rose-600 hover:bg-rose-50">Sign out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

{{-- Secondary page context header (scrolls with content, OLT-style) --}}
<section class="border-b border-slate-200/80 bg-[#f4f7fb]">
    <div class="px-4 py-5 sm:px-6 lg:px-8">
        @if(!empty($breadcrumbs))
            <nav class="mb-3 flex flex-wrap items-center gap-1.5 text-[11px] text-slate-400" aria-label="Breadcrumb">
                @foreach($breadcrumbs as $crumb)
                    <span class="inline-flex items-center gap-1.5">
                        @if(! $loop->first)
                            <svg class="h-3 w-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        @endif
                        @if(!empty($crumb['url']) && ! $loop->last)
                            <a href="{{ $crumb['url'] }}" class="font-medium hover:text-cyan-600">{{ $crumb['label'] }}</a>
                        @else
                            <span class="font-semibold text-slate-500">{{ $crumb['label'] }}</span>
                        @endif
                    </span>
                @endforeach
            </nav>
        @endif

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-3">
                @php
                    $backUrl = null;
                    if (! empty($breadcrumbs) && count($breadcrumbs) > 1) {
                        foreach (array_reverse($breadcrumbs) as $crumb) {
                            if (! empty($crumb['url'])) {
                                $backUrl = $crumb['url'];
                                break;
                            }
                        }
                    }
                @endphp

                @if($backUrl)
                    <a href="{{ $backUrl }}" class="mt-0.5 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-800" title="Back">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                @endif

                <div class="flex min-w-0 items-start gap-3">
                    <div class="mt-0.5 hidden h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 sm:inline-flex">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h10"/>
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ $header ?? 'Dashboard' }}</h1>
                            @isset($status)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.12em] text-emerald-700 ring-1 ring-inset ring-emerald-100">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    {{ $status }}
                                </span>
                            @endisset
                        </div>

                        @isset($subheader)
                            <p class="mt-1 text-sm text-slate-500">{{ $subheader }}</p>
                        @endisset

                        @isset($meta)
                            <p class="mt-1 text-xs font-medium text-slate-400">{{ $meta }}</p>
                        @endisset
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @isset($actions)
                    {{ $actions }}
                @else
                    <button type="button" onclick="window.location.reload()" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-800" title="Refresh">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/>
                        </svg>
                    </button>
                    @can('devices.create')
                        <a href="{{ route('devices.create') }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-cyan-600" title="Quick add">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </a>
                    @endcan
                    @can('settings.view')
                        <a href="{{ route('settings.edit') }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-800" title="Settings">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317a1 1 0 011.35-.936l.094.04a1 1 0 00.74 0l.094-.04a1 1 0 011.35.936l.04.094a1 1 0 00.632.632l.094.04a1 1 0 01.65 1.212l-.04.1a1 1 0 000 .74l.04.094a1 1 0 01-.65 1.212l-.094.04a1 1 0 00-.632.632l-.04.094a1 1 0 01-1.35.936l-.094-.04a1 1 0 00-.74 0l-.094.04a1 1 0 01-1.35-.936l-.04-.094a1 1 0 00-.632-.632l-.094-.04a1 1 0 01-.65-1.212l.04-.094a1 1 0 000-.74l-.04-.1a1 1 0 01.65-1.212l.094-.04a1 1 0 00.632-.632l.04-.094z"/>
                                <circle cx="12" cy="12" r="3" stroke-width="1.8"/>
                            </svg>
                        </a>
                    @endcan
                @endisset
            </div>
        </div>
    </div>
</section>
