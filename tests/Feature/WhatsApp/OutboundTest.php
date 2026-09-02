<?php

namespace Tests\Feature\WhatsApp;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * [SISTEM KUA] Notifikasi sistem ikut terkirim ke WhatsApp.
 */
class OutboundTest extends TestCase
{
    use RefreshDatabase;

    private User $warga;

    protected function setUp(): void
    {
        parent::setUp();

        config(['whatsapp.driver' => 'log', 'whatsapp.enabled' => true]);

        $this->warga = User::factory()->create([
            'role' => User::ROLE_WARGA,
            'phone' => '081234567890',
        ]);
    }

    public function test_notification_also_queues_whatsapp_message(): void
    {
        Queue::fake();

        Notification::send($this->warga->id, 'Reservasi Anda disetujui.');

        Queue::assertPushed(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            return $job->to === '6281234567890'
                && str_contains($job->body, 'disetujui');
        });
    }

    public function test_user_without_phone_gets_no_whatsapp(): void
    {
        Queue::fake();

        $tanpaNomor = User::factory()->create(['role' => User::ROLE_WARGA, 'phone' => null]);

        Notification::send($tanpaNomor->id, 'Halo.');

        Queue::assertNothingPushed();
    }

    public function test_channel_can_be_switched_off(): void
    {
        Queue::fake();
        config(['whatsapp.enabled' => false]);

        Notification::send($this->warga->id, 'Halo.');

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('notifications', 1);   // lonceng in-app tetap jalan
    }

    public function test_approval_flow_sends_whatsapp_end_to_end(): void
    {
        $service = Service::create([
            'name' => 'Pendaftaran Nikah', 'description' => 'x',
            'duration' => 60, 'fee' => 0, 'is_active' => true,
        ]);

        $reservation = Reservation::create([
            'user_id' => $this->warga->id,
            'service_id' => $service->id,
            'reservation_date' => today()->addDays(2)->toDateString(),
            'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_PENDING,
        ]);

        $reservation->approveAndIssueQueue();

        $pesan = WhatsAppMessage::outbound()->first();

        $this->assertNotNull($pesan);
        $this->assertSame('6281234567890', $pesan->wa_number);
        $this->assertStringContainsString('A-001', $pesan->body);
        $this->assertSame($this->warga->id, $pesan->user_id);
    }

    // --- Jendela 24 jam Cloud API ---

    public function test_uses_template_when_outside_session_window(): void
    {
        config(['whatsapp.driver' => 'cloud', 'whatsapp.cloud.phone_number_id' => '123']);
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.OUT']]], 200)]);

        // Tidak ada pesan masuk sama sekali → di luar jendela.
        Notification::send($this->warga->id, 'Reservasi Anda disetujui.');

        Http::assertSent(function ($request) {
            return $request['type'] === 'template'
                && $request['template']['name'] === config('whatsapp.template.name')
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === '6281234567890';
        });
    }

    public function test_uses_free_text_within_session_window(): void
    {
        config(['whatsapp.driver' => 'cloud', 'whatsapp.cloud.phone_number_id' => '123']);
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.OUT']]], 200)]);

        // Warga baru saja mengirim pesan → masih dalam jendela 24 jam.
        WhatsAppMessage::record([
            'direction' => WhatsAppMessage::DIRECTION_IN,
            'wa_number' => '6281234567890',
            'body' => 'halo',
            'status' => WhatsAppMessage::STATUS_RECEIVED,
        ]);

        Notification::send($this->warga->id, 'Reservasi Anda disetujui.');

        Http::assertSent(function ($request) {
            return $request['type'] === 'text'
                && str_contains($request['text']['body'], 'disetujui');
        });
    }

    public function test_failed_cloud_request_is_recorded_not_thrown(): void
    {
        config(['whatsapp.driver' => 'cloud', 'whatsapp.cloud.phone_number_id' => '123']);
        Http::fake(['*' => Http::response(['error' => ['message' => 'Token kedaluwarsa']], 401)]);

        Notification::send($this->warga->id, 'Reservasi Anda disetujui.');

        $pesan = WhatsAppMessage::outbound()->first();

        $this->assertSame(WhatsAppMessage::STATUS_FAILED, $pesan->status);
        $this->assertStringContainsString('Token kedaluwarsa', $pesan->error);
    }
}
