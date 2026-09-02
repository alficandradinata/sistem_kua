<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Petugas\RejectReservationRequest;
use App\Models\Reservation;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * [SISTEM KUA] Verifikasi reservasi oleh petugas KUA. Lihat PROGRESS.md.
 */
class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'status' => $request->string('status')->toString() ?: null,
            'date' => $request->date('date')?->toDateString(),
            'service_id' => $request->integer('service_id') ?: null,
        ];

        $reservations = Reservation::query()
            ->with(['user', 'service', 'queueDetail', 'approvedBy', 'rejectedBy'])
            ->when($filters['status'], fn ($q, $s) => $q->status($s))
            ->when($filters['date'], fn ($q, $d) => $q->forDate($d))
            ->when($filters['service_id'], fn ($q, $id) => $q->where('service_id', $id))
            ->orderBy('reservation_date')
            ->orderBy('reservation_time')
            ->paginate(15)
            ->withQueryString();

        return view('petugas.reservations.index', [
            'reservations' => $reservations,
            'services' => Service::orderBy('name')->get(),
            'filters' => $filters,
            'statuses' => Reservation::STATUSES,
        ]);
    }

    public function approve(Reservation $reservation): RedirectResponse
    {
        if (! $reservation->isPending()) {
            return back()->withErrors(['status' => 'Hanya reservasi berstatus Menunggu yang bisa disetujui.']);
        }

        $queue = $reservation->approveAndIssueQueue();

        return back()->with('status', "Reservasi disetujui. Nomor antrean {$queue->queue_number} diterbitkan.");
    }

    public function reject(RejectReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        if (! $reservation->isPending()) {
            return back()->withErrors(['status' => 'Hanya reservasi berstatus Menunggu yang bisa ditolak.']);
        }

        $reservation->reject($request->validated()['reason']);

        return back()->with('status', 'Reservasi ditolak dan warga sudah diberi notifikasi.');
    }
}
