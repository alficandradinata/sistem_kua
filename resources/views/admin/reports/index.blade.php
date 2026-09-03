{{-- [SISTEM KUA] Daftar & pembuatan laporan rekap. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-stone-800">Laporan Rekap Reservasi</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            <x-admin-tabs />
            <x-alert />

            {{-- Pilih periode + pratinjau angka sebelum disimpan --}}
            <div class="rounded-lg bg-white p-5 shadow-sm">
                <form method="GET" class="grid gap-4 sm:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-stone-500">Jenis laporan</label>
                        <select name="type"
                                class="w-full rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">
                            @foreach (\App\Models\Report::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-stone-500">Tanggal acuan</label>
                        <input type="date" name="date" value="{{ $date }}"
                               class="w-full rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">
                    </div>
                    <div class="flex items-end">
                        <button class="rounded-md bg-stone-800 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-700">
                            Hitung Pratinjau
                        </button>
                    </div>
                </form>

                <div class="mt-5 rounded-lg border border-kua-100 bg-kua-50 p-4">
                    <p class="text-sm font-medium text-kua-900">
                        Periode
                        {{ $periodStart->locale('id')->translatedFormat('j F Y') }}
                        @if (! $periodStart->isSameDay($periodEnd))
                            &ndash; {{ $periodEnd->locale('id')->translatedFormat('j F Y') }}
                        @endif
                    </p>

                    <div class="mt-3 grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        <div>
                            <p class="text-xs text-kua-700">Total</p>
                            <p class="text-2xl font-bold text-kua-900">{{ $preview['total'] }}</p>
                        </div>
                        @foreach (\App\Models\Reservation::STATUSES as $status => $label)
                            <div>
                                <p class="text-xs text-kua-700">{{ $label }}</p>
                                <p class="text-2xl font-bold text-kua-900">{{ $preview[$status] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('admin.reports.store') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="report_type" value="{{ $type }}">
                        <input type="hidden" name="report_date" value="{{ $date }}">
                        <x-primary-button>Simpan sebagai Laporan</x-primary-button>
                        <span class="ml-2 text-xs text-kua-700">
                            Periode yang sudah pernah disimpan akan diperbarui, bukan diduplikasi.
                        </span>
                    </form>
                </div>
            </div>

            {{-- Laporan tersimpan --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-stone-100 px-5 py-3 text-sm font-semibold text-stone-700">
                    Laporan {{ \App\Models\Report::TYPES[$type] }} Tersimpan
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                            <tr>
                                <th class="px-5 py-3">Periode</th>
                                <th class="px-5 py-3 text-right">Total</th>
                                <th class="px-5 py-3 text-right">Selesai</th>
                                <th class="px-5 py-3 text-right">Ditolak</th>
                                <th class="px-5 py-3 text-right">Dibatalkan</th>
                                <th class="px-5 py-3 text-right">% Selesai</th>
                                <th class="px-5 py-3">Dibuat oleh</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($reports as $report)
                                <tr class="hover:bg-stone-50">
                                    <td class="px-5 py-3 font-medium text-stone-900">{{ $report->period_label }}</td>
                                    <td class="px-5 py-3 text-right">{{ $report->total_reservations }}</td>
                                    <td class="px-5 py-3 text-right text-kua-700">{{ $report->total_completed }}</td>
                                    <td class="px-5 py-3 text-right text-rose-700">{{ $report->total_rejected }}</td>
                                    <td class="px-5 py-3 text-right text-stone-600">{{ $report->total_cancelled }}</td>
                                    <td class="px-5 py-3 text-right">{{ $report->completion_rate }}%</td>
                                    <td class="px-5 py-3 text-stone-600">{{ $report->generatedBy?->name ?? '-' }}</td>
                                    <td class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.reports.show', $report) }}"
                                               class="rounded-md bg-kua-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-kua-700">
                                                Lihat
                                            </a>
                                            <form method="POST" action="{{ route('admin.reports.destroy', $report) }}"
                                                  onsubmit="return confirm('Hapus laporan ini?')">
                                                @csrf @method('DELETE')
                                                <button class="rounded-md border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-10 text-center text-stone-500">
                                        Belum ada laporan {{ strtolower(\App\Models\Report::TYPES[$type]) }} tersimpan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($reports->hasPages())
                    <div class="border-t border-stone-100 px-5 py-3">{{ $reports->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
