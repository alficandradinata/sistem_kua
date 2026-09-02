<?php

namespace Tests\Feature;

use App\Models\QueueDetail;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * [SISTEM KUA] Layar antrean ruang tunggu (tanpa login).
 */
class PublicQueueDisplayTest extends TestCase
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

    private function queue(string $number, string $name, ?string $date = null): QueueDetail
    {
        $reservation = Reservation::create([
            'user_id' => User::factory()->create(['role' => User::ROLE_WARGA, 'name' => $name])->id,
            'service_id' => $this->service->id,
            'reservation_date' => $date ?? today()->toDateString(),
            'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_APPROVED,
        ]);

        return QueueDetail::create(['reservation_id' => $reservation->id, 'queue_number' => $number]);
    }

    public function test_display_is_reachable_without_login(): void
    {
        $this->get(route('queue.display'))->assertOk();
    }

    public function test_display_shows_called_number_and_the_waiting_ones(): void
    {
        $dipanggil = $this->queue('A-001', 'Warga Satu');
        $dipanggil->markAsCalled();

        $this->queue('A-002', 'Warga Dua');

        $this->get(route('queue.display'))
            ->assertOk()
            ->assertSee('A-001')
            ->assertSee('A-002')
            ->assertSee('Nomor Dipanggil');
    }

    /**
     * Alasan halaman ini dibuat "nomor saja": layar ruang tunggu terbaca semua
     * orang, dan sebagian layanan KUA sensitif. Nama warga maupun jenis
     * layanannya tidak boleh bocor ke situ.
     */
    public function test_display_never_leaks_citizen_name_or_service(): void
    {
        $this->queue('A-001', 'Budi Santoso')->markAsCalled();
        $this->queue('A-002', 'Siti Aminah');

        $response = $this->get(route('queue.display'))->assertOk();

        $response->assertDontSee('Budi Santoso');
        $response->assertDontSee('Siti Aminah');
        $response->assertDontSee($this->service->name);
    }

    public function test_display_only_covers_today(): void
    {
        $this->queue('A-009', 'Warga Besok', today()->addDay()->toDateString());

        $this->get(route('queue.display'))
            ->assertOk()
            ->assertDontSee('A-009')
            ->assertSee('Belum ada nomor yang dipanggil hari ini');
    }

    public function test_attended_number_is_no_longer_shown_as_current(): void
    {
        $selesai = $this->queue('A-001', 'Warga Satu');
        $selesai->markAsAttended();

        $berjalan = $this->queue('A-002', 'Warga Dua');
        $berjalan->markAsCalled();

        $this->get(route('queue.display'))
            ->assertOk()
            ->assertSee('A-002')          // yang sedang di loket
            ->assertDontSee('A-001')      // sudah selesai: bukan "dipanggil", bukan "berikutnya"
            ->assertSee('selesai dilayani');
    }
}
