<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// [SISTEM KUA] Jejak audit: siapa yang menyetujui / menolak / memanggil.
//
// Sistem instansi pemerintah harus bisa menunjuk penanggung jawab setiap
// keputusan. Sebelum ini reservasi hanya menyimpan hasilnya, bukan pelakunya.
//
// Catatan FK: sengaja nullOnDelete, BUKAN cascade seperti kolom domain lain —
// menghapus akun petugas tidak boleh ikut menghapus riwayat reservasi warga.
// Penghapusannya sendiri dijaga di Admin\UserController::destroy.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('rejection_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            $table->foreignId('rejected_by')->nullable()->after('approved_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
        });

        Schema::table('queue_details', function (Blueprint $table) {
            $table->foreignId('called_by')->nullable()->after('called_at')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('attended_by')->nullable()->after('attended_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['approved_at', 'rejected_at']);
        });

        Schema::table('queue_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('called_by');
            $table->dropConstrainedForeignId('attended_by');
        });
    }
};
