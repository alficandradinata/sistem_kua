<?php

namespace Tests\Feature\Admin;

use App\Models\Report;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * [SISTEM KUA] Perintah artisan pembuat laporan otomatis (dipakai scheduler).
 */
class GenerateReportCommandTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = Service::create([
            'name' => 'Pendaftaran Nikah', 'description' => 'x',
            'duration' => 60, 'fee' => 0, 'is_active' => true,
        ]);
    }

    private function makeReservation(string $date, string $status, string $time = '08:00:00'): void
    {
        Reservation::create([
            'user_id' => User::factory()->create()->id,
            'service_id' => $this->service->id,
            'reservation_date' => $date,
            'reservation_time' => $time,
            'status' => $status,
        ]);
    }

    public function test_command_generates_daily_report_for_today(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->makeReservation(today()->toDateString(), Reservation::STATUS_COMPLETED);
        $this->makeReservation(today()->toDateString(), Reservation::STATUS_CANCELLED, '09:00:00');

        $this->artisan('laporan:buat')->assertSuccessful();

        $report = Report::first();

        $this->assertSame(Report::TYPE_DAILY, $report->report_type);
        $this->assertSame(today()->toDateString(), $report->report_date->toDateString());
        $this->assertSame(2, $report->total_reservations);
        $this->assertSame(1, $report->total_completed);
        $this->assertSame($admin->id, $report->generated_by);
    }

    public function test_command_accepts_type_and_date_options(): void
    {
        User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->makeReservation('2026-03-09', Reservation::STATUS_COMPLETED);
        $this->makeReservation('2026-03-15', Reservation::STATUS_PENDING);

        $this->artisan('laporan:buat', ['--type' => 'weekly', '--date' => '2026-03-12'])
            ->assertSuccessful();

        $report = Report::first();

        $this->assertSame(Report::TYPE_WEEKLY, $report->report_type);
        $this->assertSame('2026-03-09', $report->report_date->toDateString());
        $this->assertSame(2, $report->total_reservations);
    }

    public function test_command_rejects_unknown_type(): void
    {
        User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->artisan('laporan:buat', ['--type' => 'tahunan'])->assertFailed();

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_command_fails_without_admin_account(): void
    {
        User::factory()->create(['role' => User::ROLE_WARGA]);

        $this->artisan('laporan:buat')->assertFailed();

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_command_does_not_duplicate_existing_period(): void
    {
        User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->makeReservation(today()->toDateString(), Reservation::STATUS_COMPLETED);

        $this->artisan('laporan:buat')->assertSuccessful();
        $this->makeReservation(today()->toDateString(), Reservation::STATUS_CANCELLED, '09:00:00');
        $this->artisan('laporan:buat')->assertSuccessful();

        $this->assertDatabaseCount('reports', 1);
        $this->assertSame(2, Report::first()->total_reservations);
    }

    public function test_daily_trend_covers_every_day_of_period(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->makeReservation('2026-03-09', Reservation::STATUS_COMPLETED);
        $this->makeReservation('2026-03-09', Reservation::STATUS_CANCELLED, '09:00:00');
        $this->makeReservation('2026-03-11', Reservation::STATUS_PENDING);

        $report = Report::generateFor(Report::TYPE_WEEKLY, '2026-03-12', $admin->id);
        $trend = $report->dailyTrend();

        $this->assertCount(7, $trend);                       // Senin s.d. Minggu
        $this->assertSame('2026-03-09', $trend->first()->date->toDateString());

        $this->assertSame(2, $trend[0]->total);
        $this->assertSame(1, $trend[0]->completed);
        $this->assertSame(1, $trend[0]->cancelled);
        $this->assertSame(0, $trend[1]->total);              // hari kosong tetap ada
        $this->assertSame(1, $trend[2]->pending);
    }

    public function test_report_page_shows_trend_chart_for_multi_day_period(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->makeReservation(Carbon::parse('2026-03-09')->toDateString(), Reservation::STATUS_COMPLETED);

        $weekly = Report::generateFor(Report::TYPE_WEEKLY, '2026-03-12', $admin->id);
        $daily = Report::generateFor(Report::TYPE_DAILY, '2026-03-09', $admin->id);

        $this->actingAs($admin)->get(route('admin.reports.show', $weekly))
            ->assertOk()
            ->assertSee('Tren Reservasi Harian');

        // Periode sehari tidak perlu grafik tren.
        $this->actingAs($admin)->get(route('admin.reports.show', $daily))
            ->assertOk()
            ->assertDontSee('Tren Reservasi Harian');
    }
}
