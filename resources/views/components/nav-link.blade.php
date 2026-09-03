{{-- [SISTEM KUA] Tautan navigasi utama — dipakai di atas bilah hijau gelap. --}}
@props(['active'])

@php
    // Penanda aktif: garis emas di bawah + teks putih. Tidak mengandalkan warna
    // saja — bobot hurufnya ikut naik, supaya tetap terbaca kalau warnanya lolos.
    $classes = ($active ?? false)
        ? 'inline-flex items-center border-b-2 border-emas-400 px-1 pt-1 text-sm font-semibold leading-5 text-white transition'
        : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-kua-100 transition hover:border-kua-400 hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
