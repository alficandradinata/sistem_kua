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
    </main>

    <footer class="border-t border-gray-100 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-sm text-gray-500">
            &copy; {{ date('Y') }} Kantor Urusan Agama. Sistem Informasi Reservasi Antrean.
        </div>
    </footer>

</body>
</html>
