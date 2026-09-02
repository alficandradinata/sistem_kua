<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HolidayRequest;
use App\Models\Holiday;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * [SISTEM KUA] Master data hari libur. Lihat PROGRESS.md.
 */
class HolidayController extends Controller
{
    public function index(Request $request): View
    {
        $year = $request->integer('year') ?: now()->year;

        return view('admin.holidays.index', [
            'year' => $year,
            'years' => range(now()->year - 1, now()->year + 2),
            'holidays' => Holiday::inYear($year)->orderBy('holiday_date')->get(),
        ]);
    }

    public function store(HolidayRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $holiday = Holiday::create($data);

        return back()->with('status', $this->clashWarning($data['holiday_date'])
            ?? "Hari libur {$holiday->formatted_date} ditambahkan.");
    }

    public function update(HolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update($request->validated());

        return back()->with('status', 'Hari libur diperbarui.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('status', 'Hari libur dihapus.');
    }

    /**
     * Peringatkan admin bila tanggal libur baru bentrok dengan reservasi yang
     * terlanjur disetujui — sistem tidak membatalkannya otomatis.
     */
    private function clashWarning(string $date): ?string
    {
        $count = Reservation::forDate($date)
            ->where('status', '!=', Reservation::STATUS_CANCELLED)
            ->count();

        return $count > 0
            ? "Hari libur ditambahkan, TAPI sudah ada {$count} reservasi aktif pada tanggal itu. "
                .'Silakan hubungi warga terkait atau tolak reservasinya lewat menu Verifikasi.'
            : null;
    }
}
