{{-- [SISTEM KUA] Rincian satu laporan rekap. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-stone-800">
                Laporan {{ $report->type_label }} &mdash; {{ $report->period_label }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.pdf', $report) }}"
                   class="rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                    Unduh PDF
                </a>
                <a href="{{ route('admin.reports.export', $report) }}"
                   class="rounded-md bg-kua-600 px-4 py-2 text-sm font-semibold text-white hover:bg-kua-700">
                    Unduh CSV
                </a>
                <a href="{{ route('admin.reports.index', ['type' => $report->report_type]) }}"
                   class="rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-50">
                    Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            <x-admin-tabs />
            <x-alert />

            <p class="text-sm text-stone-600">
                Rentang {{ $periodStart->locale('id')->translatedFormat('l, j F Y') }}
                @if (! $periodStart->isSameDay($periodEnd))
                    s.d. {{ $periodEnd->locale('id')->translatedFormat('l, j F Y') }}
                @endif
                &middot; dibuat oleh {{ $report->generatedBy?->name ?? '-' }}
                pada {{ $report->created_at->locale('id')->translatedFormat('j F Y H:i') }}
            </p>

            {{-- Angka utama --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-stone-500">Total reservasi</p>
                    <p class="mt-1 text-3xl font-bold text-stone-900">{{ $report->total_reservations }}</p>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-stone-500">Selesai dilayani</p>
                    <p class="mt-1 text-3xl font-bold text-kua-700">{{ $report->total_completed }}</p>
                    <p class="mt-2 text-xs text-stone-500">{{ $report->completion_rate }}% dari total</p>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-stone-500">Ditolak petugas</p>
                    <p class="mt-1 text-3xl font-bold text-rose-700">{{ $report->total_rejected }}</p>
                    <p class="mt-2 text-xs text-stone-500">{{ $report->rejection_rate }}% dari total</p>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-stone-500">Dibatalkan warga</p>
                    <p class="mt-1 text-3xl font-bold text-stone-700">{{ $report->total_cancelled }}</p>
                    <p class="mt-2 text-xs text-stone-500">{{ $report->cancellation_rate }}% dari total</p>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-stone-500">Belum tuntas</p>
                    <p class="mt-1 text-3xl font-bold text-amber-700">{{ $report->total_pending }}</p>
                    <p class="mt-2 text-xs text-stone-500">menunggu / disetujui</p>
                </div>
            </div>

            {{-- Tren harian: hanya berguna kalau periodenya lebih dari sehari --}}
            @if (! $periodStart->isSameDay($periodEnd))
                <x-report-trend :trend="$report->dailyTrend()" />
            @endif

            {{-- Rincian per layanan --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-stone-100 px-5 py-3 text-sm font-semibold text-stone-700">
                    Rincian per Layanan
                </div>
                <table class="min-w-full divide-y divide-stone-200 text-sm">
                    <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="px-5 py-3">Layanan</th>
                            <th class="px-5 py-3 text-right">Total</th>
                            <th class="px-5 py-3 text-right">Selesai</th>
                            <th class="px-5 py-3 text-right">Ditolak</th>
                            <th class="px-5 py-3 text-right">Dibatalkan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($breakdown as $row)
                            <tr class="hover:bg-stone-50">
                                <td class="px-5 py-3 font-medium text-stone-900">{{ $row->service_name }}</td>
                                <td class="px-5 py-3 text-right">{{ $row->total }}</td>
                                <td class="px-5 py-3 text-right text-kua-700">{{ $row->completed }}</td>
                                <td class="px-5 py-3 text-right text-rose-700">{{ $row->rejected }}</td>
                                <td class="px-5 py-3 text-right text-stone-600">{{ $row->cancelled }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-stone-500">Tidak ada reservasi pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Daftar reservasi --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-stone-100 px-5 py-3 text-sm font-semibold text-stone-700">
                    Daftar Reservasi Periode Ini
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                            <tr>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Jam</th>
                                <th class="px-5 py-3">Warga</th>
                                <th class="px-5 py-3">Layanan</th>
                                <th class="px-5 py-3">No. Antrean</th>
                                <th class="px-5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($reservations as $reservation)
                                <tr class="hover:bg-stone-50">
                                    <td class="px-5 py-3 whitespace-nowrap">{{ $reservation->formatted_date }}</td>
                                    <td class="px-5 py-3 whitespace-nowrap">{{ $reservation->formatted_time }}</td>
                                    <td class="px-5 py-3">{{ $reservation->user?->name ?? '-' }}</td>
                                    <td class="px-5 py-3">{{ $reservation->service?->name ?? '-' }}</td>
                                    <td class="px-5 py-3 font-mono">{{ $reservation->queueDetail?->queue_number ?? '-' }}</td>
                                    <td class="px-5 py-3"><x-status-badge :color="$reservation->status_color" :label="$reservation->status_label" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-10 text-center text-stone-500">Belum ada reservasi pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($reservations->hasPages())
                    <div class="border-t border-stone-100 px-5 py-3">{{ $reservations->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
