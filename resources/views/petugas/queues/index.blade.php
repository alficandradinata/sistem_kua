{{-- [SISTEM KUA] Papan antrean harian petugas. Lihat PROGRESS.md. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-stone-800">Papan Antrean</h2>
            {{-- Dibuka di tab baru untuk ditayangkan di layar ruang tunggu --}}
            <a href="{{ route('queue.display') }}" target="_blank" rel="noopener"
               class="rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-50">
                Buka Layar Antrean
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">

            <x-petugas-tabs />
            <x-alert />

            {{-- Ringkasan + kontrol tanggal --}}
            <div class="flex flex-col gap-4 rounded-lg bg-white p-5 shadow-sm sm:flex-row sm:items-end sm:justify-between">
                <form method="GET" class="flex items-end gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-stone-500">Tanggal antrean</label>
                        <input type="date" name="date" value="{{ $date }}"
                               class="rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500">
                    </div>
                    <button class="rounded-md bg-stone-800 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-700">
                        Tampilkan
                    </button>
                </form>

                <form method="POST" action="{{ route('petugas.queues.callNext', ['date' => $date]) }}">
                    @csrf @method('PATCH')
                    <button @disabled($waiting === 0)
                            class="rounded-md px-5 py-2 text-sm font-semibold text-white
                                   {{ $waiting === 0 ? 'cursor-not-allowed bg-stone-300' : 'bg-kua-600 hover:bg-kua-700' }}">
                        Panggil Antrean Berikutnya
                    </button>
                </form>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-stone-500">Menunggu</p>
                    <p class="mt-1 text-3xl font-bold text-stone-900">{{ $waiting }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-stone-500">Sedang dipanggil</p>
                    <p class="mt-1 text-3xl font-bold text-kua-600">{{ $called }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-stone-500">Sudah dilayani</p>
                    <p class="mt-1 text-3xl font-bold text-kua-600">{{ $attended }}</p>
                </div>
            </div>

            {{-- Daftar antrean --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                            <tr>
                                <th class="px-5 py-3">No.</th>
                                <th class="px-5 py-3">Pemohon</th>
                                <th class="px-5 py-3">Layanan</th>
                                <th class="px-5 py-3">Jam</th>
                                <th class="px-5 py-3">Keadaan</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($queues as $queue)
                                <tr class="hover:bg-stone-50">
                                    <td class="px-5 py-3 font-mono text-base font-semibold text-stone-900">
                                        {{ $queue->queue_number }}
                                    </td>
                                    <td class="px-5 py-3">{{ $queue->reservation->user->name }}</td>
                                    <td class="px-5 py-3">{{ $queue->reservation->service->name }}</td>
                                    <td class="px-5 py-3">{{ $queue->reservation->formatted_time }}</td>
                                    <td class="px-5 py-3">
                                        @if ($queue->isAttended())
                                            <x-status-badge color="bg-kua-100 text-kua-800" label="Sudah dilayani" />
                                            <div class="mt-1 text-xs text-stone-400">
                                                {{ $queue->attended_at?->format('H:i') }}
                                                @if ($queue->waiting_duration !== null)
                                                    · {{ $queue->waiting_duration }} mnt
                                                @endif
                                            </div>
                                        @elseif ($queue->is_called)
                                            <x-status-badge color="bg-sky-100 text-sky-800" label="Sedang dipanggil" />
                                            <div class="mt-1 text-xs text-stone-400">{{ $queue->called_at?->format('H:i') }}</div>
                                        @else
                                            <x-status-badge color="bg-amber-100 text-amber-800" label="Menunggu" />
                                        @endif

                                        {{-- [SISTEM KUA] Jejak audit: petugas loket penanggung jawab --}}
                                        @if ($queue->handled_by_label)
                                            <div class="mt-1 text-xs text-stone-400">{{ $queue->handled_by_label }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        @if (! $queue->is_called)
                                            <form method="POST" action="{{ route('petugas.queues.call', $queue) }}">
                                                @csrf @method('PATCH')
                                                <button class="rounded-md bg-kua-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-kua-700">
                                                    Panggil
                                                </button>
                                            </form>
                                        @elseif (! $queue->isAttended())
                                            <form method="POST" action="{{ route('petugas.queues.attend', $queue) }}">
                                                @csrf @method('PATCH')
                                                <button class="rounded-md bg-kua-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-kua-700">
                                                    Selesai Dilayani
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-stone-400">Selesai</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-stone-500">
                                        Belum ada antrean untuk tanggal ini. Antrean terbit otomatis saat reservasi disetujui.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
