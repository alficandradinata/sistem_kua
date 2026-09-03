<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * [SISTEM KUA] Panel "Kabar Terbaru" di dashboard warga — keputusan petugas
 * harus terlihat tanpa warga membuka lonceng notifikasi.
 */
class WargaDashboardDecisionsTest extends TestCase
{
    use RefreshDatabase;

    private User $warga;

    private User $petugas;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warga = User::factory()->create(['role' => User::ROLE_WARGA]);
        $this->petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);
        $this->service = Service::create([
            'name' => 'Pendaftaran Nikah', 'description' => 'x',
            'duration' => 60, 'fee' => 0, 'is_active' => true,
        ]);
    }

    private function reservation(?User $milik = null): Reservation
    {
        return Reservation::create([
            'user_id' => ($milik ?? $this->warga)->id,
            'service_id' => $this->service->id,
            'reservation_date' => Carbon::tomorrow()->toDateString(),
            'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_PENDING,
        ]);
    }

    public function test_approved_reservation_shows_queue_number_on_dashboard(): void
    {
        $reservation = $this->reservation();
        $queue = $reservation->approveAndIssueQueue($this->petugas->id);

        $this->actingAs($this->warga)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kabar Terbaru')
            ->assertSee('disetujui')
            ->assertSee($queue->queue_number);
    }

    public function test_rejected_reservation_shows_its_reason_on_dashboard(): void
    {
        $reservation = $this->reservation();
        $reservation->reject('Berkas belum lengkap', $this->petugas->id);

        $this->actingAs($this->warga)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kabar Terbaru')
            ->assertSee('ditolak petugas')
            ->assertSee('Berkas belum lengkap');
    }

    /**
     * Panel ini kabar, bukan arsip — kalau keputusan lama ikut nongkrong terus,
     * warga berhenti memperhatikannya.
     */
    public function test_old_decisions_drop_off_the_panel(): void
    {
        $reservation = $this->reservation();
        $reservation->reject('Berkas kedaluwarsa', $this->petugas->id);
        $reservation->update(['rejected_at' => now()->subDays(30)]);

        $this->actingAs($this->warga)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Kabar Terbaru')
            ->assertDontSee('Berkas kedaluwarsa');
    }

    public function test_panel_is_hidden_when_nothing_was_decided(): void
    {
        $this->reservation(); // tetap pending

        $this->actingAs($this->warga)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Kabar Terbaru');
    }

    /**
     * Keputusan milik warga lain tidak boleh bocor ke dashboard orang.
     */
    public function test_panel_never_shows_another_citizens_decision(): void
    {
        $lain = User::factory()->create(['role' => User::ROLE_WARGA]);
        $punyaOrangLain = $this->reservation($lain);
        $punyaOrangLain->reject('Rahasia orang lain', $this->petugas->id);

        $this->actingAs($this->warga)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Rahasia orang lain')
            ->assertDontSee('Kabar Terbaru');
    }
}
