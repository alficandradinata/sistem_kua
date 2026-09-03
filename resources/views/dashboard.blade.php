{{-- [SISTEM KUA] Dashboard warga (diedit dari Breeze). Lihat PROGRESS.md. --}}
<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-display text-2xl font-semibold text-stone-900">Dashboard Warga</h2>
                <p class="mt-1 text-sm text-stone-500">
                    Selamat datang, {{ auth()->user()->name }}.
                </p>
            </div>
            <a href="{{ route('reservations.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-kua-700 px-5 py-2.5 text-sm font-semibold text-white shadow-kartu transition hover:bg-kua-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Buat Reservasi
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <x-alert />

            {{-- Reservasi saya --}}
            <div class="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-kartu">
                <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                    <h3 class="font-display text-base font-semibold text-stone-800">Reservasi Saya</h3>
                    @if ($reservations->total() > 0)
                        <span class="text-xs text-stone-500">{{ $reservations->total() }} reservasi</span>
                    @endif
                </div>

                @if ($reservations->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <x-application-logo class="mx-auto h-12 w-12 text-stone-300" />
                        <p class="mt-4 text-sm text-stone-500">Belum ada reservasi.</p>
                        <a href="{{ route('reservations.create') }}"
                           class="mt-5 inline-flex rounded-lg bg-kua-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-kua-600">
                            Buat Reservasi Pertama
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Layanan</th>
                                    <th class="px-6 py-3 font-semibold">Tanggal</th>
                                    <th class="px-6 py-3 font-semibold">Jam</th>
                                    <th class="px-6 py-3 font-semibold">No. Antrean</th>
                                    <th class="px-6 py-3 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @foreach ($reservations as $r)
                                    <tr class="transition hover:bg-stone-50">
                                        <td class="px-6 py-4">
                                            <a href="{{ route('reservations.show', $r) }}"
                                               class="font-semibold text-kua-800 transition hover:text-kua-600 hover:underline">
                                                {{ $r->service->name }}
                                            </a>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-stone-700">{{ $r->formatted_date }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-stone-600">{{ $r->formatted_time }}</td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            @if ($r->queueDetail)
                                                <span class="font-semibold tracking-tight text-stone-900">
                                                    {{ $r->queueDetail->queue_number }}
                                                </span>
                                            @else
                                                <span class="text-stone-400">&mdash;</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <x-status-badge :color="$r->status_color" :label="$r->status_label" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($reservations->hasPages())
                        <div class="border-t border-stone-100 px-6 py-4">{{ $reservations->links() }}</div>
                    @endif
                @endif
            </div>

            {{-- [SISTEM KUA] Keputusan petugas ditampilkan langsung di sini, bukan
                 hanya di lonceng notifikasi — kabar disetujui/ditolak terlalu
                 penting untuk mengandalkan warga mengklik loncengnya sendiri.
                 Panel ini hilang sendiri setelah 7 hari (scope recentlyDecided). --}}
            @if ($decisions->isNotEmpty())
                <section class="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-kartu">
                    <div class="flex items-center gap-2 border-b border-stone-100 px-6 py-4">
                        <svg class="h-5 w-5 text-emas-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <h3 class="font-display text-base font-semibold text-stone-800">Kabar Terbaru</h3>
                    </div>

                    <ul class="divide-y divide-stone-100">
                        @foreach ($decisions as $d)
                            <li class="{{ $d->isRejected() ? 'bg-rose-50/60' : 'bg-kua-50/60' }}">
                                <a href="{{ route('reservations.show', $d) }}"
                                   class="flex items-start gap-4 px-6 py-5 transition hover:bg-white/70">

                                    {{-- Ikon status: penanda kedua di samping warna --}}
                                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full
                                                 {{ $d->isRejected() ? 'bg-rose-100 text-rose-700' : 'bg-kua-100 text-kua-700' }}">
                                        @if ($d->isRejected())
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        @endif
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold {{ $d->isRejected() ? 'text-rose-900' : 'text-kua-900' }}">
                                            Reservasi {{ $d->service->name }}
                                            {{ $d->isRejected() ? 'ditolak petugas' : 'disetujui' }}
                                        </p>
                                        <p class="mt-1 text-xs text-stone-600">
                                            Jadwal {{ $d->formatted_date }}, {{ $d->formatted_time }}
                                        </p>

                                        @if ($d->isRejected())
                                            <p class="mt-2 text-sm text-rose-800">
                                                <span class="font-medium">Alasan:</span>
                                                {{ $d->rejection_reason ?: 'tidak dicantumkan petugas.' }}
                                            </p>
                                            <p class="mt-1.5 text-xs text-rose-700">
                                                Anda bisa mengajukan reservasi baru setelah kendalanya diperbaiki.
                                            </p>
                                        @elseif ($d->queueDetail)
                                            <p class="mt-2 text-sm text-kua-800">
                                                Nomor antrean Anda
                                                <span class="ms-1 rounded-md bg-kua-800 px-2.5 py-1 font-display text-base font-semibold tracking-tight text-white">
                                                    {{ $d->queueDetail->queue_number }}
                                                </span>
                                            </p>
                                        @endif
                                    </div>

                                    <svg class="mt-1 h-4 w-4 shrink-0 text-stone-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

        </div>
    </div>
</x-app-layout>
