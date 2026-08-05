<aside
    class="fixed inset-y-0 left-0 z-40 flex w-[280px] flex-col border-r border-slate-200/80 bg-white shadow-[1px_0_0_rgba(15,23,42,0.03)] transition-transform duration-200 ease-out lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
>
    {{-- Brand --}}
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-100 px-5">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-500 to-cyan-700 text-xs font-bold text-white shadow-sm shadow-cyan-200">
            SGR
        </div>
        <div class="min-w-0">
            <div class="truncate text-[13px] font-bold leading-tight tracking-tight text-slate-900">SGR SNMP Test Kit</div>
            <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Lab Platform</div>
        </div>
        <button type="button" class="sgr-btn-icon ms-auto lg:hidden" @click="sidebarOpen = false" title="Close menu">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Main</div>

        <a href="{{ route('dashboard') }}" @click="sidebarOpen = false" class="sgr-nav-link {{ request()->routeIs('dashboard') ? 'sgr-nav-link-active' : '' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>
            <span>Dashboard</span>
        </a>

        @can('devices.view')
            <a href="{{ route('devices.index') }}" @click="sidebarOpen = false" class="sgr-nav-link {{ request()->routeIs('devices.*') ? 'sgr-nav-link-active' : '' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 5h14a1 1 0 011 1v4H4V6a1 1 0 011-1zm-1 7h16v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/></svg>
                <span>Devices</span>
            </a>
            <a href="{{ route('interfaces.index') }}" @click="sidebarOpen = false" class="sgr-nav-link {{ request()->routeIs('interfaces.*') ? 'sgr-nav-link-active' : '' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h10M4 18h16"/></svg>
                <span>Interfaces</span>
            </a>
        @endcan

        @can('settings.view')
            <div class="px-3 pb-2 pt-5 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">System</div>
            <a href="{{ route('settings.edit') }}" @click="sidebarOpen = false" class="sgr-nav-link {{ request()->routeIs('settings.*') ? 'sgr-nav-link-active' : '' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317a1 1 0 011.35-.936l.094.04a1 1 0 00.74 0l.094-.04a1 1 0 011.35.936l.04.094a1 1 0 00.632.632l.094.04a1 1 0 01.65 1.212l-.04.1a1 1 0 000 .74l.04.094a1 1 0 01-.65 1.212l-.094.04a1 1 0 00-.632.632l-.04.094a1 1 0 01-1.35.936l-.094-.04a1 1 0 00-.74 0l-.094.04a1 1 0 01-1.35-.936l-.04-.094a1 1 0 00-.632-.632l-.094-.04a1 1 0 01-.65-1.212l.04-.094a1 1 0 000-.74l-.04-.1a1 1 0 01.65-1.212l.094-.04a1 1 0 00.632-.632l.04-.094z"/><circle cx="12" cy="12" r="3" stroke-width="1.8"/></svg>
                <span>Settings</span>
            </a>
        @endcan

        <a href="{{ route('profile.edit') }}" @click="sidebarOpen = false" class="sgr-nav-link {{ request()->routeIs('profile.*') ? 'sgr-nav-link-active' : '' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 21a8 8 0 1116 0"/></svg>
            <span>Profile</span>
        </a>
    </nav>

    {{-- Footer --}}
    <div class="shrink-0 space-y-3 border-t border-slate-100 p-4">
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-3 py-2.5">
            <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.12em] text-emerald-700">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                </span>
                System Alive
            </div>
            <p class="mt-1 text-[11px] text-emerald-700/80">Monitoring services ready</p>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-3">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cyan-600 text-sm font-bold text-white">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</div>
                    <div class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                    Sign out
                </button>
            </form>
        </div>
    </div>
</aside>
