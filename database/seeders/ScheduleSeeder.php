<?php

namespace Database\Seeders;

use App\Models\Schedule;
use Illuminate\Database\Seeder;

/**
 * [SISTEM KUA] Jam operasional KUA (0=Minggu..6=Sabtu). Lihat PROGRESS.md.
 */
class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['day_of_week' => 0, 'open_time' => null,       'close_time' => null,       'is_active' => false],
            ['day_of_week' => 1, 'open_time' => '08:00:00', 'close_time' => '15:00:00', 'is_active' => true],
            ['day_of_week' => 2, 'open_time' => '08:00:00', 'close_time' => '15:00:00', 'is_active' => true],
            ['day_of_week' => 3, 'open_time' => '08:00:00', 'close_time' => '15:00:00', 'is_active' => true],
            ['day_of_week' => 4, 'open_time' => '08:00:00', 'close_time' => '15:00:00', 'is_active' => true],
            ['day_of_week' => 5, 'open_time' => '08:00:00', 'close_time' => '16:00:00', 'is_active' => true],
            ['day_of_week' => 6, 'open_time' => null,       'close_time' => null,       'is_active' => false],
        ];

        foreach ($rows as $row) {
            Schedule::updateOrCreate(['day_of_week' => $row['day_of_week']], $row);
        }
    }
}
