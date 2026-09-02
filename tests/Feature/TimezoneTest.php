<?php

namespace Tests\Feature;

use App\Models\QueueDetail;
use App\Models\Report;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\ServiceSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * [SISTEM KUA] Mengunci zona waktu WIB.
 *
 * Dengan zona UTC, tiap pukul 00:00–07:00 WIB `today()` masih memberi tanggal
 * kemarin — papan antrean, nomor antrean, validasi tanggal, dan periode laporan
 * ikut salah sehari. Test ini memakai instan 2026-09-01 18:00 UTC yang berarti
 * 2026-09-02 pukul 01:00 WIB: dini hari, saat perbedaannya paling terasa.
 */
class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    /** 2026-09-01 18:00 UTC = 2026-09-02 01:00 WIB */
    private const DINI_HARI_WIB = '2026-09-01 18:00:00';

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function bekukanDiniHariWib(): void
    {
        Carbon::setTestNow(Carbon::parse(self::DINI_HARI_WIB, 'UTC'));
    }

    private function service(): Service
    {
        return Service::create([
            'name' => 'Pendaftaran Nikah', 'description' => 'x',
            'duration' => 60, 'fee' => 0, 'is_active' => true,
        ]);
    }

    public function test_app_timezone_is_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
    }

    public function test_today_follows_wib_not_utc(): void
    {
        $this->bekukanDiniHariWib();

        $this->assertSame('2026-09-02', today()->toDateString());
        $this->assertSame('2026-09-02 01:00:00', now()->toDateTimeString());
    }

    public function test_today_scope_finds_reservation_of_current_wib_date(): void
    {
        $this->bekukanDiniHariWib();

        $service = $this->service();
        $warga = User::factory()->create(['role' => User::ROLE_WARGA]);

        // Reservasi hari ini menurut WIB (2 Sep), bukan menurut UTC (1 Sep).
        Reservation::create([
            'user_id' => $warga->id, 'service_id' => $service->id,
            'reservation_date' => '2026-09-02', 'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_APPROVED,
        ]);
        Reservation::create([
            'user_id' => $warga->id, 'service_id' => $service->id,
            'reservation_date' => '2026-09-01', 'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_APPROVED,
        ]);

        $this->assertSame(1, Reservation::today()->count());
        $this->assertSame('2026-09-02', Reservation::today()->first()->reservation_date->toDateString());
    }

    public function test_queue_number_resets_per_wib_date(): void
    {
        $this->bekukanDiniHariWib();

        $service = $this->service();
        $warga = User::factory()->create(['role' => User::ROLE_WARGA]);

        $kemarin = Reservation::create([
            'user_id' => $warga->id, 'service_id' => $service->id,
            'reservation_date' => '2026-09-01', 'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_APPROVED,
        ]);
        QueueDetail::create([
            'reservation_id' => $kemarin->id,
            'queue_number' => QueueDetail::generateNumber('2026-09-01'),
        ]);

        $hariIni = Reservation::create([
            'user_id' => $warga->id, 'service_id' => $service->id,
            'reservation_date' => today()->toDateString(), 'reservation_time' => '09:00:00',
            'status' => Reservation::STATUS_PENDING,
        ]);

        // Nomor antrean hari ini mulai dari 1 lagi, tidak melanjutkan nomor kemarin.
        $queue = $hariIni->approveAndIssueQueue();

        $this->assertSame('A-001', $queue->queue_number);
    }

    public function test_daily_report_period_uses_wib_date(): void
    {
        $this->bekukanDiniHariWib();

        $service = $this->service();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Reservation::create([
            'user_id' => User::factory()->create()->id, 'service_id' => $service->id,
            'reservation_date' => '2026-09-02', 'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_COMPLETED,
        ]);

        $this->artisan('laporan:buat')->assertSuccessful();

        $report = Report::first();

        $this->assertSame('2026-09-02', $report->report_date->toDateString());
        $this->assertSame(1, $report->total_reservations);
    }

    public function test_reservation_form_rejects_today_wib_as_reservation_date(): void
    {
        $this->bekukanDiniHariWib();

        $service = $this->service();
        $warga = User::factory()->create(['role' => User::ROLE_WARGA]);

        // KUA dibuka pada hari itu (2 Sep 2026 = Rabu) supaya satu-satunya aturan
        // yang bisa menolak adalah aturan tanggalnya sendiri, bukan "KUA tutup".
        Schedule::create([
            'day_of_week' => Carbon::parse('2026-09-02')->dayOfWeek,
            'open_time' => '08:00:00',
            'close_time' => '15:00:00',
            'is_active' => true,
        ]);

        // Slot dibuat sungguhan supaya slot_id-nya valid.
        $slot = ServiceSlot::create([
            'service_id' => $service->id,
            'slot_start_time' => '08:00:00',
            'slot_duration' => 60,
            'quota_per_day' => 5,
            'is_active' => true,
        ]);

        // Tanggal ditulis literal, bukan today(), supaya test ini tetap membedakan
        // zona waktu: 2 Sep adalah "hari ini" menurut WIB (harus ditolak), tetapi
        // "besok" menurut UTC (akan lolos) — di situlah bugnya ketahuan.
        $this->actingAs($warga)->post(route('reservations.store'), [
            'service_id' => $service->id,
            'reservation_date' => '2026-09-02',
            'slot_id' => $slot->id,
        ])->assertSessionHasErrors('reservation_date');

        $this->assertDatabaseCount('reservations', 0);
    }
}
