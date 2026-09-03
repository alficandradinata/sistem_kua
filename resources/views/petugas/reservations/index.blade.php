{{-- [SISTEM KUA] Verifikasi reservasi oleh petugas. Lihat PROGRESS.md. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-stone-800">Verifikasi Reservasi</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">

            <x-petugas-tabs />
            <x-alert />

            {{-- Filter --}}
            <form method="GET" class="grid gap-3 rounded-lg bg-white p-5 shadow-sm sm:grid-cols-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-stone-500">Status</label>
                    <select name="status" class="w-full rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-stone-500">Layanan</label>
                    <select name="service_id" class="w-full rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">
                        <option value="">Semua layanan</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected($filters['service_id'] == $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-stone-500">Tanggal</label>
                    <input type="date" name="date" value="{{ $filters['date'] }}"
                           class="w-full rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">
                </div>
                <div class="flex items-end gap-2">
                    <button class="rounded-md bg-stone-800 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-700">
                        Terapkan
                    </button>
                    <a href="{{ route('petugas.reservations.index') }}" class="px-2 py-2 text-sm text-stone-500 hover:text-stone-800">
                        Reset
                    </a>
                </div>
            </form>

            {{-- Tabel --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                            <tr>
                                <th class="px-5 py-3">Pemohon</th>
                                <th class="px-5 py-3">Layanan</th>
                                <th class="px-5 py-3">Jadwal</th>
                                <th class="px-5 py-3">Antrean</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($reservations as $reservation)
                                <tr class="hover:bg-stone-50">
                                    <td class="px-5 py-3">
                                        <div class="font-medium text-stone-900">{{ $reservation->user->name }}</div>
                                        <div class="text-xs text-stone-500">{{ $reservation->user->phone ?? $reservation->user->email }}</div>
                                    </td>
                                    <td class="px-5 py-3">{{ $reservation->service->name }}</td>
                                    <td class="px-5 py-3">
                                        <div>{{ $reservation->formatted_date }}</div>
                                        <div class="text-xs text-stone-500">{{ $reservation->formatted_time }}</div>
                                    </td>
                                    <td class="px-5 py-3 font-mono text-stone-700">
                                        {{ $reservation->queueDetail?->queue_number ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-status-badge :color="$reservation->status_color" :label="$reservation->status_label" />
                                        @if ($reservation->isRejected() && $reservation->rejection_reason)
                                            <p class="mt-1 max-w-[16rem] text-xs text-stone-500">
                                                {{ $reservation->rejection_reason }}
                                            </p>
                                        @endif
                                        {{-- [SISTEM KUA] Jejak audit: penanggung jawab keputusan --}}
                                        @if ($reservation->verification_log)
                                            <p class="mt-1 max-w-[16rem] text-xs text-stone-400">
                                                {{ $reservation->verification_log }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        @if ($reservation->isPending())
                                            <div x-data="{ tolak: false }" class="flex flex-col items-end gap-2">
                                                <div class="flex justify-end gap-2">
                                                    <form method="POST" action="{{ route('petugas.reservations.approve', $reservation) }}">
                                                        @csrf @method('PATCH')
                                                        <button class="rounded-md bg-kua-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-kua-700">
                                                            Setujui
                                                        </button>
                                                    </form>
                                                    <button type="button" @click="tolak = !tolak"
                                                            class="rounded-md border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                        Tolak
                                                    </button>
                                                </div>
                                                <form x-show="tolak" x-cloak method="POST"
                                                      action="{{ route('petugas.reservations.reject', $reservation) }}"
                                                      class="flex w-64 gap-2">
                                                    @csrf @method('PATCH')
                                                    <input type="text" name="reason" required minlength="5" maxlength="255"
                                                           placeholder="Alasan penolakan"
                                                           class="w-full rounded-md border-stone-300 text-xs focus:border-rose-500 focus:ring-rose-500">
                                                    <button class="rounded-md bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">
                                                        Kirim
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="block text-right text-xs text-stone-400">Tidak ada aksi</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-stone-500">
                                        Tidak ada reservasi yang cocok dengan filter.
                                    </td>
                                </tr>
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
