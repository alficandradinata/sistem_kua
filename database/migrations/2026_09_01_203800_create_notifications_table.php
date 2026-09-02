<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// [SISTEM KUA] Tabel `notifications` — notifikasi warga. (6/8) Lihat PROGRESS.md.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')                      // Penerima notifikasi
                ->constrained('users')
                ->onDelete('cascade');
            $table->text('message');                          // Isi pesan notifikasi
            $table->enum('type', ['email', 'sms', 'in-app'])
                ->default('in-app');                          // Kanal notifikasi
            $table->boolean('is_read')->default(false);       // Status dibaca
            $table->timestamp('sent_at')->nullable();         // Waktu notifikasi dikirim
            $table->timestamps();

            $table->index(['user_id', 'is_read']);            // Query notifikasi belum dibaca per user
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
