<?php

namespace Tests\Feature\Petugas;

use App\Models\Notification;
use App\Models\QueueDetail;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * [SISTEM KUA] Verifikasi reservasi oleh petugas.
 */
class ReservationVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $petugas;

    private User $warga;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);
        $this->warga = User::factory()->create(['role' => User::ROLE_WARGA]);
        $this->service = Service::create([
            'name' => 'Pendaftaran Nikah', 'description' => 'x',
            'duration' => 60, 'fee' => 0, 'is_active' => true,
        ]);
    }

    private function reservation(string $status = Reservation::STATUS_PENDING): Reservation
    {
        return Reservation::create([
            'user_id' => $this->warga->id,
            'service_id' => $this->service->id,
            'reservation_date' => Carbon::tomorrow()->toDateString(),
            'reservation_time' => '08:00:00',
            'status' => $status,
        ]);
    }

    public function test_petugas_can_open_verification_page(): void
    {
        $this->actingAs($this->petugas)->get(route('petugas.reservations.index'))->assertOk();
    }

    public function test_warga_cannot_open_verification_page(): void
    {
        $this->actingAs($this->warga)->get(route('petugas.reservations.index'))->assertForbidden();
    }

    public function test_approve_sets_status_issues_queue_number_and_notifies(): void
    {
        $reservation = $this->reservation();

        $this->actingAs($this->petugas)
            ->patch(route('petugas.reservations.approve', $reservation))
            ->assertRedirect();

        $reservation->refresh();
        $this->assertSame(Reservation::STATUS_APPROVED, $reservation->status);
        $this->assertNotNull($reservation->queueDetail);
        $this->assertSame('A-001', $reservation->queueDetail->queue_number);
        $this->assertFalse($reservation->queueDetail->is_called);

        $this->assertSame(1, Notification::where('user_id', $this->warga->id)->count());
        $this->assertStringContainsString('A-001', Notification::first()->message);
    }

    public function test_queue_numbers_increment_per_date(): void
    {
        $first = $this->reservation();
        $second = Reservation::create([
            'user_id' => User::factory()->create()->id,
            'service_id' => $this->service->id,
            'reservation_date' => Carbon::tomorrow()->toDateString(),
            'reservation_time' => '09:00:00',
            'status' => Reservation::STATUS_PENDING,
        ]);

        $this->actingAs($this->petugas)->patch(route('petugas.reservations.approve', $first));
        $this->actingAs($this->petugas)->patch(route('petugas.reservations.approve', $second));

        $this->assertSame('A-001', $first->refresh()->queueDetail->queue_number);
        $this->assertSame('A-002', $second->refresh()->queueDetail->queue_number);
    }

    public function test_cannot_approve_non_pending_reservation(): void
    {
        $reservation = $this->reservation(Reservation::STATUS_COMPLETED);

        $this->actingAs($this->petugas)
            ->patch(route('petugas.reservations.approve', $reservation))
            ->assertSessionHasErrors('status');

        $this->assertSame(0, QueueDetail::count());
    }

    public function test_reject_requires_reason_and_marks_reservation_rejected(): void
    {
        $reservation = $this->reservation();
        $reservation->update(['notes' => 'Mohon dilayani pagi.']);

        $this->actingAs($this->petugas)
            ->patch(route('petugas.reservations.reject', $reservation), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(Reservation::STATUS_PENDING, $reservation->refresh()->status);

        $this->actingAs($this->petugas)
            ->patch(route('petugas.reservations.reject', $reservation), ['reason' => 'Berkas belum lengkap'])
            ->assertRedirect();

        $reservation->refresh();
        $this->assertSame(Reservation::STATUS_REJECTED, $reservation->status);
        $this->assertSame('Berkas belum lengkap', $reservation->rejection_reason);

        // Catatan milik warga tidak boleh ikut ditulisi alasan petugas.
        $this->assertSame('Mohon dilayani pagi.', $reservation->notes);

        $this->assertSame(1, Notification::where('user_id', $this->warga->id)->count());
    }

    /**
     * Ditolak petugas dan dibatalkan warga adalah dua hal berbeda secara
     * administratif — laporan KUA bergantung pada pemisahan ini.
     */
    public function test_rejected_is_a_distinct_status_from_cancelled(): void
    {
        $ditolak = $this->reservation();
        $ditolak->reject('Tanggal bentrok agenda KUA');

        $dibatalkan = $this->reservation();
        $dibatalkan->cancel();

        $this->assertTrue($ditolak->fresh()->isRejected());
        $this->assertFalse($ditolak->fresh()->isCancelled());
        $this->assertTrue($dibatalkan->fresh()->isCancelled());
        $this->assertFalse($dibatalkan->fresh()->isRejected());

        $this->assertSame(1, Reservation::rejected()->count());
        $this->assertSame(1, Reservation::cancelled()->count());
        $this->assertSame(0, Reservation::active()->count());
    }

    /**
     * Jejak audit: setiap keputusan verifikasi harus bisa ditunjuk pelakunya.
     */
    public function test_approval_and_rejection_record_the_officer(): void
    {
        $disetujui = $this->reservation();

        $this->actingAs($this->petugas)
            ->patch(route('petugas.reservations.approve', $disetujui))
            ->assertRedirect();

        $disetujui->refresh();
        $this->assertSame($this->petugas->id, $disetujui->approved_by);
        $this->assertNotNull($disetujui->approved_at);
        $this->assertNull($disetujui->rejected_by);
        $this->assertSame($this->petugas->name, $disetujui->approvedBy->name);

        $ditolak = $this->reservation();

        $this->actingAs($this->petugas)
            ->patch(route('petugas.reservations.reject', $ditolak), ['reason' => 'Berkas belum lengkap'])
            ->assertRedirect();

        $ditolak->refresh();
        $this->assertSame($this->petugas->id, $ditolak->rejected_by);
        $this->assertNotNull($ditolak->rejected_at);
        $this->assertNull($ditolak->approved_by);
        $this->assertStringContainsString($this->petugas->name, $ditolak->verification_log);
    }

    /**
     * Kalau akun petugasnya dihapus, jejaknya tidak boleh ikut lenyap diam-diam
     * — barisnya tetap ada, hanya namanya yang hilang.
     */
    public function test_audit_trail_survives_officer_account_deletion(): void
    {
        $reservation = $this->reservation();
        $reservation->reject('Berkas belum lengkap', $this->petugas->id);

        $this->petugas->delete();

        $reservation->refresh();
        $this->assertSame(Reservation::STATUS_REJECTED, $reservation->status);
        $this->assertNull($reservation->rejected_by);
        $this->assertNotNull($reservation->rejected_at);
        $this->assertStringContainsString('sudah dihapus', $reservation->verification_log);
    }

    public function test_warga_sees_rejection_reason_on_reservation_page(): void
    {
        $reservation = $this->reservation();
        $reservation->reject('Berkas belum lengkap');

        $this->actingAs($this->warga)
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Ditolak')
            ->assertSee('Berkas belum lengkap');
    }

    public function test_filter_by_status_works(): void
    {
        $this->reservation();
        $this->reservation(Reservation::STATUS_COMPLETED);

        $this->actingAs($this->petugas)
            ->get(route('petugas.reservations.index', ['status' => Reservation::STATUS_COMPLETED]))
            ->assertOk()
            ->assertSee('Selesai');
    }
}
