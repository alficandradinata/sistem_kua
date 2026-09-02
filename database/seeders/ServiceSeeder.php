<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * [SISTEM KUA] Layanan KUA. Lihat PROGRESS.md.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Pendaftaran Nikah', 'duration' => 60, 'fee' => 600000, 'description' => 'Pendaftaran dan pemeriksaan berkas calon pengantin.'],
            ['name' => 'Rujuk', 'duration' => 45, 'fee' => 0, 'description' => 'Pencatatan rujuk bagi pasangan yang telah bercerai.'],
            ['name' => 'Legalisir Buku Nikah', 'duration' => 15, 'fee' => 0, 'description' => 'Pengesahan fotokopi buku nikah.'],
            ['name' => 'Konsultasi Keluarga Sakinah', 'duration' => 30, 'fee' => 0, 'description' => 'Bimbingan dan konsultasi rumah tangga.'],
            ['name' => 'Duplikat Buku Nikah', 'duration' => 30, 'fee' => 0, 'description' => 'Penerbitan buku nikah pengganti yang hilang atau rusak.'],
        ];

        foreach ($services as $data) {
            Service::updateOrCreate(['name' => $data['name']], [...$data, 'is_active' => true]);
        }
    }
}
