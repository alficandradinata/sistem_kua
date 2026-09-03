{{-- [SISTEM KUA] Lambang aplikasi: lengkung mihrab + kubah kecil.
     Sengaja outline `currentColor` supaya bisa dipakai di bilah hijau maupun
     latar terang tanpa perlu varian file terpisah. --}}
<svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.6"
     stroke-linecap="round" stroke-linejoin="round" {{ $attributes }}>
    {{-- Kubah kecil di puncak --}}
    <path d="M16 3.5c1.6 1.1 2.4 2.3 2.4 3.5 0 1.4-1.1 2.4-2.4 2.4S13.6 8.4 13.6 7c0-1.2.8-2.4 2.4-3.5z" />

    {{-- Lengkung mihrab --}}
    <path d="M7.5 27.5V17.5C7.5 12.8 11.3 9.4 16 9.4s8.5 3.4 8.5 8.1v10" />

    {{-- Lantai --}}
    <path d="M4.5 27.5h23" />

    {{-- Bukaan dalam — memberi kedalaman tanpa menambah garis berat --}}
    <path d="M12.4 27.5v-9.3c0-2.2 1.6-3.9 3.6-3.9s3.6 1.7 3.6 3.9v9.3" />
</svg>
