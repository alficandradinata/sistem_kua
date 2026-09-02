<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceSlotRequest;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\ServiceSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * [SISTEM KUA] Master data slot & kuota antrean. Lihat PROGRESS.md.
 */
class ServiceSlotController extends Controller
{
    public function index(Request $request): View
    {
        $serviceId = $request->integer('service_id') ?: null;

        return view('admin.slots.index', [
            'services' => Service::orderBy('name')->get(),
            'serviceId' => $serviceId,
            'slots' => ServiceSlot::with('service')
                ->when($serviceId, fn ($q, $id) => $q->forService($id))
                ->orderBy('service_id')->orderBy('slot_start_time')
                ->get(),
        ]);
    }

    public function store(ServiceSlotRequest $request): RedirectResponse
    {
        ServiceSlot::create($request->validated());

        return back()->with('status', 'Slot antrean ditambahkan.');
    }

    public function update(ServiceSlotRequest $request, ServiceSlot $slot): RedirectResponse
    {
        $data = $request->validated();

        // Mengubah jam mulai memutus kaitan dengan reservasi yang sudah ada di jam lama,
        // karena kuota dihitung dari kecocokan jam. Cegah supaya kuota tidak salah hitung.
        if ($data['slot_start_time'] !== $slot->slot_start_time && $this->hasActiveReservations($slot)) {
            return back()->withErrors([
                'slot_start_time' => 'Jam mulai tidak bisa diubah karena sudah ada reservasi aktif pada jam ini. '
                    .'Nonaktifkan slot lama lalu buat slot baru.',
            ]);
        }

        $slot->update($data);

        return back()->with('status', 'Slot antrean diperbarui.');
    }

    public function destroy(ServiceSlot $slot): RedirectResponse
    {
        if ($this->hasActiveReservations($slot)) {
            return back()->withErrors([
                'slot' => 'Slot ini masih dipakai reservasi aktif. Nonaktifkan saja, jangan dihapus.',
            ]);
        }

        $slot->delete();

        return back()->with('status', 'Slot antrean dihapus.');
    }

    private function hasActiveReservations(ServiceSlot $slot): bool
    {
        return Reservation::query()
            ->where('service_id', $slot->service_id)
            ->where('reservation_time', $slot->slot_start_time)
            ->whereDate('reservation_date', '>=', today())
            ->active()
            ->exists();
    }
}
