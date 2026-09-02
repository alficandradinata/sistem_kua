<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\ServiceSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * [SISTEM KUA] Alur reservasi warga.
 */
class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $warga;

    private Service $service;

    private ServiceSlot $slot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warga = User::factory()->create(['role' => User::ROLE_WARGA]);
        $this->service = Service::create([
            'name' => 'Pendaftaran Nikah', 'description' => 'x',
            'duration' => 60, 'fee' => 0, 'is_active' => true,
        ]);
        $this->slot = ServiceSlot::create([
            'service_id' => $this->service->id, 'quota_per_day' => 1,
            'slot_start_time' => '08:00:00', 'slot_duration' => 60, 'is_active' => true,
        ]);
        // Buka setiap hari supaya tanggal uji tidak tergantung hari kalender.
        foreach (range(0, 6) as $d) {
            Schedule::create([
                'day_of_week' => $d, 'open_time' => '08:00:00',
                'close_time' => '15:00:00', 'is_active' => true,
            ]);
        }
    }

    private function nextDate(): string
    {
        return Carbon::tomorrow()->addDay()->toDateString();
    }

    public function test_warga_can_open_create_form(): void
    {
        $this->actingAs($this->warga)->get(route('reservations.create'))->assertOk();
    }

    public function test_warga_can_create_reservation(): void
    {
        $date = $this->nextDate();

        $res = $this->actingAs($this->warga)->post(route('reservations.store'), [
            'service_id' => $this->service->id,
            'reservation_date' => $date,
            'slot_id' => $this->slot->id,
        ]);

        $reservation = Reservation::first();
        $this->assertNotNull($reservation);
        $res->assertRedirect(route('reservations.show', $reservation));
        $this->assertSame(Reservation::STATUS_PENDING, $reservation->status);
        $this->assertSame('08:00:00', $reservation->reservation_time);
    }

    public function test_reservation_rejected_on_holiday(): void
    {
        $date = $this->nextDate();
        Holiday::create(['holiday_date' => $date, 'description' => 'Libur', 'is_active' => true]);

        $this->actingAs($this->warga)->post(route('reservations.store'), [
            'service_id' => $this->service->id,
            'reservation_date' => $date,
            'slot_id' => $this->slot->id,
        ])->assertSessionHasErrors('reservation_date');

        $this->assertSame(0, Reservation::count());
    }

    public function test_reservation_rejected_when_kua_closed(): void
    {
        $date = $this->nextDate();
        Schedule::where('day_of_week', Carbon::parse($date)->dayOfWeek)->update(['is_active' => false]);

        $this->actingAs($this->warga)->post(route('reservations.store'), [
            'service_id' => $this->service->id,
            'reservation_date' => $date,
            'slot_id' => $this->slot->id,
        ])->assertSessionHasErrors('reservation_date');
    }

    public function test_reservation_rejected_in_the_past(): void
    {
        $this->actingAs($this->warga)->post(route('reservations.store'), [
            'service_id' => $this->service->id,
            'reservation_date' => Carbon::yesterday()->toDateString(),
            'slot_id' => $this->slot->id,
        ])->assertSessionHasErrors('reservation_date');
    }

    public function test_reservation_rejected_when_slot_full(): void
    {
        $date = $this->nextDate();
        // Isi kuota (quota_per_day = 1) oleh warga lain.
        Reservation::create([
            'user_id' => User::factory()->create()->id,
            'service_id' => $this->service->id,
            'reservation_date' => $date,
            'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_PENDING,
        ]);

        $this->actingAs($this->warga)->post(route('reservations.store'), [
            'service_id' => $this->service->id,
            'reservation_date' => $date,
            'slot_id' => $this->slot->id,
        ])->assertSessionHasErrors('slot_id');
    }

    public function test_warga_cannot_double_book_same_service_and_date(): void
    {
        $date = $this->nextDate();
        ServiceSlot::where('id', $this->slot->id)->update(['quota_per_day' => 5]);

        $payload = [
            'service_id' => $this->service->id,
            'reservation_date' => $date,
            'slot_id' => $this->slot->id,
        ];

        $this->actingAs($this->warga)->post(route('reservations.store'), $payload);
        $this->actingAs($this->warga)->post(route('reservations.store'), $payload)
            ->assertSessionHasErrors('service_id');

        $this->assertSame(1, Reservation::count());
    }

    public function test_warga_can_cancel_own_reservation(): void
    {
        $reservation = Reservation::create([
            'user_id' => $this->warga->id,
            'service_id' => $this->service->id,
            'reservation_date' => $this->nextDate(),
            'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_PENDING,
        ]);

        $this->actingAs($this->warga)
            ->patch(route('reservations.cancel', $reservation))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(Reservation::STATUS_CANCELLED, $reservation->fresh()->status);
    }

    public function test_warga_cannot_view_others_reservation(): void
    {
        $other = Reservation::create([
            'user_id' => User::factory()->create()->id,
            'service_id' => $this->service->id,
            'reservation_date' => $this->nextDate(),
            'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_PENDING,
        ]);

        $this->actingAs($this->warga)->get(route('reservations.show', $other))->assertForbidden();
    }
}
