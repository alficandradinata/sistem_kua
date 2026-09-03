{{-- [SISTEM KUA] Detail reservasi warga. Lihat PROGRESS.md. --}}
<x-app-layout>
    <x-slot name="title">Detail Reservasi</x-slot>

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-display text-2xl font-semibold text-stone-900">Detail Reservasi</h2>
                <p class="mt-1 text-sm text-stone-500">{{ $reservation->service->name }}</p>
            </div>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-stone-600 transition hover:text-stone-900">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">

            <x-alert />

            {{-- Nomor antrean sebagai bintang halaman: inilah yang dicari warga
                 saat membuka halaman ini, jadi bukan sekadar satu baris di tabel. --}}
            @if ($reservation->queueDetail)
                <div class="overflow-hidden rounded-2xl bg-kua-900 text-center shadow-naik">
                    <div class="garis-emas h-1"></div>
                    <div class="px-6 py-9">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emas-300">
                            Nomor Antrean Anda
                        </p>
                        <p class="mt-4 font-display text-6xl font-semibold tracking-tight text-white sm:text-7xl">
                            {{ $reservation->queueDetail->queue_number }}
                        </p>
                        <p class="mt-4 text-sm text-kua-100">
                            Tunjukkan nomor ini di loket pada
                            {{ $reservation->formatted_date }}, {{ $reservation->formatted_time }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- Rincian --}}
            <div class="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-kartu">
                <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                    <h3 class="font-display text-base font-semibold text-stone-800">
                        {{ $reservation->service->name }}
                    </h3>
                    <x-status-badge :color="$reservation->status_color" :label="$reservation->status_label" />
                </div>

                <dl class="divide-y divide-stone-100 text-sm">
                    <div class="flex gap-4 px-6 py-4">
                        <dt class="w-32 shrink-0 text-stone-500">Tanggal</dt>
                        <dd class="font-medium text-stone-900">{{ $reservation->full_date }}</dd>
                    </div>
                    <div class="flex gap-4 px-6 py-4">
                        <dt class="w-32 shrink-0 text-stone-500">Jam</dt>
                        <dd class="font-medium text-stone-900">{{ $reservation->formatted_time }}</dd>
                    </div>
                    <div class="flex gap-4 px-6 py-4">
                        <dt class="w-32 shrink-0 text-stone-500">Biaya</dt>
                        <dd class="font-medium text-stone-900">{{ $reservation->service->formatted_fee }}</dd>
                    </div>
                    @if ($reservation->notes)
                        <div class="flex gap-4 px-6 py-4">
                            <dt class="w-32 shrink-0 text-stone-500">Catatan</dt>
                            <dd class="text-stone-700">{{ $reservation->notes }}</dd>
                        </div>
                    @endif
                    {{-- [SISTEM KUA] Jejak audit — warga berhak tahu siapa yang memutuskan --}}
                    @if ($reservation->verification_log)
                        <div class="flex gap-4 px-6 py-4">
                            <dt class="w-32 shrink-0 text-stone-500">Diverifikasi</dt>
                            <dd class="text-stone-600">{{ $reservation->verification_log }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($reservation->canBeCancelled())
                    <div class="flex justify-end border-t border-stone-100 bg-stone-50 px-6 py-4">
                        <form method="POST" action="{{ route('reservations.cancel', $reservation) }}"
                              onsubmit="return confirm('Batalkan reservasi ini?')">
                            @csrf
                            @method('PATCH')
                            <x-danger-button>Batalkan Reservasi</x-danger-button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- [SISTEM KUA] Alasan penolakan punya kolomnya sendiri, tidak lagi
                 menumpang `notes` milik warga — jadi ditampilkan terpisah. --}}
            @if ($reservation->isRejected())
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-6">
                    <h3 class="font-display text-base font-semibold text-rose-900">
                        Reservasi ini ditolak petugas
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-rose-800">
                        {{ $reservation->rejection_reason ?: 'Petugas tidak mencantumkan alasan.' }}
                    </p>
                    <p class="mt-3 text-xs text-rose-700">
                        Anda bisa mengajukan reservasi baru setelah kendalanya diperbaiki.
                    </p>
                </div>
            @endif

            {{-- [SISTEM KUA] Koordinasi lewat WhatsApp, pesan sudah terisi konteks reservasi ini --}}
            @php
                $waKua = config('whatsapp.contact_number');
                $waPesan = 'Halo KUA, saya ingin berkoordinasi soal reservasi '
                    .$reservation->service->name.' pada '.$reservation->full_date
                    .' pukul '.$reservation->formatted_time
                    .($reservation->queueDetail ? ' (antrean '.$reservation->queueDetail->queue_number.')' : '')
                    .'.';
                $waLink = \App\Support\PhoneNumber::waMeLink($waKua, $waPesan);
            @endphp
            @if ($waLink)
                <a href="{{ $waLink }}" target="_blank" rel="noopener"
                   class="flex items-center justify-between gap-4 rounded-xl border border-stone-200 bg-white p-5 shadow-kartu transition hover:shadow-naik">
                    <div>
                        <p class="text-sm font-semibold text-stone-900">Perlu berkoordinasi soal reservasi ini?</p>
                        <p class="mt-1 text-xs text-stone-600">
                            Chat petugas lewat WhatsApp &mdash; pesannya sudah kami isikan otomatis.
                        </p>
                    </div>
                    <span class="shrink-0 rounded-lg bg-kua-700 px-5 py-2.5 text-sm font-semibold text-white">
                        Chat
                    </span>
                </a>
            @endif

        </div>
    </div>
</x-app-layout>
