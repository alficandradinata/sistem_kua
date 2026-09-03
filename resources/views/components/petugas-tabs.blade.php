{{-- [SISTEM KUA] Sub-navigasi area petugas. --}}
@php
    $tabs = [
        ['route' => 'petugas.dashboard', 'label' => 'Ringkasan', 'pattern' => 'petugas.dashboard'],
        ['route' => 'petugas.reservations.index', 'label' => 'Verifikasi Reservasi', 'pattern' => 'petugas.reservations.*'],
        ['route' => 'petugas.queues.index', 'label' => 'Papan Antrean', 'pattern' => 'petugas.queues.*'],
        ['route' => 'petugas.whatsapp.index', 'label' => 'Koordinasi WhatsApp', 'pattern' => 'petugas.whatsapp.*'],
    ];
@endphp

<nav class="flex flex-wrap gap-x-1 border-b border-stone-200">
    @foreach ($tabs as $tab)
        @php $active = request()->routeIs($tab['pattern']); @endphp
        <a href="{{ route($tab['route']) }}"
           class="-mb-px border-b-2 px-4 py-2.5 text-sm transition
                  {{ $active
                        ? 'border-kua-700 font-semibold text-kua-800'
                        : 'border-transparent font-medium text-stone-500 hover:border-stone-300 hover:text-stone-800' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
