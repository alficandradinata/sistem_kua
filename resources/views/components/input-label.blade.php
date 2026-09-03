{{-- [SISTEM KUA] Label input. --}}
@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-stone-700']) }}>
    {{ $value ?? $slot }}
</label>
