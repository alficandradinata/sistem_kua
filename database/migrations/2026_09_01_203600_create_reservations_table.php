<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// [SISTEM KUA] Tabel `reservations` — TABEL INTI. (4/8) Lihat PROGRESS.md.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')                      // Pemohon / masyarakat
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('service_id')                   // Layanan yang direservasi
                ->constrained('services')
                ->onDelete('cascade');
            $table->date('reservation_date');                 // Tanggal kedatangan
            $table->time('reservation_time');                 // Jam slot antrean
            $table->enum('status', ['pending', 'approved', 'completed', 'cancelled'])
                ->default('pending');                         // Status reservasi
            $table->text('notes')->nullable();                // Catatan tambahan dari pemohon
            $table->timestamps();

            $table->index(['user_id', 'reservation_date']);   // Query riwayat reservasi user
            $table->index(['service_id', 'status']);          // Query rekap per layanan & status
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
