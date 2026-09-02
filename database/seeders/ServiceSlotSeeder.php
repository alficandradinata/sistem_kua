<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceSlot;
use Illuminate\Database\Seeder;

/**
 * [SISTEM KUA] Slot antrean per layanan (kuota harian). Lihat PROGRESS.md.
 */
class ServiceSlotSeeder extends Seeder
{
    public function run(): void
    {
        $times = ['08:00:00', '09:00:00', '10:00:00', '13:00:00'];

        foreach (Service::all() as $service) {
            foreach ($times as $time) {
                ServiceSlot::updateOrCreate(
                    ['service_id' => $service->id, 'slot_start_time' => $time],
                    ['quota_per_day' => 5, 'slot_duration' => 60, 'is_active' => true],
                );
            }
        }
    }
}
