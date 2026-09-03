{{-- [SISTEM KUA] Layout halaman masuk/daftar. Diedit dari Breeze. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' — '.config('app.name') : config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700|lora:500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-stone-900 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-kua-950 px-4 py-10">

            <a href="/" class="flex flex-col items-center gap-3 text-center">
                <x-application-logo class="h-14 w-14 text-emas-300" />
                <span class="font-display text-xl font-semibold tracking-tight text-white">
                    Reservasi Antrean KUA
                </span>
            </a>

            <div class="mt-8 w-full overflow-hidden rounded-2xl bg-white shadow-naik sm:max-w-md">
                {{-- Garis emas di kepala kartu: penanda "resmi" tanpa menambah bidang warna --}}
                <div class="garis-emas h-1"></div>
                <div class="px-7 py-8">
                    {{ $slot }}
                </div>
            </div>

            <p class="mt-8 text-center text-xs text-kua-200">
                Kantor Urusan Agama &middot; Sistem Informasi Reservasi Antrean
            </p>
        </div>
    </body>
</html>
