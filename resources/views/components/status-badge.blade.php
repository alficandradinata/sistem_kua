{{-- [SISTEM KUA] Badge status reservasi. --}}
@props(['status' => '', 'label' => null, 'color' => null])

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium '
        .($color ?? 'bg-gray-100 text-gray-800'),
]) }}>
    {{ $label ?? $slot }}
</span>
