<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\QueueDetail;
use App\Models\Schedule;
use App\Models\Service;

/**
 * [SISTEM KUA] Halaman publik (tanpa login). Lihat PROGRESS.md.
 */
class PublicController extends Controller
{
    public function home()
    {
        return view('public.home', [
            'services' => Service::active()->get(),
            'schedules' => Schedule::active()->orderBy('day_of_week')->get(),
            'holidays' => Holiday::active()->upcoming()->take(5)->get(),
        ]);
    }

    /**
     * Layar antrean untuk ditayangkan di ruang tunggu KUA.
     *
     * Halaman ini TANPA login dan terbaca semua orang di ruangan, jadi isinya
     * sengaja hanya nomor antrean — tanpa nama warga dan tanpa jenis layanan.
     * Sebagian layanan KUA sensitif; jangan menambahkan kolom identitas di sini.
     */
    public function queue()
    {
        $today = today()->toDateString();

        $queues = QueueDetail::forDate($today)->orderBy('queue_number')->get();

        return view('public.queue', [
            'today' => today(),
            // Yang sedang di loket = dipanggil terakhir dan belum selesai.
            'current' => $queues->whereNull('attended_at')
                ->where('is_called', true)
                ->sortByDesc('called_at')
                ->first(),
            'next' => $queues->where('is_called', false)->take(5),
            'waiting' => $queues->where('is_called', false)->count(),
            'attended' => $queues->whereNotNull('attended_at')->count(),
        ]);
    }
}
