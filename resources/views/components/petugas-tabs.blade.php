{{-- [SISTEM KUA] Sub-navigasi area petugas. --}}
@php
    $tabs = [
        ['route' => 'petugas.dashboard', 'label' => 'Ringkasan', 'pattern' => 'petugas.dashboard'],
        ['route' => 'petugas.reservations.index', 'label' => 'Verifikasi Reservasi', 'pattern' => 'petugas.reservations.*'],
        ['route' => 'petugas.queues.index', 'label' => 'Papan Antrean', 'pattern' => 'petugas.queues.*'],
    ];
@endphp

<nav class="flex flex-wrap gap-1 border-b border-gray-200">
    @foreach ($tabs as $tab)
        @php $active = request()->routeIs($tab['pattern']); @endphp
        <a href="{{ route($tab['route']) }}"
           class="-mb-px border-b-2 px-4 py-2 text-sm font-medium transition
                  {{ $active
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
