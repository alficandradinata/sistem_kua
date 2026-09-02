<?php

namespace App\Jobs;

use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * [SISTEM KUA] Kirim satu pesan WhatsApp di latar belakang, supaya request web
 * (mis. petugas menyetujui reservasi) tidak menunggu jawaban Meta.
 *
 * Memilih sendiri teks bebas vs template: Cloud API hanya menerima teks bebas
 * dalam 24 jam sejak pesan terakhir warga.
 */
class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $to,
        public string $body,
        public bool $isAutoReply = false,
    ) {}

    public function handle(WhatsAppGateway $gateway): void
    {
        if (! config('whatsapp.enabled')) {
            return;
        }

        // Balasan otomatis selalu berada di dalam jendela 24 jam (warga baru saja
        // mengirim pesan), jadi tidak perlu template.
        if ($this->isAutoReply || WhatsAppMessage::withinSessionWindow($this->to)) {
            $gateway->sendText($this->to, $this->body, $this->isAutoReply);

            return;
        }

        $gateway->sendTemplate($this->to, config('whatsapp.template.name'), [$this->body]);
    }
}
