{{-- [SISTEM KUA] Layar antrean ruang tunggu. Nomor saja — tanpa nama warga
     dan tanpa jenis layanan, karena halaman ini terbaca semua orang di ruangan. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Muat ulang berkala: papan ini menyala berjam-jam tanpa ada yang menyentuh
         keyboard, jadi tidak boleh bergantung pada klik atau JavaScript. --}}
    <meta http-equiv="refresh" content="15">
    <title>Layar Antrean KUA</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-900 font-sans text-white antialiased">

    <div class="mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-8">

        <header class="flex flex-wrap items-baseline justify-between gap-3 border-b border-white/10 pb-5">
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Antrean Layanan KUA</h1>
            <p class="text-base text-slate-400 sm:text-lg">
                {{ $today->locale('id')->translatedFormat('l, j F Y') }}
            </p>
        </header>

        {{-- Nomor yang sedang dilayani: satu-satunya hal yang harus terbaca dari
             seberang ruangan, jadi ukurannya jauh di atas elemen lain. --}}
        <section class="flex flex-1 flex-col items-center justify-center py-10">
            <p class="text-lg uppercase tracking-[0.2em] text-slate-400 sm:text-xl">Nomor Dipanggil</p>

            @if ($current)
                <p class="mt-4 font-mono text-[6rem] font-bold leading-none tracking-tight text-white sm:text-[10rem]">
                    {{ $current->queue_number }}
                </p>
                <p class="mt-5 text-lg text-slate-400 sm:text-xl">
                    Silakan menuju loket pelayanan
                </p>
            @else
                <p class="mt-4 font-mono text-[6rem] font-bold leading-none text-slate-700 sm:text-[10rem]">—</p>
                <p class="mt-5 text-lg text-slate-400 sm:text-xl">
                    Belum ada nomor yang dipanggil hari ini
                </p>
            @endif
        </section>

        {{-- Antrean berikutnya --}}
        <section class="border-t border-white/10 pt-6">
            <p class="text-sm uppercase tracking-[0.15em] text-slate-400">Nomor Berikutnya</p>

            @if ($next->isEmpty())
                <p class="mt-3 text-lg text-slate-500">Tidak ada nomor yang menunggu.</p>
            @else
                <div class="mt-3 flex flex-wrap gap-3">
                    @foreach ($next as $queue)
                        <span class="rounded-lg bg-white/10 px-5 py-3 font-mono text-2xl font-semibold tracking-tight sm:text-3xl">
                            {{ $queue->queue_number }}
                        </span>
                    @endforeach
                </div>
            @endif
        </section>

        <footer class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-5
                       text-base text-slate-400 sm:text-lg">
            <div class="flex flex-wrap gap-6">
                <span><span class="font-semibold text-white">{{ $waiting }}</span> menunggu</span>
                <span><span class="font-semibold text-white">{{ $attended }}</span> selesai dilayani</span>
            </div>
            <span class="text-sm text-slate-500">
                Layar diperbarui otomatis &middot; {{ now()->locale('id')->translatedFormat('H:i') }} WIB
            </span>
        </footer>

    </div>

</body>
</html>
