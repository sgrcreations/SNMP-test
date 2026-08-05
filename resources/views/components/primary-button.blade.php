<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-xl border border-transparent bg-cyan-600 px-4 py-2.5 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
