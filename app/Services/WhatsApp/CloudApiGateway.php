<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [SISTEM KUA] Driver produksi: WhatsApp Cloud API resmi Meta.
 *
 * Catatan penting: teks bebas hanya diterima Meta dalam 24 jam sejak pesan
 * terakhir dari warga. Di luar itu panggil sendTemplate() dengan template yang
 * sudah disetujui (lihat config/whatsapp.php).
 */
class CloudApiGateway implements WhatsAppGateway
{
    public function sendText(string $to, string $body, bool $isAutoReply = false): WhatsAppMessage
    {
        return $this->kirim($to, $body, $isAutoReply, [
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $body],
        ]);
    }

    public function sendTemplate(string $to, string $template, array $parameters = []): WhatsAppMessage
    {
        $body = $parameters[0] ?? "[template {$template}]";

        return $this->kirim($to, $body, false, [
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => config('whatsapp.template.language', 'id')],
                'components' => [[
                    'type' => 'body',
                    'parameters' => array_map(
                        fn ($nilai) => ['type' => 'text', 'text' => (string) $nilai],
                        $parameters
                    ),
                ]],
            ],
        ]);
    }

    public function name(): string
    {
        return 'WhatsApp Cloud API ('.config('whatsapp.cloud.api_version').')';
    }

    /**
     * @param  array<string, mixed>  $isiPesan  bagian payload yang membedakan teks vs template
     */
    private function kirim(string $to, string $body, bool $isAutoReply, array $isiPesan): WhatsAppMessage
    {
        $nomor = PhoneNumber::normalize($to);

        $dasar = [
            'direction' => WhatsAppMessage::DIRECTION_OUT,
            'wa_number' => $nomor,
            'user_id' => User::findByPhone($nomor)?->id,
            'body' => $body,
            'is_auto_reply' => $isAutoReply,
        ];

        if (! $nomor) {
            return WhatsAppMessage::record($dasar + [
                'wa_number' => $to,
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error' => 'Nomor tujuan tidak valid.',
            ]);
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            config('whatsapp.cloud.api_version'),
            config('whatsapp.cloud.phone_number_id'),
        );

        try {
            $response = Http::withToken(config('whatsapp.cloud.token'))
                ->timeout(15)
                ->post($url, ['messaging_product' => 'whatsapp', 'to' => $nomor] + $isiPesan);
        } catch (\Throwable $e) {
            Log::error('[SISTEM KUA] Gagal menghubungi WhatsApp Cloud API', ['pesan' => $e->getMessage()]);

            return WhatsAppMessage::record($dasar + [
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);
        }

        if ($response->failed()) {
            Log::warning('[SISTEM KUA] WhatsApp Cloud API menolak pesan', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return WhatsAppMessage::record($dasar + [
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error' => $response->json('error.message') ?? $response->body(),
                'payload' => $response->json(),
            ]);
        }

        return WhatsAppMessage::record($dasar + [
            'wamid' => $response->json('messages.0.id'),
            'status' => WhatsAppMessage::STATUS_SENT,
            'payload' => $response->json(),
        ]);
    }
}
