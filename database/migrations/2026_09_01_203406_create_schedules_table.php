<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// [SISTEM KUA] Tabel `schedules` — jam operasional per hari. (2/8) Lihat PROGRESS.md.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->integer('day_of_week');                   // 0=Minggu, 1=Senin, ... 6=Sabtu
            $table->time('open_time')->nullable();            // Jam buka pelayanan
            $table->time('close_time')->nullable();           // Jam tutup pelayanan
            $table->boolean('is_active')->default(true);      // Hari kerja aktif / libur
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
