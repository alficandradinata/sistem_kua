<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
// Facade scheduler bawaan Laravel — bukan App\Models\Schedule (jam operasional KUA).
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// [SISTEM KUA] Pengingat H-1 dikirim sore hari, saat warga masih sempat bersiap.
Schedule::command('pengingat:reservasi')
    ->dailyAt('17:00')
    ->withoutOverlapping();

// [SISTEM KUA] Laporan rekap dibuat otomatis di akhir tiap periode.
// Jalankan `php artisan schedule:work` (dev) atau cron `schedule:run` tiap menit (produksi).
Schedule::command('laporan:buat --type=daily')
    ->dailyAt('23:55')
    ->withoutOverlapping();

Schedule::command('laporan:buat --type=weekly')
    ->weeklyOn(0, '23:57')          // Minggu malam, akhir pekan berjalan
    ->withoutOverlapping();

Schedule::command('laporan:buat --type=monthly')
    ->lastDayOfMonth('23:59')
    ->withoutOverlapping();
