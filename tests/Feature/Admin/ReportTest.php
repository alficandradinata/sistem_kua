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
 * [SISTEM KUA] Laporan rekap reservasi (panel admin).
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->service = Service::create([
            'name' => 'Pendaftaran Nikah', 'description' => 'x',
            'duration' => 60, 'fee' => 600000, 'is_active' => true,
        ]);
    }

    private function makeReservation(string $date, string $status, string $time = '08:00:00'): Reservation
    {
        return Reservation::create([
            'user_id' => User::factory()->create()->id,
            'service_id' => $this->service->id,
            'reservation_date' => $date,
            'reservation_time' => $time,
            'status' => $status,
        ]);
    }

    // --- Hak akses ---

    public function test_petugas_and_warga_cannot_access_reports(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);
        $warga = User::factory()->create(['role' => User::ROLE_WARGA]);

        $this->actingAs($petugas)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($warga)->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_admin_can_open_report_page(): void
    {
        $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk();
    }

    // --- Pembuatan laporan ---

    public function test_daily_report_counts_only_that_day(): void
    {
        $date = '2026-03-12';

        $this->makeReservation($date, Reservation::STATUS_COMPLETED, '08:00:00');
        $this->makeReservation($date, Reservation::STATUS_COMPLETED, '09:00:00');
        $this->makeReservation($date, Reservation::STATUS_CANCELLED, '10:00:00');
        $this->makeReservation($date, Reservation::STATUS_PENDING, '11:00:00');
        $this->makeReservation('2026-03-13', Reservation::STATUS_COMPLETED); // di luar periode

        $this->actingAs($this->admin)->post(route('admin.reports.store'), [
            'report_type' => Report::TYPE_DAILY,
            'report_date' => $date,
        ])->assertRedirect();

        $report = Report::first();

        $this->assertSame(4, $report->total_reservations);
        $this->assertSame(2, $report->total_completed);
        $this->assertSame(1, $report->total_cancelled);
        $this->assertSame(1, $report->total_pending);
        $this->assertSame(50.0, $report->completion_rate);
        $this->assertSame($this->admin->id, $report->generated_by);
        $this->assertSame($date, $report->report_date->toDateString());
    }

    public function test_regenerating_same_period_updates_instead_of_duplicating(): void
    {
        $date = '2026-03-12';
        $this->makeReservation($date, Reservation::STATUS_COMPLETED);

        $this->actingAs($this->admin)->post(route('admin.reports.store'), [
            'report_type' => Report::TYPE_DAILY, 'report_date' => $date,
        ]);

        $this->makeReservation($date, Reservation::STATUS_CANCELLED, '09:00:00');

        $this->actingAs($this->admin)->post(route('admin.reports.store'), [
            'report_type' => Report::TYPE_DAILY, 'report_date' => $date,
        ]);

        $this->assertDatabaseCount('reports', 1);

        $report = Report::first();
        $this->assertSame(2, $report->total_reservations);
        $this->assertSame(1, $report->total_cancelled);
    }

    public function test_weekly_report_normalises_date_and_covers_whole_week(): void
    {
        // 12 Maret 2026 = Kamis; pekannya Senin 9 s.d. Minggu 15 Maret.
        $this->makeReservation('2026-03-09', Reservation::STATUS_COMPLETED);
        $this->makeReservation('2026-03-15', Reservation::STATUS_PENDING);
        $this->makeReservation('2026-03-16', Reservation::STATUS_COMPLETED); // pekan berikutnya

        $report = Report::generateFor(Report::TYPE_WEEKLY, '2026-03-12', $this->admin->id);

        $this->assertSame('2026-03-09', $report->report_date->toDateString());
        $this->assertSame(2, $report->total_reservations);
        $this->assertSame(1, $report->total_completed);
    }

    public function test_monthly_report_normalises_date_and_covers_whole_month(): void
    {
        $this->makeReservation('2026-03-01', Reservation::STATUS_COMPLETED);
        $this->makeReservation('2026-03-31', Reservation::STATUS_CANCELLED);
        $this->makeReservation('2026-04-01', Reservation::STATUS_COMPLETED); // bulan berikutnya

        $report = Report::generateFor(Report::TYPE_MONTHLY, '2026-03-20', $this->admin->id);

        $this->assertSame('2026-03-01', $report->report_date->toDateString());
        $this->assertSame(2, $report->total_reservations);
        $this->assertSame('Maret 2026', $report->period_label);
    }

    public function test_invalid_report_type_is_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.reports.store'), [
            'report_type' => 'tahunan', 'report_date' => Carbon::today()->toDateString(),
        ])->assertSessionHasErrors('report_type');
    }

    // --- Rincian & ekspor ---

    public function test_report_detail_shows_service_breakdown(): void
    {
        $date = '2026-03-12';
        $this->makeReservation($date, Reservation::STATUS_COMPLETED);
        $this->makeReservation($date, Reservation::STATUS_CANCELLED, '09:00:00');

        $report = Report::generateFor(Report::TYPE_DAILY, $date, $this->admin->id);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee('Pendaftaran Nikah');

        $row = $report->serviceBreakdown()->first();
        $this->assertSame(2, (int) $row->total);
        $this->assertSame(1, (int) $row->completed);
        $this->assertSame(1, (int) $row->cancelled);
    }

    public function test_admin_can_export_report_as_csv(): void
    {
        $date = '2026-03-12';
        $this->makeReservation($date, Reservation::STATUS_COMPLETED);

        $report = Report::generateFor(Report::TYPE_DAILY, $date, $this->admin->id);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.export', $report));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Nama Warga', $csv);
        $this->assertStringContainsString('Pendaftaran Nikah', $csv);
    }

    public function test_admin_can_export_report_as_pdf(): void
    {
        $date = '2026-03-12';
        $this->makeReservation($date, Reservation::STATUS_COMPLETED);

        $report = Report::generateFor(Report::TYPE_DAILY, $date, $this->admin->id);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.pdf', $report));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('laporan-daily-2026-03-12.pdf',
            $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_petugas_cannot_download_report_pdf(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);
        $report = Report::generateFor(Report::TYPE_DAILY, '2026-03-12', $this->admin->id);

        $this->actingAs($petugas)->get(route('admin.reports.pdf', $report))->assertForbidden();
    }

    public function test_admin_can_delete_report(): void
    {
        $report = Report::generateFor(Report::TYPE_DAILY, '2026-03-12', $this->admin->id);

        $this->actingAs($this->admin)
            ->delete(route('admin.reports.destroy', $report))
            ->assertRedirect(route('admin.reports.index', ['type' => Report::TYPE_DAILY]));

        $this->assertDatabaseCount('reports', 0);
    }
}
