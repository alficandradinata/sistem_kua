{{-- [SISTEM KUA] Tombol pendamping / batal. --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-lg border border-stone-300 bg-white px-5 py-2.5 text-sm font-semibold text-stone-700 shadow-kartu transition hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
