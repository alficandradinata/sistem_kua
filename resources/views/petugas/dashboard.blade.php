{{-- [SISTEM KUA] Ringkasan petugas KUA. Lihat PROGRESS.md. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Dashboard Petugas KUA</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">

            <x-petugas-tabs />
            <x-alert />

            <div class="grid gap-4 sm:grid-cols-3">
                <a href="{{ route('petugas.reservations.index', ['date' => now()->toDateString()]) }}"
                   class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                    <p class="text-sm text-gray-500">Reservasi hari ini</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ $todayCount }}</p>
                    <p class="mt-2 text-xs text-indigo-600">Lihat daftar &rarr;</p>
                </a>
                <a href="{{ route('petugas.reservations.index', ['status' => 'pending']) }}"
                   class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                    <p class="text-sm text-gray-500">Menunggu persetujuan</p>
                    <p class="mt-1 text-3xl font-bold text-yellow-600">{{ $pendingCount }}</p>
                    <p class="mt-2 text-xs text-indigo-600">Verifikasi sekarang &rarr;</p>
                </a>
                <a href="{{ route('petugas.queues.index') }}"
                   class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                    <p class="text-sm text-gray-500">Antrean belum dipanggil</p>
                    <p class="mt-1 text-3xl font-bold text-indigo-600">{{ $waitingQueue }}</p>
                    <p class="mt-2 text-xs text-indigo-600">Buka papan antrean &rarr;</p>
                </a>
            </div>

            {{-- Reservasi terbaru menunggu --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-800">Menunggu Verifikasi Terbaru</h3>
                    <a href="{{ route('petugas.reservations.index', ['status' => 'pending']) }}"
                       class="text-sm text-indigo-600 hover:underline">Lihat semua</a>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($latestPending as $reservation)
                            <tr>
                                <td class="px-5 py-3">{{ $reservation->user->name }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $reservation->service->name }}</td>
                                <td class="px-5 py-3 text-gray-600">
                                    {{ $reservation->formatted_date }} · {{ $reservation->formatted_time }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-5 py-8 text-center text-gray-500">Tidak ada reservasi yang menunggu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
