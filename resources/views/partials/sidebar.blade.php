<aside class="fixed inset-y-0 start-0 z-40 w-72 transform border-e border-slate-200 bg-white transition lg:static lg:translate-x-0 dark:border-slate-800 dark:bg-slate-900"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
    <div class="flex h-16 items-center gap-3 border-b border-slate-200 px-6 dark:border-slate-800">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-cyan-600 text-white font-bold">S</div>
        <div>
            <div class="text-sm font-semibold tracking-wide">SNMP Monitor</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">Network Validation Lab</div>
        </div>
    </div>

    <nav class="space-y-1 p-4 text-sm">
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2 {{ request()->routeIs('dashboard') ? 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
            Dashboard
        </a>

        @can('devices.view')
            <a href="{{ route('devices.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 {{ request()->routeIs('devices.*') ? 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                Devices
            </a>
        @endcan

        <div class="px-3 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Coming Soon</div>
        <span class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-400 cursor-not-allowed">OID Explorer</span>
        <span class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-400 cursor-not-allowed">Interfaces</span>
        <span class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-400 cursor-not-allowed">Metrics</span>
        <span class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-400 cursor-not-allowed">Alerts</span>
        <span class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-400 cursor-not-allowed">Reports</span>

        @can('settings.view')
            <div class="px-3 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-400">System</div>
            <a href="{{ route('settings.edit') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 {{ request()->routeIs('settings.*') ? 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                Settings
            </a>
        @endcan
    </nav>
</aside>

<div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" @click="sidebarOpen = false"></div>
