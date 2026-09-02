<?php

namespace App\Services\WhatsApp;

use App\Models\AutoReply;
use App\Models\Holiday;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Cache;

/**
 * [SISTEM KUA] Menyusun balasan otomatis untuk chat WhatsApp yang masuk.
 *
 * Urutan pemeriksaan: kata kunci yang diatur admin → menu angka → sapaan default.
 * Kalau nomor pengirim terdaftar sebagai warga, balasan status memuat data
 * reservasinya sendiri.
 */
class AutoReplyResolver
{
    /**
     * Balasan untuk sebuah pesan, atau null bila tidak perlu dibalas
     * (masih dalam masa jeda anti-spam).
     */
    public function resolve(string $from, string $message): ?string
    {
        if ($this->baruSajaDibalas($from)) {
            return null;
        }

        $user = User::findByPhone($from);
        $teks = mb_strtolower(trim($message));

        $balasan = $this->dariKataKunciAdmin($teks)
            ?? $this->dariMenu($teks, $user)
            ?? $this->sapaan($user);

        $this->tandaiSudahDibalas($from);

        return $balasan.$this->catatanJamLayanan();
    }

    /**
     * Jangan membalas nomor yang sama berkali-kali dalam waktu singkat —
     * mencegah balas-berbalas dan penggunaan kuota Meta yang sia-sia.
     *
     * Penandanya disimpan di cache, bukan dibaca dari tabel pesan keluar:
     * pengiriman berjalan lewat antrean, jadi barisnya baru ada beberapa saat
     * kemudian dan pengecekan berbasis tabel bisa kebobolan.
     */
    private function baruSajaDibalas(string $from): bool
    {
        return $this->jedaMenit() > 0 && Cache::has($this->kunciJeda($from));
    }

    private function tandaiSudahDibalas(string $from): void
    {
        if ($this->jedaMenit() > 0) {
            Cache::put($this->kunciJeda($from), true, now()->addMinutes($this->jedaMenit()));
        }
    }

    private function jedaMenit(): int
    {
        return (int) config('whatsapp.auto_reply_cooldown_minutes', 2);
    }

    private function kunciJeda(string $from): string
    {
        return 'wa-autoreply:'.PhoneNumber::normalize($from);
    }

    private function dariKataKunciAdmin(string $teks): ?string
    {
        return AutoReply::match($teks)?->reply_body;
    }

    private function dariMenu(string $teks, ?User $user): ?string
    {
        $statusKata = ['1', 'status', 'antrean', 'antrian', 'cek'];
        $jadwalKata = ['2', 'jadwal', 'jam', 'syarat', 'layanan', 'buka'];
        $petugasKata = ['3', 'petugas', 'operator', 'admin', 'manusia'];

        return match (true) {
            $this->cocok($teks, $statusKata) => $this->statusReservasi($user),
            $this->cocok($teks, $jadwalKata) => $this->jadwalDanLayanan(),
            $this->cocok($teks, $petugasKata) => $this->diteruskanKePetugas(),
            default => null,
        };
    }

    /**
     * @param  array<string>  $kata
     */
    private function cocok(string $teks, array $kata): bool
    {
        foreach ($kata as $k) {
            if ($teks === $k || str_contains($teks, $k)) {
                return true;
            }
        }

        return false;
    }

    private function statusReservasi(?User $user): string
    {
        if (! $user) {
            return "Nomor ini belum terdaftar di Sistem Reservasi KUA.\n\n"
                .'Silakan daftar dulu lewat '.url('/register')
                .' dan cantumkan nomor WhatsApp ini agar status reservasi bisa dicek dari sini.';
        }

        $reservasi = Reservation::forUser($user->id)
            ->upcoming()
            ->with(['service', 'queueDetail'])
            ->orderBy('reservation_date')
            ->orderBy('reservation_time')
            ->get();

        if ($reservasi->isEmpty()) {
            return "Halo {$user->name}, saat ini tidak ada reservasi aktif atas nama Anda.\n\n"
                .'Buat reservasi baru di '.url('/reservasi/buat');
        }

        $baris = $reservasi->map(function (Reservation $r) {
            $antrean = $r->queueDetail
                ? "\n  Nomor antrean: {$r->queueDetail->queue_number}"
                : "\n  Nomor antrean terbit setelah disetujui petugas.";

            return "• {$r->service->name}\n"
                ."  {$r->full_date}, {$r->formatted_time}\n"
                ."  Status: {$r->status_label}".$antrean;
        })->implode("\n\n");

        return "Halo {$user->name}, berikut reservasi aktif Anda:\n\n{$baris}";
    }

    private function jadwalDanLayanan(): string
    {
        $jadwal = Schedule::active()->orderBy('day_of_week')->get()
            ->map(fn (Schedule $s) => "• {$s->day_name}: {$s->operational_hours}")
            ->implode("\n");

        $layanan = Service::active()->orderBy('name')->pluck('name')
            ->map(fn ($nama) => "• {$nama}")
            ->implode("\n");

        return "*Jam Layanan KUA*\n".($jadwal ?: '• Belum diatur')
            ."\n\n*Layanan yang tersedia*\n".($layanan ?: '• Belum ada')
            ."\n\nPendaftaran antrean online: ".url('/');
    }

    private function diteruskanKePetugas(): string
    {
        return "Baik, pesan Anda kami teruskan ke petugas KUA.\n\n"
            .'Mohon tunggu; petugas akan membalas melalui nomor ini pada jam layanan.';
    }

    private function sapaan(?User $user): string
    {
        $nama = $user ? " {$user->name}" : '';

        return "Halo{$nama}, ini layanan WhatsApp Kantor Urusan Agama.\n\n"
            ."Balas dengan angka:\n"
            ."1 — Cek status reservasi & nomor antrean\n"
            ."2 — Jam layanan, daftar layanan & syarat\n"
            ."3 — Bicara dengan petugas\n\n"
            .'Reservasi antrean online: '.url('/');
    }

    /**
     * Tambahan pemberitahuan bila pesan masuk di luar jam layanan atau hari libur.
     */
    private function catatanJamLayanan(): string
    {
        $hariIni = today()->toDateString();

        if (Holiday::isHoliday($hariIni)) {
            return "\n\n_Catatan: hari ini KUA libur. Balasan petugas menyusul pada hari kerja berikutnya._";
        }

        $jadwal = Schedule::forDate($hariIni);

        if (! $jadwal || ! $jadwal->is_active) {
            return "\n\n_Catatan: hari ini KUA tidak melayani. Balasan petugas menyusul pada hari kerja berikutnya._";
        }

        if (! $jadwal->isWithinHours(now()->format('H:i:s'))) {
            return "\n\n_Catatan: pesan ini masuk di luar jam layanan ({$jadwal->operational_hours}). "
                .'Petugas membalas pada jam layanan berikutnya._';
        }

        return '';
    }
}
