<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\QueueDetail;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * [SISTEM KUA] Pengingat H-1 reservasi (perintah `pengingat:reservasi`).
 */
class ReservationReminderTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    private User $warga;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = Service::create([
            'name' => 'Pendaftaran Nikah', 'description' => 'x',
            'duration' => 60, 'fee' => 0, 'is_active' => true,
        ]);
        $this->warga = User::factory()->create(['role' => User::ROLE_WARGA]);
    }

    private function makeReservation(string $date, string $status, string $time = '08:00:00'): Reservation
    {
        return Reservation::create([
            'user_id' => $this->warga->id,
            'service_id' => $this->service->id,
            'reservation_date' => $date,
            'reservation_time' => $time,
            'status' => $status,
        ]);
    }

    public function test_reminder_sent_for_approved_reservation_tomorrow(): void
    {
        $reservation = $this->makeReservation(today()->addDay()->toDateString(), Reservation::STATUS_APPROVED);
        QueueDetail::create([
            'reservation_id' => $reservation->id,
            'queue_number' => QueueDetail::generateNumber($reservation->reservation_date->toDateString()),
        ]);

        $this->artisan('pengingat:reservasi')->assertSuccessful();

        $notification = Notification::forUser($this->warga->id)->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Pengingat', $notification->message);
        $this->assertStringContainsString('Pendaftaran Nikah', $notification->message);
        $this->assertStringContainsString('A-001', $notification->message);
        $this->assertNotNull($reservation->fresh()->reminded_at);
    }

    public function test_reminder_not_sent_twice(): void
    {
        $this->makeReservation(today()->addDay()->toDateString(), Reservation::STATUS_APPROVED);

        $this->artisan('pengingat:reservasi')->assertSuccessful();
        $this->artisan('pengingat:reservasi')->assertSuccessful();

        $this->assertSame(1, Notification::forUser($this->warga->id)->count());
    }

    public function test_reminder_skips_other_dates_and_statuses(): void
    {
        // Besok tapi belum disetujui.
        $this->makeReservation(today()->addDay()->toDateString(), Reservation::STATUS_PENDING);
        // Sudah disetujui tapi lusa.
        $this->makeReservation(today()->addDays(2)->toDateString(), Reservation::STATUS_APPROVED, '09:00:00');
        // Besok tapi sudah dibatalkan.
        $this->makeReservation(today()->addDay()->toDateString(), Reservation::STATUS_CANCELLED, '10:00:00');

        $this->artisan('pengingat:reservasi')->assertSuccessful();

        $this->assertSame(0, Notification::count());
    }

    public function test_reminder_accepts_explicit_date(): void
    {
        $this->makeReservation('2026-12-24', Reservation::STATUS_APPROVED);

        $this->artisan('pengingat:reservasi', ['--date' => '2026-12-24'])->assertSuccessful();

        $this->assertSame(1, Notification::forUser($this->warga->id)->count());
    }

    public function test_reminder_rejects_invalid_date(): void
    {
        $this->artisan('pengingat:reservasi', ['--date' => 'bukan-tanggal'])->assertFailed();

        $this->assertSame(0, Notification::count());
    }

    public function test_send_reminder_returns_null_when_already_reminded(): void
    {
        $reservation = $this->makeReservation(today()->addDay()->toDateString(), Reservation::STATUS_APPROVED);

        $this->assertNotNull($reservation->sendReminder());
        $this->assertNull($reservation->fresh()->sendReminder());
    }
}
