{{-- [SISTEM KUA] Input teks dasar. --}}
@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border-stone-300 text-stone-900 shadow-kartu transition placeholder:text-stone-400 focus:border-kua-500 focus:ring-kua-500 disabled:bg-stone-100']) }}>
