{{-- [SISTEM KUA] Layout utama area login. Diedit dari Breeze. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Judul per halaman; jatuh ke nama aplikasi kalau view tidak mengisinya --}}
        <title>{{ isset($title) ? $title.' — '.config('app.name') : config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700|lora:500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-stone-50">
            @include('layouts.navigation')

            {{-- Kepala halaman: latar putih di atas badan hangat, dipisah garis
                 tipis. Judulnya serif — inilah yang bikin halaman terasa resmi. --}}
            @isset($header)
                <header class="border-b border-stone-200 bg-white">
                    <div class="mx-auto max-w-7xl px-4 py-7 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>

            <footer class="mt-16 border-t border-stone-200 bg-white">
                <div class="mx-auto max-w-7xl px-4 py-6 text-center text-xs text-stone-500 sm:px-6 lg:px-8">
                    &copy; {{ date('Y') }} Kantor Urusan Agama &middot; Sistem Informasi Reservasi Antrean
                </div>
            </footer>
        </div>
    </body>
</html>
