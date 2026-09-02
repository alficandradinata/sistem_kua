<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * [SISTEM KUA] Perbaikan integritas data master sebelum panel admin dibuka. (10)
 *
 * 1. schedules.day_of_week WAJIB unik — satu hari hanya boleh punya satu jadwal,
 *    kalau tidak Schedule::forDate() bisa mengembalikan baris yang salah.
 * 2. service_slots (service_id, slot_start_time) WAJIB unik — kuota dihitung
 *    berdasarkan jam mulai, slot ganda membuat perhitungan kuota kacau.
 * 3. reservations.service_id diubah dari CASCADE ke RESTRICT — menghapus layanan
 *    tidak boleh diam-diam menghapus riwayat reservasi warga. Layanan yang sudah
 *    dipakai harus dinonaktifkan (is_active = false), bukan dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->unique('day_of_week');
        });

        Schema::table('service_slots', function (Blueprint $table) {
            $table->unique(['service_id', 'slot_start_time'], 'service_slots_service_time_unique');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
        });

        Schema::table('service_slots', function (Blueprint $table) {
            $table->dropUnique('service_slots_service_time_unique');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropUnique(['day_of_week']);
        });
    }
};
