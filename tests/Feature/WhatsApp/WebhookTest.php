<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * [SISTEM KUA] Webhook WhatsApp Cloud API: verifikasi, keamanan, dan auto-reply.
 */
class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'rahasia-app-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.driver' => 'log',
            'whatsapp.enabled' => true,
            'whatsapp.cloud.verify_token' => 'token-verifikasi',
            'whatsapp.cloud.app_secret' => self::SECRET,
            'whatsapp.auto_reply_cooldown_minutes' => 0,
        ]);
    }

    /**
     * Payload chat masuk seperti yang dikirim Meta.
     */
    private function payload(string $from, string $text, string $wamid = 'wamid.TEST1'): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'messages' => [[
                            'from' => $from,
                            'id' => $wamid,
                            'type' => 'text',
                            'text' => ['body' => $text],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    private function kirimWebhook(array $payload, ?string $signature = null)
    {
        $json = json_encode($payload);

        $signature ??= 'sha256='.hash_hmac('sha256', $json, self::SECRET);

        return $this->call(
            'POST',
            route('whatsapp.webhook'),
            [], [], [],
            ['HTTP_X-Hub-Signature-256' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $json,
        );
    }

    // --- Verifikasi webhook ---

    public function test_verification_echoes_challenge(): void
    {
        $this->get(route('whatsapp.webhook.verify').'?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'token-verifikasi',
            'hub.challenge' => '1234567890',
        ]))->assertOk()->assertSee('1234567890');
    }

    public function test_verification_rejects_wrong_token(): void
    {
        $this->get(route('whatsapp.webhook.verify').'?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'token-palsu',
            'hub.challenge' => '1234567890',
        ]))->assertForbidden();
    }

    // --- Keamanan ---

    public function test_rejects_invalid_signature(): void
    {
        $this->kirimWebhook($this->payload('6281234567890', 'halo'), 'sha256=palsu')
            ->assertForbidden();

        $this->assertDatabaseCount('whatsapp_messages', 0);
    }

    // --- Pesan masuk ---

    public function test_incoming_message_is_stored_and_answered(): void
    {
        $this->kirimWebhook($this->payload('6281234567890', 'halo'))->assertOk();

        $this->assertSame(1, WhatsAppMessage::inbound()->count());
        $this->assertSame(1, WhatsAppMessage::outbound()->count());

        $balasan = WhatsAppMessage::outbound()->first();
        $this->assertTrue($balasan->is_auto_reply);
        $this->assertStringContainsString('layanan WhatsApp Kantor Urusan Agama', $balasan->body);
    }

    public function test_duplicate_wamid_is_processed_once(): void
    {
        $payload = $this->payload('6281234567890', 'halo', 'wamid.SAMA');

        $this->kirimWebhook($payload)->assertOk();
        $this->kirimWebhook($payload)->assertOk();   // Meta mengirim ulang

        $this->assertSame(1, WhatsAppMessage::inbound()->count());
    }

    public function test_non_text_message_is_ignored(): void
    {
        $payload = $this->payload('6281234567890', 'x');
        unset($payload['entry'][0]['changes'][0]['value']['messages'][0]['text']);

        $this->kirimWebhook($payload)->assertOk();

        $this->assertDatabaseCount('whatsapp_messages', 0);
    }

    public function test_incoming_message_is_linked_to_registered_user(): void
    {
        $warga = User::factory()->create(['role' => User::ROLE_WARGA, 'phone' => '081234567890']);

        $this->kirimWebhook($this->payload('6281234567890', 'halo'))->assertOk();

        $this->assertSame($warga->id, WhatsAppMessage::inbound()->first()->user_id);
    }

    public function test_status_menu_reports_reservation_and_queue_number(): void
    {
        $warga = User::factory()->create(['role' => User::ROLE_WARGA, 'phone' => '081234567890']);
        $service = Service::create([
            'name' => 'Pendaftaran Nikah', 'description' => 'x',
            'duration' => 60, 'fee' => 0, 'is_active' => true,
        ]);
        $reservation = Reservation::create([
            'user_id' => $warga->id,
            'service_id' => $service->id,
            'reservation_date' => today()->addDays(3)->toDateString(),
            'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_PENDING,
        ]);
        $reservation->approveAndIssueQueue();

        $this->kirimWebhook($this->payload('6281234567890', '1'))->assertOk();

        $balasan = WhatsAppMessage::outbound()->where('is_auto_reply', true)->latest('id')->first();

        $this->assertStringContainsString('Pendaftaran Nikah', $balasan->body);
        $this->assertStringContainsString('A-001', $balasan->body);
        $this->assertStringContainsString('Disetujui', $balasan->body);
    }

    public function test_status_menu_guides_unknown_number_to_register(): void
    {
        $this->kirimWebhook($this->payload('6289999999999', 'status'))->assertOk();

        $balasan = WhatsAppMessage::outbound()->first();

        $this->assertStringContainsString('belum terdaftar', $balasan->body);
    }
}
