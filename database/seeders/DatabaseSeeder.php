<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * [SISTEM KUA] Urutan penting: user & service dulu, slot butuh service.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ServiceSeeder::class,
            ScheduleSeeder::class,
            ServiceSlotSeeder::class,
            HolidaySeeder::class,
        ]);
    }
}
