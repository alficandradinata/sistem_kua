<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// [SISTEM KUA] Kata kunci → balasan otomatis WhatsApp, dikelola admin. Lihat PROGRESS.md.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_replies', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 100);                       // kata kunci yang dicari di pesan warga
            $table->enum('match_type', ['exact', 'contains'])     // cocok persis / mengandung
                ->default('contains');
            $table->text('reply_body');                           // isi balasan
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0); // urutan pengecekan
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_replies');
    }
};
