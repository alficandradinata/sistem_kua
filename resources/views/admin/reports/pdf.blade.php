{{-- [SISTEM KUA] Template PDF laporan (dompdf). CSS sengaja sederhana:
     dompdf tidak mendukung flexbox/grid, jadi tata letaknya pakai tabel. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan {{ $report->type_label }} — {{ $report->period_label }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        h2 { font-size: 11px; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #d1d5db; }
        .kop { border-bottom: 2px solid #374151; padding-bottom: 8px; margin-bottom: 14px; }
        .kop .instansi { font-size: 12px; font-weight: bold; letter-spacing: .5px; }
        .kop .meta { color: #6b7280; font-size: 9px; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 5px 7px; text-align: left; }
        thead th { background: #f3f4f6; border-bottom: 1px solid #d1d5db; font-size: 9px;
                   text-transform: uppercase; letter-spacing: .4px; color: #4b5563; }
        tbody td { border-bottom: 1px solid #e5e7eb; }
        .angka { text-align: right; }
        .ringkas td { border: 1px solid #e5e7eb; width: 25%; vertical-align: top; }
        .ringkas .label { color: #6b7280; font-size: 9px; }
        .ringkas .nilai { font-size: 17px; font-weight: bold; }
        .hijau { color: #15803d; }
        .merah { color: #b91c1c; }
        .kuning { color: #a16207; }
        .kaki { margin-top: 20px; border-top: 1px solid #d1d5db; padding-top: 6px;
                color: #6b7280; font-size: 8px; }
        .kosong { padding: 14px; text-align: center; color: #6b7280; }
    </style>
</head>
<body>

<div class="kop">
    <div class="instansi">KANTOR URUSAN AGAMA</div>
    <h1>Laporan {{ $report->type_label }} Reservasi Antrean</h1>
    <div class="meta">
        Periode: {{ $periodStart->locale('id')->translatedFormat('l, j F Y') }}
        @if (! $periodStart->isSameDay($periodEnd))
            s.d. {{ $periodEnd->locale('id')->translatedFormat('l, j F Y') }}
        @endif
    </div>
</div>

<h2>Ringkasan</h2>
<table class="ringkas">
    <tr>
        <td>
            <div class="label">Total reservasi</div>
            <div class="nilai">{{ $report->total_reservations }}</div>
        </td>
        <td>
            <div class="label">Selesai dilayani</div>
            <div class="nilai hijau">{{ $report->total_completed }}</div>
            <div class="label">{{ $report->completion_rate }}% dari total</div>
        </td>
        <td>
            <div class="label">Ditolak petugas</div>
            <div class="nilai merah">{{ $report->total_rejected }}</div>
            <div class="label">{{ $report->rejection_rate }}% dari total</div>
        </td>
        <td>
            <div class="label">Dibatalkan warga</div>
            <div class="nilai">{{ $report->total_cancelled }}</div>
            <div class="label">{{ $report->cancellation_rate }}% dari total</div>
        </td>
        <td>
            <div class="label">Belum tuntas</div>
            <div class="nilai kuning">{{ $report->total_pending }}</div>
            <div class="label">menunggu / disetujui</div>
        </td>
    </tr>
</table>

<h2>Rincian per Layanan</h2>
<table>
    <thead>
        <tr>
            <th>Layanan</th>
            <th class="angka">Total</th>
            <th class="angka">Selesai</th>
            <th class="angka">Ditolak</th>
            <th class="angka">Dibatalkan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($breakdown as $row)
            <tr>
                <td>{{ $row->service_name }}</td>
                <td class="angka">{{ $row->total }}</td>
                <td class="angka hijau">{{ $row->completed }}</td>
                <td class="angka merah">{{ $row->rejected }}</td>
                <td class="angka">{{ $row->cancelled }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="kosong">Tidak ada reservasi pada periode ini.</td></tr>
        @endforelse
    </tbody>
</table>

@if ($trend && $trend->count() > 1)
    <h2>Tren Harian</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th class="angka">Total</th>
                <th class="angka">Selesai</th>
                <th class="angka">Belum tuntas</th>
                <th class="angka">Ditolak</th>
                <th class="angka">Dibatalkan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($trend as $hari)
                <tr>
                    <td>{{ $hari->date->locale('id')->translatedFormat('D, j M Y') }}</td>
                    <td class="angka">{{ $hari->total }}</td>
                    <td class="angka">{{ $hari->completed }}</td>
                    <td class="angka">{{ $hari->pending }}</td>
                    <td class="angka">{{ $hari->rejected }}</td>
                    <td class="angka">{{ $hari->cancelled }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<h2>Daftar Reservasi</h2>
<table>
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Jam</th>
            <th>Warga</th>
            <th>Layanan</th>
            <th>No. Antrean</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($reservations as $reservation)
            <tr>
                <td>{{ $reservation->formatted_date }}</td>
                <td>{{ substr((string) $reservation->reservation_time, 0, 5) }}</td>
                <td>{{ $reservation->user?->name ?? '-' }}</td>
                <td>{{ $reservation->service?->name ?? '-' }}</td>
                <td>{{ $reservation->queueDetail?->queue_number ?? '-' }}</td>
                <td>{{ $reservation->status_label }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="kosong">Belum ada reservasi pada periode ini.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="kaki">
    Dibuat oleh {{ $report->generatedBy?->name ?? '-' }}
    pada {{ $report->created_at->locale('id')->translatedFormat('j F Y H:i') }} WIB &middot;
    dicetak {{ now()->locale('id')->translatedFormat('j F Y H:i') }} WIB &middot;
    Sistem Informasi Reservasi Antrean KUA
</div>

</body>
</html>
