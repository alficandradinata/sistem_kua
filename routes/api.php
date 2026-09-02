<?php

// [SISTEM KUA] Endpoint yang dipanggil server Meta (bukan browser).
// Grup "api" tidak memakai session maupun CSRF. Lihat PROGRESS.md.

use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('whatsapp/webhook', [WhatsAppWebhookController::class, 'verify'])
    ->name('whatsapp.webhook.verify');

Route::post('whatsapp/webhook', [WhatsAppWebhookController::class, 'handle'])
    ->name('whatsapp.webhook');
