<?php

use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// [SISTEM KUA] Samakan format nomor HP lama ke 62… supaya bisa dicocokkan dengan
// nomor pengirim WhatsApp. Nomor baru dinormalisasi otomatis oleh mutator di User.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNotNull('phone')->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['phone' => PhoneNumber::normalize($user->phone)]);
                }
            });
    }

    public function down(): void
    {
        // Format lama (08…) tidak disimpan, jadi tidak ada yang bisa dikembalikan.
    }
};
