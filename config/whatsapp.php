<?php

/*
 * [SISTEM KUA] Konfigurasi kanal WhatsApp.
 *
 * driver "log"   → dipakai saat dev/test. Tidak menghubungi Meta; pesan hanya
 *                  dicatat di log & tabel whatsapp_messages.
 * driver "cloud" → WhatsApp Cloud API resmi Meta (produksi).
 *
 * Ingat: Cloud API hanya mengizinkan teks bebas dalam 24 jam sejak pesan
 * terakhir dari warga. Di luar itu wajib memakai template yang sudah disetujui
 * Meta — isi namanya di WHATSAPP_TEMPLATE.
 */

return [

    'driver' => env('WHATSAPP_DRIVER', 'log'),

    // Matikan totalnya (tidak ada pesan keluar sama sekali) dengan false.
    'enabled' => (bool) env('WHATSAPP_ENABLED', true),

    // Nomor WA resmi KUA untuk tombol "Chat WhatsApp" (format 62…).
    'contact_number' => env('WHATSAPP_CONTACT_NUMBER'),

    'cloud' => [
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'token' => env('WHATSAPP_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
    ],

    // Template Utility yang disetujui Meta, dipakai di luar jendela 24 jam.
    // Harus punya tepat satu parameter body ({{1}}) yang diisi isi notifikasi.
    'template' => [
        'name' => env('WHATSAPP_TEMPLATE', 'notifikasi_kua'),
        'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'id'),
    ],

    // Jendela layanan Cloud API (jam) sejak pesan terakhir warga.
    'session_window_hours' => 24,

    // Jangan membalas otomatis nomor yang sama lebih sering dari ini (menit).
    'auto_reply_cooldown_minutes' => (int) env('WHATSAPP_AUTOREPLY_COOLDOWN', 2),

];
