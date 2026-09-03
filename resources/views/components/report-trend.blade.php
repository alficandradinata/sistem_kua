{{-- [SISTEM KUA] Grafik batang bertumpuk: tren reservasi harian dalam satu periode laporan. --}}
@props(['trend'])

@php
    // Warna status tetap (bukan warna seri) — selalu didampingi label di legenda.
    $colors = [
        'completed' => '#0ca30c',   // selesai
        'pending' => '#fab219',     // belum tuntas
        'rejected' => '#d03b3b',    // ditolak petugas
        'cancelled' => '#8a8f98',   // dibatalkan warga
    ];

    $plotHeight = 150;                       // tinggi area plot (px), di luar label sumbu
    $max = max(1, (int) $trend->max('total'));
    $puncak = $trend->sortByDesc('total')->first();   // hanya puncak yang diberi label langsung
    $adaData = $trend->sum('total') > 0;

    // Urutan tumpukan dari bawah ke atas; ujung data (paling atas) yang dibulatkan.
    $urutan = ['completed', 'pending', 'rejected', 'cancelled'];
@endphp

<div class="overflow-hidden rounded-lg bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-5 py-3">
        <h3 class="text-sm font-semibold text-stone-700">Tren Reservasi Harian</h3>

        {{-- Legenda: identitas tidak pernah lewat warna saja --}}
        <div class="flex flex-wrap items-center gap-4 text-xs text-stone-600">
            @foreach (['completed' => 'Selesai', 'pending' => 'Belum tuntas', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'] as $key => $label)
                <span class="inline-flex items-center gap-1.5">
                    <span class="inline-block h-2.5 w-2.5 rounded-sm" style="background: {{ $colors[$key] }}"></span>
                    {{ $label }}
                </span>
            @endforeach
        </div>
    </div>

    @if (! $adaData)
        <p class="px-5 py-10 text-center text-sm text-stone-500">
            Tidak ada reservasi pada periode ini, jadi tidak ada tren untuk digambar.
        </p>
    @else
        <div class="px-5 pb-4 pt-6">
            {{-- pt-5 memberi ruang label puncak; tanpa itu scroll container memotongnya --}}
            <div class="overflow-x-auto pt-5">
                <div class="flex items-end gap-1 border-b border-stone-200"
                     style="min-width: {{ max(320, $trend->count() * 22) }}px">
                    @foreach ($trend as $hari)
                        @php
                            // Segmen teratas yang punya nilai = ujung data, itu yang dibulatkan.
                            $terisi = array_values(array_filter($urutan, fn ($k) => $hari->{$k} > 0));
                            $ujung = end($terisi) ?: null;
                        @endphp

                        <div class="group relative flex flex-1 flex-col items-center">
                            {{-- Kolom hover setinggi plot supaya area sentuhnya lega --}}
                            <div class="flex w-full flex-col items-center justify-end" style="height: {{ $plotHeight }}px">
                                @if ($hari === $puncak && $hari->total > 0)
                                    <span class="mb-1 text-center text-[10px] font-semibold tabular-nums text-stone-500">
                                        {{ $hari->total }}
                                    </span>
                                @endif

                                {{-- Batang tipis: lebar dibatasi, bukan selebar kolom --}}
                                <div class="flex w-full max-w-[20px] flex-col justify-end gap-[2px]">
                                    @foreach (array_reverse($urutan) as $key)
                                        @if ($hari->{$key} > 0)
                                            <div class="{{ $key === $ujung ? 'rounded-t-[4px]' : '' }}"
                                                 style="height: {{ max(3, round($hari->{$key} / $max * ($plotHeight - 20))) }}px;
                                                        background: {{ $colors[$key] }}"></div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            {{-- Tooltip: melengkapi, bukan satu-satunya cara membaca angka
                                 (tabel di bawah halaman ini memuat seluruh datanya).
                                 Diletakkan DI DALAM area plot — kalau ditaruh di atasnya,
                                 container scroll horizontal ikut memotongnya. --}}
                            <div class="pointer-events-none absolute left-1/2 top-0 z-10 hidden w-36 -translate-x-1/2
                                        rounded-md bg-stone-900 px-3 py-2 text-left text-[11px] leading-4 text-white
                                        shadow-lg group-hover:block
                                        group-first:left-0 group-first:translate-x-0
                                        group-last:left-auto group-last:right-0 group-last:translate-x-0">
                                <p class="font-semibold">{{ $hari->date->locale('id')->translatedFormat('D, j M') }}</p>
                                <p class="mt-1 text-stone-300">Total {{ $hari->total }} reservasi</p>
                                <p>Selesai {{ $hari->completed }} &middot; Belum tuntas {{ $hari->pending }}</p>
                                <p>Ditolak {{ $hari->rejected }} &middot; Dibatalkan {{ $hari->cancelled }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Sumbu X: tanggal, sejajar dengan batangnya --}}
                <div class="flex gap-1 pt-1.5" style="min-width: {{ max(320, $trend->count() * 22) }}px">
                    @foreach ($trend as $hari)
                        <span class="flex-1 text-center text-[10px] tabular-nums text-stone-400">
                            {{ $hari->date->day }}
                        </span>
                    @endforeach
                </div>
            </div>

            <p class="mt-3 text-xs text-stone-400">
                Tinggi batang mengikuti hari tersibuk ({{ $max }} reservasi). Arahkan kursor ke satu hari untuk rinciannya.
            </p>
        </div>
    @endif
</div>
