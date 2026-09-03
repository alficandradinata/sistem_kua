{{-- [SISTEM KUA] Tautan navigasi versi mobile — juga di atas bilah hijau gelap. --}}
@props(['active'])

@php
    $classes = ($active ?? false)
        ? 'block w-full border-s-4 border-emas-400 bg-white/10 py-2 pe-4 ps-3 text-start text-base font-semibold text-white transition'
        : 'block w-full border-s-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-kua-100 transition hover:border-kua-400 hover:bg-white/5 hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
