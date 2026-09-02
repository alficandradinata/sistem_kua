<?php

// [SISTEM KUA] File ini ditimpa Breeze lalu disesuaikan. Lihat PROGRESS.md.

use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ServiceSlotController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Petugas\QueueController;
use App\Http\Controllers\Petugas\ReservationController as PetugasReservationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Warga
    Route::get('/dashboard', [DashboardController::class, 'warga'])->name('dashboard');

    Route::get('/reservasi/buat', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('/reservasi', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/reservasi/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::patch('/reservasi/{reservation}/batal', [ReservationController::class, 'cancel'])->name('reservations.cancel');

    // Petugas KUA (admin juga boleh masuk)
    Route::middleware('role:petugas,admin')
        ->prefix('petugas')->name('petugas.')
        ->group(function () {
            Route::get('/', [DashboardController::class, 'petugas'])->name('dashboard');

            Route::get('reservasi', [PetugasReservationController::class, 'index'])->name('reservations.index');
            Route::patch('reservasi/{reservation}/setujui', [PetugasReservationController::class, 'approve'])->name('reservations.approve');
            Route::patch('reservasi/{reservation}/tolak', [PetugasReservationController::class, 'reject'])->name('reservations.reject');

            Route::get('antrean', [QueueController::class, 'index'])->name('queues.index');
            Route::patch('antrean/panggil-berikutnya', [QueueController::class, 'callNext'])->name('queues.callNext');
            Route::patch('antrean/{queue}/panggil', [QueueController::class, 'call'])->name('queues.call');
            Route::patch('antrean/{queue}/layani', [QueueController::class, 'attend'])->name('queues.attend');
        });

    // Administrator — master data
    Route::middleware('role:admin')
        ->prefix('admin')->name('admin.')
        ->group(function () {
            Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');

            Route::resource('layanan', ServiceController::class)
                ->parameters(['layanan' => 'service'])->except('show')->names('services');

            Route::get('jadwal', [ScheduleController::class, 'index'])->name('schedules.index');
            Route::put('jadwal', [ScheduleController::class, 'update'])->name('schedules.update');

            Route::resource('slot', ServiceSlotController::class)
                ->parameters(['slot' => 'slot'])->only(['index', 'store', 'update', 'destroy'])->names('slots');

            Route::resource('libur', HolidayController::class)
                ->parameters(['libur' => 'holiday'])->only(['index', 'store', 'update', 'destroy'])->names('holidays');

            Route::resource('pengguna', UserController::class)
                ->parameters(['pengguna' => 'user'])->except('show')->names('users');

            Route::get('laporan', [ReportController::class, 'index'])->name('reports.index');
            Route::post('laporan', [ReportController::class, 'store'])->name('reports.store');
            Route::get('laporan/{report}', [ReportController::class, 'show'])->name('reports.show');
            Route::get('laporan/{report}/ekspor', [ReportController::class, 'export'])->name('reports.export');
            Route::delete('laporan/{report}', [ReportController::class, 'destroy'])->name('reports.destroy');
        });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
