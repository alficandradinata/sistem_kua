<?php

use App\Models\Reservation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// [SISTEM KUA] Pisahkan "ditolak petugas" dari "dibatalkan warga".
//
// Sebelum ini keduanya disimpan sebagai `cancelled` dan alasan penolakan cuma
// ditempel ke kolom `notes` milik warga, sehingga laporan resmi tidak bisa
// membedakan berkas yang ditolak KUA dari reservasi yang dibatalkan sendiri.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'completed', 'cancelled', 'rejected'])
                ->default('pending')
                ->change();

            // Alasan penolakan petugas — dipisah dari `notes` yang milik warga.
            $table->text('rejection_reason')->nullable()->after('notes');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->integer('total_rejected')->default(0)->after('total_cancelled');
        });

        $this->pindahkanDataLama();
    }

    public function down(): void
    {
        // Kembalikan yang ditolak menjadi dibatalkan supaya muat di enum lama.
        DB::table('reservations')
            ->where('status', Reservation::STATUS_REJECTED)
            ->update(['status' => Reservation::STATUS_CANCELLED]);

        Schema::table('reservations', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'completed', 'cancelled'])
                ->default('pending')
                ->change();

            $table->dropColumn('rejection_reason');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('total_rejected');
        });
    }

    /**
     * Reservasi lama yang ditolak petugas dikenali dari penanda yang dulu
     * ditulis ke `notes`. Alasannya dipindah ke kolomnya sendiri dan penanda
     * itu dibersihkan dari catatan warga.
     */
    private function pindahkanDataLama(): void
    {
        $penanda = '[Ditolak petugas] ';

        DB::table('reservations')
            ->where('status', Reservation::STATUS_CANCELLED)
            ->where('notes', 'like', '%'.$penanda.'%')
            ->orderBy('id')
            ->each(function ($row) use ($penanda) {
                $posisi = strpos($row->notes, $penanda);
                $alasan = trim(substr($row->notes, $posisi + strlen($penanda)));
                $catatan = trim(substr($row->notes, 0, $posisi));

                DB::table('reservations')->where('id', $row->id)->update([
                    'status' => Reservation::STATUS_REJECTED,
                    'rejection_reason' => $alasan !== '' ? $alasan : null,
                    'notes' => $catatan !== '' ? $catatan : null,
                ]);
            });
    }
};
