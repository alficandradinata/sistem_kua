<?php

namespace Database\Seeders;

use App\Models\AutoReply;
use Illuminate\Database\Seeder;

/**
 * [SISTEM KUA] Balasan otomatis WhatsApp bawaan. Admin bisa mengubah/menambah
 * lewat menu Admin → WhatsApp → Balasan Otomatis.
 */
class AutoReplySeeder extends Seeder
{
    public function run(): void
    {
        $balasan = [
            [
                'keyword' => 'terima kasih',
                'match_type' => AutoReply::MATCH_CONTAINS,
                'sort_order' => 10,
                'reply_body' => "Sama-sama. Senang bisa membantu.\n\n"
                    .'Ketik *1* kapan saja untuk mengecek status reservasi Anda.',
            ],
            [
                'keyword' => 'lokasi',
                'match_type' => AutoReply::MATCH_CONTAINS,
                'sort_order' => 20,
                'reply_body' => "*Alamat Kantor Urusan Agama*\n"
                    ."(silakan admin sesuaikan alamat lengkap di menu Admin → WhatsApp)\n\n"
                    .'Datang paling lambat 15 menit sebelum jam reservasi Anda.',
            ],
            [
                'keyword' => 'syarat nikah',
                'match_type' => AutoReply::MATCH_CONTAINS,
                'sort_order' => 30,
                'reply_body' => "*Berkas pendaftaran nikah*\n"
                    ."• Surat pengantar RT/RW & kelurahan (N1-N4)\n"
                    ."• Fotokopi KTP & KK calon pengantin\n"
                    ."• Fotokopi akta kelahiran\n"
                    ."• Pas foto 2x3 dan 4x6 latar biru\n"
                    ."• Akta cerai / surat kematian pasangan (bila ada)\n\n"
                    .'Bawa berkas asli saat datang ke KUA.',
            ],
            [
                'keyword' => 'batal',
                'match_type' => AutoReply::MATCH_CONTAINS,
                'sort_order' => 40,
                'reply_body' => "Pembatalan reservasi dilakukan sendiri lewat aplikasi:\n"
                    ."buka menu *Reservasi Saya* lalu tekan *Batalkan*.\n\n"
                    .'Jika kesulitan, ketik *3* untuk dihubungkan dengan petugas.',
            ],
        ];

        foreach ($balasan as $data) {
            AutoReply::firstOrCreate(
                ['keyword' => $data['keyword']],
                $data + ['is_active' => true],
            );
        }
    }
}
