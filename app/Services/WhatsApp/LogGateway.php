<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;

/**
 * [SISTEM KUA] Driver dev/test: tidak menghubungi Meta sama sekali.
 * Pesan tetap tercatat di tabel whatsapp_messages + log, sehingga seluruh alur
 * (webhook → balasan → riwayat) bisa diuji tanpa akun WhatsApp Business.
 */
class LogGateway implements WhatsAppGateway
{
    public function sendText(string $to, string $body, bool $isAutoReply = false): WhatsAppMessage
    {
        return $this->catat($to, $body, $isAutoReply, 'teks');
    }

    public function sendTemplate(string $to, string $template, array $parameters = []): WhatsAppMessage
    {
        // Yang dicatat isi parameternya, karena itulah yang dibaca warga.
        $body = $parameters[0] ?? "[template {$template}]";

        return $this->catat($to, $body, false, "template:{$template}");
    }

    public function name(): string
    {
        return 'log (tidak mengirim ke WhatsApp)';
    }

    private function catat(string $to, string $body, bool $isAutoReply, string $jenis): WhatsAppMessage
    {
        $nomor = PhoneNumber::normalize($to);

        Log::info('[SISTEM KUA] WhatsApp keluar (driver log)', [
            'ke' => $nomor,
            'jenis' => $jenis,
            'isi' => $body,
        ]);

        return WhatsAppMessage::record([
            'direction' => WhatsAppMessage::DIRECTION_OUT,
            'wa_number' => $nomor,
            'user_id' => User::findByPhone($nomor)?->id,
            'body' => $body,
            'status' => WhatsAppMessage::STATUS_SENT,
            'is_auto_reply' => $isAutoReply,
            'payload' => ['driver' => 'log', 'jenis' => $jenis],
        ]);
    }
}
