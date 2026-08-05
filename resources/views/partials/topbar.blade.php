<header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800" @click="sidebarOpen = true">
                <span class="sr-only">Open sidebar</span>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div>
                <h1 class="text-base font-semibold sm:text-lg">{{ $header ?? ($title ?? 'Dashboard') }}</h1>
                @isset($subheader)
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $subheader }}</p>
                @endisset
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            <button type="button"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    @click="dark = !dark">
                <span x-show="!dark">Dark</span>
                <span x-show="dark" x-cloak>Light</span>
            </button>

            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                    <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-cyan-600 text-xs font-semibold text-white">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak
                     class="absolute end-0 mt-2 w-48 rounded-lg border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full px-4 py-2 text-start text-sm hover:bg-slate-50 dark:hover:bg-slate-800">Log out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
