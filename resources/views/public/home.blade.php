{{-- [SISTEM KUA] Landing page publik. Lihat PROGRESS.md. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservasi Antrean KUA</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased bg-gray-50">

    {{-- Header --}}
    <header class="bg-white border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <span class="font-semibold text-lg">Reservasi Antrean KUA</span>
            <nav class="flex items-center gap-3 text-sm">
                <a href="{{ route('queue.display') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">
                    Layar Antrean
                </a>
                @auth
                    <a href="{{ route(auth()->user()->homeRoute()) }}"
                       class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">Masuk</a>
                    <a href="{{ route('register') }}"
                       class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Daftar</a>
                @endauth
            </nav>
        </div>
    </header>

    {{-- Hero --}}
    <section class="bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight">
                Ambil Antrean Layanan KUA Secara Online
            </h1>
            <p class="mt-4 text-gray-600 max-w-2xl mx-auto">
                Pesan jadwal kedatangan Anda untuk layanan pernikahan, rujuk, legalisir, dan konsultasi.
                Tanpa antre lama di loket.
            </p>
            @guest
                <a href="{{ route('register') }}"
                   class="inline-block mt-8 px-6 py-3 bg-indigo-600 text-white rounded-md font-semibold hover:bg-indigo-700">
                    Mulai Reservasi
                </a>
            @endguest
        </div>
    </section>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-14 space-y-16">

        {{-- Layanan --}}
        <section>
            <h2 class="text-2xl font-semibold mb-6">Layanan Tersedia</h2>
            @if ($services->isEmpty())
                <p class="text-gray-500">Belum ada layanan.</p>
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $service)
                        <div class="bg-white rounded-lg shadow-sm p-6 flex flex-col">
                            <h3 class="font-semibold text-lg">{{ $service->name }}</h3>
                            <p class="mt-2 text-sm text-gray-600 flex-1">{{ $service->description }}</p>
                            <dl class="mt-4 flex items-center gap-4 text-sm">
                                <div>
                                    <dt class="text-gray-400">Perkiraan durasi</dt>
                                    <dd class="font-medium">{{ $service->duration_label }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-400">Biaya</dt>
                                    <dd class="font-medium">{{ $service->formatted_fee }}</dd>
                                </div>
                            </dl>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Jadwal --}}
        <section class="grid gap-10 lg:grid-cols-2">
            <div>
                <h2 class="text-2xl font-semibold mb-6">Jam Pelayanan</h2>
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <table class="min-w-full text-sm">
                        <tbody>
                            @foreach ($schedules as $schedule)
                                <tr class="border-b last:border-0">
                                    <td class="py-3 px-5 font-medium">{{ $schedule->day_name }}</td>
                                    <td class="py-3 px-5 text-right text-gray-600">{{ $schedule->operational_hours }}</td>
                                </tr>
                            @endforeach
                            @if ($schedules->isEmpty())
                                <tr><td class="py-3 px-5 text-gray-500">Jadwal belum diatur.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Hari libur --}}
            <div>
                <h2 class="text-2xl font-semibold mb-6">Hari Libur Mendatang</h2>
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <ul class="divide-y">
                        @forelse ($holidays as $holiday)
                            <li class="py-3 px-5 flex items-center justify-between text-sm">
                                <span>{{ $holiday->description }}</span>
                                <span class="text-gray-500">{{ $holiday->formatted_date }}</span>
                            </li>
                        @empty
                            <li class="py-3 px-5 text-sm text-gray-500">Tidak ada hari libur terdekat.</li>
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
            <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
                <div class="rounded-lg bg-green-50 border border-green-200 p-6 sm:flex sm:items-center sm:justify-between sm:gap-6">
                    <div>
                        <h2 class="text-xl font-semibold text-green-900">Butuh koordinasi cepat?</h2>
                        <p class="mt-1 text-sm text-green-800">
                            Chat WhatsApp resmi KUA di
                            <span class="font-medium">{{ \App\Support\PhoneNumber::format($waKua) }}</span>.
                            Balasan otomatis akan langsung memberi tahu status reservasi dan nomor antrean Anda.
                        </p>
                    </div>
                    <a href="{{ $waLink }}" target="_blank" rel="noopener"
                       class="mt-4 sm:mt-0 inline-flex shrink-0 items-center gap-2 rounded-md bg-green-600 px-5 py-3 text-sm font-semibold text-white hover:bg-green-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.85 9.85 0 0012.04 2zm0 18.02h-.01c-1.52 0-3.01-.41-4.31-1.18l-.31-.18-3.2.84.85-3.12-.2-.32a8.2 8.2 0 01-1.26-4.38c0-4.54 3.7-8.23 8.24-8.23 2.2 0 4.27.86 5.82 2.41a8.18 8.18 0 012.41 5.83c0 4.54-3.69 8.23-8.23 8.23zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.24-.64.8-.78.97-.14.16-.29.18-.54.06-.25-.13-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.01-.38.11-.5.11-.11.25-.29.37-.43.12-.15.16-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.43h-.48c-.16 0-.43.06-.65.31-.22.25-.86.84-.86 2.05s.88 2.38 1 2.54c.12.17 1.73 2.64 4.19 3.7.59.25 1.04.4 1.4.52.59.19 1.12.16 1.55.1.47-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.15-1.18-.06-.11-.22-.17-.47-.29z"/>
                        </svg>
                        Chat WhatsApp
                    </a>
                </div>
            </section>
        @endif
    </main>

    <footer class="border-t border-gray-100 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-sm text-gray-500">
            &copy; {{ date('Y') }} Kantor Urusan Agama. Sistem Informasi Reservasi Antrean.
        </div>
    </footer>

</body>
</html>
