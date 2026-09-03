{{-- [SISTEM KUA] Landing page publik. Lihat PROGRESS.md. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservasi Antrean KUA</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700|lora:500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-50 font-sans text-stone-900 antialiased">

    {{-- Header --}}
    <header class="bg-kua-900">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="/" class="flex items-center gap-3">
                <x-application-logo class="h-8 w-auto text-emas-300" />
                <span class="font-display text-base font-semibold tracking-tight text-white">
                    Reservasi Antrean KUA
                </span>
            </a>
            <nav class="flex items-center gap-1 text-sm">
                <a href="{{ route('queue.display') }}"
                   class="rounded-lg px-3 py-2 font-medium text-kua-100 transition hover:bg-white/10 hover:text-white sm:px-4">
                    Layar Antrean
                </a>
                @auth
                    <a href="{{ route(auth()->user()->homeRoute()) }}"
                       class="rounded-lg bg-emas-400 px-4 py-2 font-semibold text-kua-950 transition hover:bg-emas-300">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="rounded-lg px-3 py-2 font-medium text-kua-100 transition hover:bg-white/10 hover:text-white sm:px-4">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}"
                       class="rounded-lg bg-emas-400 px-4 py-2 font-semibold text-kua-950 transition hover:bg-emas-300">
                        Daftar
                    </a>
                @endauth
            </nav>
        </div>
        <div class="garis-emas h-px"></div>
    </header>

    {{-- Hero --}}
    <section class="bg-kua-900">
        <div class="mx-auto max-w-6xl px-4 pb-20 pt-16 text-center sm:px-6 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-emas-300">
                Kantor Urusan Agama
            </p>
            <h1 class="mx-auto mt-5 max-w-3xl font-display text-4xl font-semibold leading-tight text-white sm:text-5xl">
                Ambil antrean layanan KUA secara daring
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-base leading-relaxed text-kua-100">
                Pesan jadwal kedatangan Anda untuk layanan pernikahan, rujuk, legalisir, dan
                konsultasi. Nomor antrean terbit begitu petugas menyetujui &mdash; tanpa antre lama di loket.
            </p>
            @guest
                <div class="mt-9 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('register') }}"
                       class="rounded-lg bg-emas-400 px-7 py-3 text-sm font-semibold text-kua-950 shadow-naik transition hover:bg-emas-300">
                        Mulai Reservasi
                    </a>
                    <a href="{{ route('queue.display') }}"
                       class="rounded-lg border border-white/25 px-7 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        Lihat Antrean Hari Ini
                    </a>
                </div>
            @endguest
        </div>
    </section>

    <main class="mx-auto max-w-6xl space-y-20 px-4 py-16 sm:px-6 lg:px-8">

        {{-- Layanan --}}
        <section>
            <h2 class="font-display text-2xl font-semibold text-stone-900">Layanan Tersedia</h2>
            <div class="garis-emas mt-3 h-0.5 w-16"></div>

            @if ($services->isEmpty())
                <p class="mt-6 text-stone-500">Belum ada layanan.</p>
            @else
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $service)
                        <article class="flex flex-col rounded-xl border border-stone-200 bg-white p-6 shadow-kartu transition hover:shadow-naik">
                            <h3 class="font-display text-lg font-semibold text-stone-900">{{ $service->name }}</h3>
                            <p class="mt-2 flex-1 text-sm leading-relaxed text-stone-600">{{ $service->description }}</p>
                            <dl class="mt-5 flex items-center gap-6 border-t border-stone-100 pt-4 text-sm">
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-stone-400">Durasi</dt>
                                    <dd class="mt-0.5 font-semibold text-stone-800">{{ $service->duration_label }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-stone-400">Biaya</dt>
                                    <dd class="mt-0.5 font-semibold text-stone-800">{{ $service->formatted_fee }}</dd>
                                </div>
                            </dl>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Jadwal & hari libur --}}
        <section class="grid gap-10 lg:grid-cols-2">
            <div>
                <h2 class="font-display text-2xl font-semibold text-stone-900">Jam Pelayanan</h2>
                <div class="garis-emas mt-3 h-0.5 w-16"></div>

                <div class="mt-8 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-kartu">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($schedules as $schedule)
                                <tr>
                                    <td class="px-5 py-3.5 font-medium text-stone-800">{{ $schedule->day_name }}</td>
                                    <td class="px-5 py-3.5 text-right text-stone-600">{{ $schedule->operational_hours }}</td>
                                </tr>
                            @empty
                                <tr><td class="px-5 py-8 text-center text-stone-500">Jadwal belum diatur.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h2 class="font-display text-2xl font-semibold text-stone-900">Hari Libur Mendatang</h2>
                <div class="garis-emas mt-3 h-0.5 w-16"></div>

                <div class="mt-8 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-kartu">
                    <ul class="divide-y divide-stone-100">
                        @forelse ($holidays as $holiday)
                            <li class="flex items-center justify-between px-5 py-3.5 text-sm">
                                <span class="font-medium text-stone-800">{{ $holiday->description }}</span>
                                <span class="text-stone-500">{{ $holiday->formatted_date }}</span>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-sm text-stone-500">Tidak ada hari libur terdekat.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </section>

        {{-- [SISTEM KUA] Kontak WhatsApp untuk koordinasi --}}
        @php
            $waKua = config('whatsapp.contact_number');
            $waPesan = 'Halo KUA, saya ingin bertanya soal reservasi antrean.';
            $waLink = \App\Support\PhoneNumber::waMeLink($waKua, $waPesan);
        @endphp
        @if ($waLink)
            <section>
                <div class="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-kartu">
                    <div class="garis-emas h-1"></div>
                    <div class="p-7 sm:flex sm:items-center sm:justify-between sm:gap-8">
                        <div>
                            <h2 class="font-display text-xl font-semibold text-stone-900">Butuh koordinasi cepat?</h2>
                            <p class="mt-2 text-sm leading-relaxed text-stone-600">
                                Chat WhatsApp resmi KUA di
                                <span class="font-semibold text-stone-800">{{ \App\Support\PhoneNumber::format($waKua) }}</span>.
                                Balasan otomatis akan langsung memberi tahu status reservasi dan nomor antrean Anda.
                            </p>
                        </div>
                        <a href="{{ $waLink }}" target="_blank" rel="noopener"
                           class="mt-5 inline-flex shrink-0 items-center gap-2 rounded-lg bg-kua-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-kua-600 sm:mt-0">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.85 9.85 0 0012.04 2zm0 18.02h-.01c-1.52 0-3.01-.41-4.31-1.18l-.31-.18-3.2.84.85-3.12-.2-.32a8.2 8.2 0 01-1.26-4.38c0-4.54 3.7-8.23 8.24-8.23 2.2 0 4.27.86 5.82 2.41a8.18 8.18 0 012.41 5.83c0 4.54-3.69 8.23-8.23 8.23zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.24-.64.8-.78.97-.14.16-.29.18-.54.06-.25-.13-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.01-.38.11-.5.11-.11.25-.29.37-.43.12-.15.16-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.43h-.48c-.16 0-.43.06-.65.31-.22.25-.86.84-.86 2.05s.88 2.38 1 2.54c.12.17 1.73 2.64 4.19 3.7.59.25 1.04.4 1.4.52.59.19 1.12.16 1.55.1.47-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.15-1.18-.06-.11-.22-.17-.47-.29z"/>
                            </svg>
                            Chat WhatsApp
                        </a>
                    </div>
                </div>
            </section>
        @endif
    </main>

    <footer class="border-t border-stone-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-8 text-center text-xs text-stone-500 sm:px-6 lg:px-8">
            &copy; {{ date('Y') }} Kantor Urusan Agama. Sistem Informasi Reservasi Antrean.
        </div>
    </footer>

</body>
</html>
