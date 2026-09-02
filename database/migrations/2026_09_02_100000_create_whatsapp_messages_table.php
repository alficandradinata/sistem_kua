<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// [SISTEM KUA] Riwayat pesan WhatsApp masuk & keluar. Lihat PROGRESS.md.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->enum('direction', ['in', 'out']);          // masuk dari warga / keluar dari sistem
            $table->string('wa_number', 20);                   // nomor lawan bicara, format 62…
            $table->foreignId('user_id')                       // terisi bila nomor dikenali
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('body');                              // isi pesan
            $table->string('wamid')->nullable()->unique();     // id pesan dari Meta (anti-proses ganda)
            $table->string('status')->default('sent');         // sent/failed/received
            $table->text('error')->nullable();                 // pesan galat bila gagal kirim
            $table->boolean('is_auto_reply')->default(false);  // balasan otomatis vs balasan petugas
            $table->json('payload')->nullable();               // payload mentah dari webhook
            $table->timestamps();

            $table->index(['wa_number', 'created_at']);        // query percakapan per nomor
            $table->index(['direction', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
