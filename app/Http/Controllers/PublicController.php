<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
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
}
