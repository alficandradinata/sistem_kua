{{-- [SISTEM KUA] Ringkasan administrator. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-stone-800">Dashboard Administrator</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <x-admin-tabs />
            <x-alert />

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('admin.services.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                    <p class="text-sm text-stone-500">Layanan</p>
                    <p class="mt-1 text-3xl font-bold text-stone-900">{{ $serviceCount }}</p>
                    <p class="mt-2 text-xs text-kua-600">Kelola layanan &rarr;</p>
                </a>
                <a href="{{ route('admin.slots.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                    <p class="text-sm text-stone-500">Slot antrean</p>
                    <p class="mt-1 text-3xl font-bold text-stone-900">{{ $slotCount }}</p>
                    <p class="mt-2 text-xs text-kua-600">Atur kuota &rarr;</p>
                </a>
                <a href="{{ route('admin.reports.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                    <p class="text-sm text-stone-500">Total reservasi</p>
                    <p class="mt-1 text-3xl font-bold text-stone-900">{{ $reservationCount }}</p>
                    <p class="mt-2 text-xs text-kua-600">Buat laporan rekap &rarr;</p>
                </a>
                <a href="{{ route('admin.users.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                    <p class="text-sm text-stone-500">Warga / Petugas</p>
                    <p class="mt-1 text-3xl font-bold text-stone-900">{{ $wargaCount }} / {{ $petugasCount }}</p>
                    <p class="mt-2 text-xs text-kua-600">Kelola akun &rarr;</p>
                </a>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-3 font-semibold text-stone-800">Jam Operasional</h3>
                    <ul class="space-y-1 text-sm">
                        @foreach ($schedules as $schedule)
                            <li class="flex justify-between">
                                <span class="text-stone-600">{{ $schedule->day_name }}</span>
                                <span class="{{ $schedule->is_active ? 'text-stone-900' : 'text-stone-400' }}">
                                    {{ $schedule->operational_hours }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('admin.schedules.index') }}" class="mt-3 inline-block text-xs text-kua-600 hover:underline">
                        Ubah jam operasional &rarr;
                    </a>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-3 font-semibold text-stone-800">Hari Libur Mendatang</h3>
                    <ul class="space-y-1 text-sm">
                        @forelse ($holidays as $holiday)
                            <li class="flex justify-between">
                                <span class="text-stone-600">{{ $holiday->description }}</span>
                                <span class="text-stone-900">{{ $holiday->formatted_date }}</span>
                            </li>
                        @empty
                            <li class="text-sm text-stone-500">Tidak ada hari libur terdekat.</li>
                        @endforelse
                    </ul>
                    <a href="{{ route('admin.holidays.index') }}" class="mt-3 inline-block text-xs text-kua-600 hover:underline">
                        Kelola hari libur &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
