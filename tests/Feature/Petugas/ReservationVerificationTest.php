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

    public function test_reject_requires_reason_and_cancels_reservation(): void
    {
        $reservation = $this->reservation();

        $this->actingAs($this->petugas)
            ->patch(route('petugas.reservations.reject', $reservation), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(Reservation::STATUS_PENDING, $reservation->refresh()->status);

        $this->actingAs($this->petugas)
            ->patch(route('petugas.reservations.reject', $reservation), ['reason' => 'Berkas belum lengkap'])
            ->assertRedirect();

        $reservation->refresh();
        $this->assertSame(Reservation::STATUS_CANCELLED, $reservation->status);
        $this->assertStringContainsString('Berkas belum lengkap', $reservation->notes);
        $this->assertSame(1, Notification::where('user_id', $this->warga->id)->count());
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
