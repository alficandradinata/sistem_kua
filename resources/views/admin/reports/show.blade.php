{{-- [SISTEM KUA] Rincian satu laporan rekap. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Laporan {{ $report->type_label }} &mdash; {{ $report->period_label }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.export', $report) }}"
                   class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                    Unduh CSV
                </a>
                <a href="{{ route('admin.reports.index', ['type' => $report->report_type]) }}"
                   class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            <x-admin-tabs />
            <x-alert />

            <p class="text-sm text-gray-600">
                Rentang {{ $periodStart->locale('id')->translatedFormat('l, j F Y') }}
                @if (! $periodStart->isSameDay($periodEnd))
                    s.d. {{ $periodEnd->locale('id')->translatedFormat('l, j F Y') }}
                @endif
                &middot; dibuat oleh {{ $report->generatedBy?->name ?? '-' }}
                pada {{ $report->created_at->locale('id')->translatedFormat('j F Y H:i') }}
            </p>

            {{-- Angka utama --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">Total reservasi</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ $report->total_reservations }}</p>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">Selesai dilayani</p>
                    <p class="mt-1 text-3xl font-bold text-green-700">{{ $report->total_completed }}</p>
                    <p class="mt-2 text-xs text-gray-500">{{ $report->completion_rate }}% dari total</p>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">Dibatalkan / ditolak</p>
                    <p class="mt-1 text-3xl font-bold text-red-700">{{ $report->total_cancelled }}</p>
                    <p class="mt-2 text-xs text-gray-500">{{ $report->cancellation_rate }}% dari total</p>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">Belum tuntas</p>
                    <p class="mt-1 text-3xl font-bold text-yellow-700">{{ $report->total_pending }}</p>
                    <p class="mt-2 text-xs text-gray-500">menunggu / disetujui</p>
                </div>
            </div>

            {{-- Tren harian: hanya berguna kalau periodenya lebih dari sehari --}}
            @if (! $periodStart->isSameDay($periodEnd))
                <x-report-trend :trend="$report->dailyTrend()" />
            @endif

            {{-- Rincian per layanan --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-3 text-sm font-semibold text-gray-700">
                    Rincian per Layanan
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Layanan</th>
                            <th class="px-5 py-3 text-right">Total</th>
                            <th class="px-5 py-3 text-right">Selesai</th>
                            <th class="px-5 py-3 text-right">Batal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($breakdown as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium text-gray-900">{{ $row->service_name }}</td>
                                <td class="px-5 py-3 text-right">{{ $row->total }}</td>
                                <td class="px-5 py-3 text-right text-green-700">{{ $row->completed }}</td>
                                <td class="px-5 py-3 text-right text-red-700">{{ $row->cancelled }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-gray-500">Tidak ada reservasi pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Daftar reservasi --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-3 text-sm font-semibold text-gray-700">
                    Daftar Reservasi Periode Ini
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Jam</th>
                                <th class="px-5 py-3">Warga</th>
                                <th class="px-5 py-3">Layanan</th>
                                <th class="px-5 py-3">No. Antrean</th>
                                <th class="px-5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($reservations as $reservation)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3 whitespace-nowrap">{{ $reservation->formatted_date }}</td>
                                    <td class="px-5 py-3 whitespace-nowrap">{{ $reservation->formatted_time }}</td>
                                    <td class="px-5 py-3">{{ $reservation->user?->name ?? '-' }}</td>
                                    <td class="px-5 py-3">{{ $reservation->service?->name ?? '-' }}</td>
                                    <td class="px-5 py-3 font-mono">{{ $reservation->queueDetail?->queue_number ?? '-' }}</td>
                                    <td class="px-5 py-3"><x-status-badge :color="$reservation->status_color" :label="$reservation->status_label" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">Belum ada reservasi pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($reservations->hasPages())
                    <div class="border-t border-gray-100 px-5 py-3">{{ $reservations->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
