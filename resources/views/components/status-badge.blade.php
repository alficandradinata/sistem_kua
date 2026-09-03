{{-- [SISTEM KUA] Badge status reservasi. Warnanya datang dari accessor model
     (Reservation::status_color), bukan ditentukan di sini. --}}
@props(['status' => '', 'label' => null, 'color' => null])

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset '
        .($color ?? 'bg-stone-100 text-stone-700 ring-stone-600/15'),
]) }}>
    {{ $label ?? $slot }}
</span>
