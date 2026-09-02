<?php

namespace Tests\Feature\Petugas;

use App\Models\Notification;
use App\Models\QueueDetail;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * [SISTEM KUA] Papan antrean petugas: panggil & layani.
 */
class QueueBoardTest extends TestCase
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
            'name' => 'Rujuk', 'description' => 'x',
            'duration' => 30, 'fee' => 0, 'is_active' => true,
        ]);
    }

    private function queue(string $number = 'A-001'): QueueDetail
    {
        $reservation = Reservation::create([
            'user_id' => $this->warga->id,
            'service_id' => $this->service->id,
            'reservation_date' => today()->toDateString(),
            'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_APPROVED,
        ]);

        return QueueDetail::create(['reservation_id' => $reservation->id, 'queue_number' => $number]);
    }

    public function test_petugas_can_open_queue_board(): void
    {
        $this->actingAs($this->petugas)->get(route('petugas.queues.index'))->assertOk();
    }

    public function test_warga_cannot_open_queue_board(): void
    {
        $this->actingAs($this->warga)->get(route('petugas.queues.index'))->assertForbidden();
    }

    public function test_call_marks_queue_and_notifies_warga(): void
    {
        $queue = $this->queue();

        $this->actingAs($this->petugas)
            ->patch(route('petugas.queues.call', $queue))
            ->assertRedirect();

        $queue->refresh();
        $this->assertTrue($queue->is_called);
        $this->assertNotNull($queue->called_at);
        $this->assertSame(1, Notification::where('user_id', $this->warga->id)->count());
    }

    public function test_cannot_call_twice(): void
    {
        $queue = $this->queue();
        $queue->markAsCalled();

        $this->actingAs($this->petugas)
            ->patch(route('petugas.queues.call', $queue))
            ->assertSessionHasErrors('queue');
    }

    public function test_attend_completes_reservation(): void
    {
        $queue = $this->queue();
        $queue->markAsCalled();

        $this->actingAs($this->petugas)
            ->patch(route('petugas.queues.attend', $queue))
            ->assertRedirect();

        $queue->refresh();
        $this->assertTrue($queue->isAttended());
        $this->assertSame(Reservation::STATUS_COMPLETED, $queue->reservation->refresh()->status);
    }

    public function test_call_next_picks_lowest_waiting_number(): void
    {
        $this->queue('A-002');
        $first = $this->queue('A-001');

        $this->actingAs($this->petugas)
            ->patch(route('petugas.queues.callNext', ['date' => today()->toDateString()]))
            ->assertRedirect();

        $this->assertTrue($first->refresh()->is_called);
    }

    public function test_call_next_errors_when_nothing_waiting(): void
    {
        $this->actingAs($this->petugas)
            ->patch(route('petugas.queues.callNext'))
            ->assertSessionHasErrors('queue');
    }
}
