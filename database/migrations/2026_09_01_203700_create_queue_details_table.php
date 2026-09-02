<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// [SISTEM KUA] Tabel `queue_details` — nomor antrean per reservasi. (5/8) Lihat PROGRESS.md.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('queue_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')               // Satu reservasi = satu nomor antrean
                ->unique()
                ->constrained('reservations')
                ->onDelete('cascade');
            $table->string('queue_number');                   // Nomor antrean (mis. A-012)
            $table->boolean('is_called')->default(false);     // Sudah dipanggil loket?
            $table->timestamp('called_at')->nullable();       // Waktu dipanggil
            $table->timestamp('attended_at')->nullable();     // Waktu dilayani
            $table->text('notes')->nullable();                // Catatan petugas loket
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_details');
    }
};
