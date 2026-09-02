<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMessage;

/**
 * [SISTEM KUA] Kontrak pengirim WhatsApp.
 *
 * Folder Services/ sengaja dipakai untuk infrastruktur (HTTP ke Meta), bukan
 * logika bisnis — keputusan domain tetap berada di model. Lihat CLAUDE.md.
 */
interface WhatsAppGateway
{
    /**
     * Kirim teks bebas. Hanya boleh dalam jendela 24 jam sejak pesan terakhir warga.
     */
    public function sendText(string $to, string $body, bool $isAutoReply = false): WhatsAppMessage;

    /**
     * Kirim template yang sudah disetujui Meta (untuk di luar jendela 24 jam).
     * $parameters mengisi {{1}}, {{2}}, … pada body template.
     */
    public function sendTemplate(string $to, string $template, array $parameters = []): WhatsAppMessage;

    /**
     * Nama driver, untuk ditampilkan di panel admin.
     */
    public function name(): string;
}
