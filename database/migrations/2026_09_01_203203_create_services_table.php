<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// [SISTEM KUA] Tabel `services` — master layanan KUA. (1/8) Lihat PROGRESS.md.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Nama layanan (mis. Pendaftaran Nikah)
            $table->text('description')->nullable();          // Deskripsi layanan
            $table->integer('duration')->default(30);         // Durasi layanan dalam menit
            $table->decimal('fee', 10, 2)->default(0);        // Biaya layanan
            $table->boolean('is_active')->default(true);      // Status aktif layanan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
