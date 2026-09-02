<?php

namespace App\Http\Controllers;

use App\Jobs\SendWhatsAppMessage;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\AutoReplyResolver;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * [SISTEM KUA] Menerima chat WhatsApp masuk dari WhatsApp Cloud API.
 *
 * Alur: Meta memanggil GET sekali untuk verifikasi, lalu POST setiap ada pesan.
 * Bila POST tidak dijawab 200, Meta mengirim ulang payload yang sama — karena itu
 * `wamid` disimpan unik agar satu pesan tidak diproses dua kali.
 */
class WhatsAppWebhookController extends Controller
{
    /**
     * Verifikasi kepemilikan webhook (dipanggil sekali saat mendaftarkan URL).
     */
    public function verify(Request $request): Response
    {
        $token = config('whatsapp.cloud.verify_token');

        if ($request->query('hub_mode') === 'subscribe'
            && $token
            && hash_equals($token, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Verifikasi gagal.', 403);
    }

    public function handle(Request $request, AutoReplyResolver $resolver): Response
    {
        if (! $this->signatureValid($request)) {
            Log::warning('[SISTEM KUA] Webhook WhatsApp ditolak: tanda tangan tidak cocok.');

            return response('Tanda tangan tidak valid.', 403);
        }

        foreach ($this->extractMessages($request->all()) as $pesan) {
            $this->processMessage($pesan, $resolver);
        }

        // Selalu 200 supaya Meta tidak mengirim ulang payload yang sudah diproses.
        return response('OK', 200);
    }

    /**
     * Meta menandatangani body mentah dengan App Secret (HMAC-SHA256).
     * Kalau app secret belum diisi (mis. driver log saat dev), pemeriksaan dilewati.
     */
    private function signatureValid(Request $request): bool
    {
        $secret = config('whatsapp.cloud.app_secret');

        if (! $secret) {
            return true;
        }

        $header = (string) $request->header('X-Hub-Signature-256');

        if (! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $harapan = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($harapan, $header);
    }

    /**
     * Ambil daftar pesan teks dari struktur payload Cloud API yang berlapis.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractMessages(array $payload): array
    {
        $hasil = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['messages'] ?? [] as $message) {
                    $hasil[] = $message;
                }
            }
        }

        return $hasil;
    }

    private function processMessage(array $pesan, AutoReplyResolver $resolver): void
    {
        $wamid = $pesan['id'] ?? null;
        $dari = PhoneNumber::normalize($pesan['from'] ?? null);
        $isi = $pesan['text']['body'] ?? null;

        if (! $dari || $isi === null) {
            return;                                   // gambar/stiker/lokasi: belum ditangani
        }

        if ($wamid && WhatsAppMessage::where('wamid', $wamid)->exists()) {
            return;                                   // sudah pernah diproses
        }

        WhatsAppMessage::record([
            'direction' => WhatsAppMessage::DIRECTION_IN,
            'wa_number' => $dari,
            'user_id' => User::findByPhone($dari)?->id,
            'body' => $isi,
            'wamid' => $wamid,
            'status' => WhatsAppMessage::STATUS_RECEIVED,
            'payload' => $pesan,
        ]);

        $balasan = $resolver->resolve($dari, $isi);

        if ($balasan !== null) {
            SendWhatsAppMessage::dispatch($dari, $balasan, isAutoReply: true);
        }
    }
}
