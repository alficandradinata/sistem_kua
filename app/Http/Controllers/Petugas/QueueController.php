<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\QueueDetail;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * [SISTEM KUA] Papan antrean harian: panggil, layani, selesaikan. Lihat PROGRESS.md.
 */
class QueueController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->date('date')?->toDateString() ?? today()->toDateString();

        $queues = QueueDetail::query()
            ->with(['reservation.user', 'reservation.service', 'calledBy', 'attendedBy'])
            ->forDate($date)
            ->orderBy('queue_number')
            ->get();

        return view('petugas.queues.index', [
            'date' => $date,
            'queues' => $queues,
            'waiting' => $queues->where('is_called', false)->count(),
            'called' => $queues->where('is_called', true)->whereNull('attended_at')->count(),
            'attended' => $queues->whereNotNull('attended_at')->count(),
        ]);
    }

    public function call(QueueDetail $queue): RedirectResponse
    {
        if ($queue->is_called) {
            return back()->withErrors(['queue' => 'Antrean ini sudah dipanggil.']);
        }

        $queue->markAsCalled();

        Notification::send(
            $queue->reservation->user_id,
            "Nomor antrean {$queue->queue_number} dipanggil. Silakan menuju loket."
        );

        return back()->with('status', "Antrean {$queue->queue_number} dipanggil.");
    }

    public function attend(QueueDetail $queue): RedirectResponse
    {
        $queue->markAsAttended();
        $queue->reservation->complete();

        return back()->with('status', "Antrean {$queue->queue_number} selesai dilayani.");
    }

    /**
     * Panggil antrean berikutnya yang masih menunggu pada tanggal tersebut.
     */
    public function callNext(Request $request): RedirectResponse
    {
        $date = $request->date('date')?->toDateString() ?? today()->toDateString();

        $next = QueueDetail::query()
            ->forDate($date)
            ->waiting()
            ->whereHas('reservation', fn ($q) => $q->where('status', Reservation::STATUS_APPROVED))
            ->orderBy('queue_number')
            ->first();

        if (! $next) {
            return back()->withErrors(['queue' => 'Tidak ada antrean yang menunggu.']);
        }

        return $this->call($next);
    }
}
