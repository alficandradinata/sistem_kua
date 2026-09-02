<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\QueueDetail;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\ServiceSlot;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * [SISTEM KUA] Dashboard per peran + redirect pasca-login. Lihat PROGRESS.md.
 */
class DashboardController extends Controller
{
    /**
     * Arahkan user ke dashboard sesuai perannya.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route(auth()->user()->homeRoute());
    }

    public function warga()
    {
        return view('dashboard', [
            'reservations' => auth()->user()->reservations()->with('service')->latest()->get(),
        ]);
    }

    public function petugas()
    {
        return view('petugas.dashboard', [
            'todayCount' => Reservation::today()->count(),
            'pendingCount' => Reservation::pending()->count(),
            'waitingQueue' => QueueDetail::waiting()->count(),
            'latestPending' => Reservation::pending()
                ->with(['user', 'service'])
                ->orderBy('reservation_date')
                ->limit(5)
                ->get(),
        ]);
    }

    public function admin()
    {
        return view('admin.dashboard', [
            'serviceCount' => Service::count(),
            'slotCount' => ServiceSlot::count(),
            'reservationCount' => Reservation::count(),
            'wargaCount' => User::role(User::ROLE_WARGA)->count(),
            'petugasCount' => User::role(User::ROLE_PETUGAS)->count(),
            'schedules' => Schedule::orderBy('day_of_week')->get(),
            'holidays' => Holiday::active()->upcoming()->take(5)->get(),
        ]);
    }
}
