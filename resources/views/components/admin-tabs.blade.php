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
        ['route' => 'admin.whatsapp.index', 'label' => 'WhatsApp', 'pattern' => 'admin.whatsapp.*'],
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
