{{-- [SISTEM KUA] Sub-navigasi area admin. --}}
@php
    $tabs = [
        ['route' => 'admin.dashboard', 'label' => 'Ringkasan', 'pattern' => 'admin.dashboard'],
        ['route' => 'admin.services.index', 'label' => 'Layanan', 'pattern' => 'admin.services.*'],
        ['route' => 'admin.schedules.index', 'label' => 'Jam Operasional', 'pattern' => 'admin.schedules.*'],
        ['route' => 'admin.slots.index', 'label' => 'Slot Antrean', 'pattern' => 'admin.slots.*'],
        ['route' => 'admin.holidays.index', 'label' => 'Hari Libur', 'pattern' => 'admin.holidays.*'],
        ['route' => 'admin.users.index', 'label' => 'Pengguna', 'pattern' => 'admin.users.*'],
        ['route' => 'admin.reports.index', 'label' => 'Laporan', 'pattern' => 'admin.reports.*'],
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
