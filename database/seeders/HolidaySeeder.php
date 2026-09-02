<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

/**
 * [SISTEM KUA] Contoh hari libur nasional 2026. Lihat PROGRESS.md.
 */
class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['holiday_date' => '2026-01-01', 'description' => 'Tahun Baru Masehi'],
            ['holiday_date' => '2026-03-19', 'description' => 'Hari Raya Nyepi'],
            ['holiday_date' => '2026-05-01', 'description' => 'Hari Buruh Internasional'],
            ['holiday_date' => '2026-08-17', 'description' => 'Hari Kemerdekaan RI'],
            ['holiday_date' => '2026-12-25', 'description' => 'Hari Raya Natal'],
        ];

        foreach ($holidays as $data) {
            Holiday::updateOrCreate(
                ['holiday_date' => $data['holiday_date']],
                [...$data, 'is_active' => true],
            );
        }
    }
}
