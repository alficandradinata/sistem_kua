<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// [SISTEM KUA] Tabel `service_slots` — slot & kuota antrean. (3/8) Lihat PROGRESS.md.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')                   // Relasi ke layanan
                ->constrained('services')
                ->onDelete('cascade');
            $table->integer('quota_per_day')->default(0);     // Kuota antrean per hari
            $table->time('slot_start_time');                  // Jam mulai slot
            $table->integer('slot_duration')->default(30);    // Durasi tiap slot (menit)
            $table->boolean('is_active')->default(true);      // Status aktif slot
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_slots');
    }
};
