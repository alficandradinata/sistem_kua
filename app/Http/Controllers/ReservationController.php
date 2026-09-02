<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\ServiceSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * [SISTEM KUA] Alur reservasi warga. Lihat PROGRESS.md.
 */
class ReservationController extends Controller
{
    /**
     * Form buat reservasi. Bila service_id + tanggal terisi (query string),
     * tampilkan slot yang tersedia untuk dipilih.
     */
    public function create(Request $request)
    {
        $services = Service::active()->orderBy('name')->get();

        $serviceId = $request->integer('service_id') ?: null;
        $date = $request->date('reservation_date')?->toDateString();

        $slots = collect();
        $dateError = null;

        if ($serviceId && $date) {
            if ($date <= now()->toDateString()) {
                $dateError = 'Tanggal reservasi harus setelah hari ini.';
            } elseif (Holiday::isHoliday($date)) {
                $dateError = 'Tanggal tersebut hari libur. Pilih tanggal lain.';
            } elseif (! Schedule::isOpenOn($date)) {
                $dateError = 'KUA tidak melayani pada hari tersebut.';
            } else {
                $slots = ServiceSlot::forService($serviceId)->active()
                    ->orderBy('slot_start_time')->get()
                    ->map(function (ServiceSlot $slot) use ($date) {
                        $slot->sisa_kuota = $slot->remainingQuota($date);

                        return $slot;
                    });
            }
        }

        return view('reservations.create', compact('services', 'slots', 'serviceId', 'date', 'dateError'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service_id' => ['required', Rule::exists('services', 'id')->where('is_active', true)],
            'reservation_date' => ['required', 'date', 'after:today'],
            'slot_id' => ['required', Rule::exists('service_slots', 'id')],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $date = Carbon::parse($data['reservation_date'])->toDateString();
        $slot = ServiceSlot::findOrFail($data['slot_id']);

        if (Holiday::isHoliday($date) || ! Schedule::isOpenOn($date)) {
            return back()->withInput()->withErrors(['reservation_date' => 'Tanggal tidak tersedia (libur atau KUA tutup).']);
        }

        if ($slot->service_id != $data['service_id'] || ! $slot->is_active) {
            return back()->withInput()->withErrors(['slot_id' => 'Slot tidak valid untuk layanan ini.']);
        }

        if (! $slot->isAvailable($date)) {
            return back()->withInput()->withErrors(['slot_id' => 'Kuota slot ini sudah penuh. Pilih slot lain.']);
        }

        $sudahAda = Reservation::forUser($request->user()->id)
            ->where('service_id', $data['service_id'])
            ->whereDate('reservation_date', $date)
            ->active()
            ->exists();

        if ($sudahAda) {
            return back()->withInput()->withErrors(['service_id' => 'Anda sudah punya reservasi aktif untuk layanan ini di tanggal tersebut.']);
        }

        $reservation = Reservation::create([
            'user_id' => $request->user()->id,
            'service_id' => $data['service_id'],
            'reservation_date' => $date,
            'reservation_time' => $slot->slot_start_time,
            'status' => Reservation::STATUS_PENDING,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('reservations.show', $reservation)
            ->with('status', 'Reservasi berhasil dibuat. Menunggu persetujuan petugas.');
    }

    public function show(Reservation $reservation)
    {
        abort_unless($reservation->user_id === auth()->id(), 403);

        $reservation->load('service', 'queueDetail');

        return view('reservations.show', compact('reservation'));
    }

    public function cancel(Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === auth()->id(), 403);
        abort_unless($reservation->canBeCancelled(), 403, 'Reservasi ini tidak bisa dibatalkan.');

        $reservation->cancel();

        return redirect()->route('dashboard')->with('status', 'Reservasi dibatalkan.');
    }
}
