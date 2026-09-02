<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// [SISTEM KUA] Tabel `reports` — rekap laporan. (8/8) Lihat PROGRESS.md.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');                      // Tanggal / periode laporan
            $table->enum('report_type', ['daily', 'weekly', 'monthly'])
                ->default('daily');                           // Jenis laporan
            $table->integer('total_reservations')->default(0); // Total reservasi pada periode
            $table->integer('total_completed')->default(0);   // Total reservasi selesai
            $table->integer('total_cancelled')->default(0);   // Total reservasi dibatalkan
            $table->foreignId('generated_by')                 // Petugas yang membuat laporan
                ->constrained('users')
                ->onDelete('cascade');
            $table->timestamps();

            $table->index(['report_date', 'report_type']);    // Query laporan per periode & jenis
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
