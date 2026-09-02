<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * [SISTEM KUA] Membuat laporan rekap otomatis (dipanggil scheduler di routes/console.php).
 * Contoh: php artisan laporan:buat --type=weekly --date=2026-09-06
 */
class GenerateReport extends Command
{
    protected $signature = 'laporan:buat
                            {--type=daily : Jenis laporan: daily, weekly, atau monthly}
                            {--date= : Tanggal acuan (Y-m-d), default hari ini}
                            {--user= : ID user pembuat, default administrator pertama}';

    protected $description = 'Buat/perbarui laporan rekap reservasi untuk satu periode';

    public function handle(): int
    {
        $type = (string) $this->option('type');

        if (! array_key_exists($type, Report::TYPES)) {
            $this->error("Jenis laporan '{$type}' tidak dikenal. Pilih: ".implode(', ', array_keys(Report::TYPES)).'.');

            return self::FAILURE;
        }

        try {
            $date = Carbon::parse($this->option('date') ?: now())->toDateString();
        } catch (\Exception) {
            $this->error('Tanggal acuan tidak valid. Gunakan format Y-m-d.');

            return self::FAILURE;
        }

        $userId = $this->option('user')
            ? (int) $this->option('user')
            : User::role(User::ROLE_ADMIN)->orderBy('id')->value('id');

        if (! $userId || ! User::whereKey($userId)->exists()) {
            $this->error('Tidak ada user pembuat laporan. Buat akun administrator dulu, atau pakai --user=ID.');

            return self::FAILURE;
        }

        $report = Report::generateFor($type, $date, $userId);

        $this->info("Laporan {$report->type_label} periode {$report->period_label} tersimpan: "
            ."total {$report->total_reservations}, selesai {$report->total_completed}, "
            ."ditolak {$report->total_rejected}, dibatalkan {$report->total_cancelled}.");

        return self::SUCCESS;
    }
}
