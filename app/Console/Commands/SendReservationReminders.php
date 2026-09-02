<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * [SISTEM KUA] Pengingat H-1 untuk reservasi yang sudah disetujui.
 * Dijadwalkan di routes/console.php; aman dijalankan berkali-kali karena
 * setiap reservasi ditandai `reminded_at` setelah pengingatnya terkirim.
 */
class SendReservationReminders extends Command
{
    protected $signature = 'pengingat:reservasi
                            {--date= : Tanggal jadwal yang diingatkan (Y-m-d), default besok}';

    protected $description = 'Kirim notifikasi pengingat H-1 ke warga yang punya reservasi besok';

    public function handle(): int
    {
        try {
            $target = Carbon::parse($this->option('date') ?: now()->addDay())->toDateString();
        } catch (\Exception) {
            $this->error('Tanggal tidak valid. Gunakan format Y-m-d.');

            return self::FAILURE;
        }

        $reservations = Reservation::approved()
            ->forDate($target)
            ->whereNull('reminded_at')
            ->with(['service', 'queueDetail'])
            ->get();

        foreach ($reservations as $reservation) {
            $reservation->sendReminder();
        }

        $this->info($reservations->isEmpty()
            ? "Tidak ada reservasi disetujui pada {$target} yang perlu diingatkan."
            : "{$reservations->count()} pengingat dikirim untuk jadwal {$target}.");

        return self::SUCCESS;
    }
}
