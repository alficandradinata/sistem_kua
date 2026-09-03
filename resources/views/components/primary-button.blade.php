{{-- [SISTEM KUA] Tombol aksi utama. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-lg bg-kua-700 px-5 py-2.5 text-sm font-semibold text-white shadow-kartu transition hover:bg-kua-600 active:bg-kua-800 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
