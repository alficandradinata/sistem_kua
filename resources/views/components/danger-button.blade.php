{{-- [SISTEM KUA] Tombol aksi merusak (hapus / batalkan). --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-lg bg-rose-700 px-5 py-2.5 text-sm font-semibold text-white shadow-kartu transition hover:bg-rose-600 active:bg-rose-800 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
