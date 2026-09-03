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

        </div>
    </div>
</x-app-layout>
