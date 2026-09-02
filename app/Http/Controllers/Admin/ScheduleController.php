<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleRequest;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * [SISTEM KUA] Jam operasional KUA. Barisnya tetap 7 (Minggu–Sabtu), jadi hanya
 * ada aksi ubah — tidak ada tambah/hapus. Lihat PROGRESS.md.
 */
class ScheduleController extends Controller
{
    public function index(): View
    {
        return view('admin.schedules.index', [
            'schedules' => $this->ensureAllDaysExist(),
            'days' => Schedule::DAYS,
        ]);
    }

    public function update(ScheduleRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            foreach ($request->validated()['days'] as $dayOfWeek => $data) {
                Schedule::updateOrCreate(
                    ['day_of_week' => (int) $dayOfWeek],
                    [
                        'is_active' => $data['is_active'],
                        'open_time' => $data['is_active'] ? $data['open_time'].':00' : null,
                        'close_time' => $data['is_active'] ? $data['close_time'].':00' : null,
                    ],
                );
            }
        });

        return back()->with('status', 'Jam operasional diperbarui.');
    }

    /**
     * Pastikan 7 hari selalu ada barisnya supaya form tidak pernah bolong.
     *
     * @return Collection<int, Schedule>
     */
    private function ensureAllDaysExist()
    {
        $existing = Schedule::orderBy('day_of_week')->get()->keyBy('day_of_week');

        return collect(array_keys(Schedule::DAYS))->mapWithKeys(fn (int $day) => [
            $day => $existing->get($day) ?? new Schedule([
                'day_of_week' => $day,
                'is_active' => false,
            ]),
        ]);
    }
}
