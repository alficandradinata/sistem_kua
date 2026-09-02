<?php

namespace Tests\Feature\WhatsApp;

use App\Models\AutoReply;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\AutoReplyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * [SISTEM KUA] Panel admin WhatsApp, balasan otomatis, dan inbox petugas.
 */
class PanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.driver' => 'log',
            'whatsapp.enabled' => true,
            'whatsapp.auto_reply_cooldown_minutes' => 0,
        ]);

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);
    }

    // --- Hak akses ---

    public function test_petugas_cannot_open_admin_whatsapp_panel(): void
    {
        $this->actingAs($this->petugas)->get(route('admin.whatsapp.index'))->assertForbidden();
    }

    public function test_warga_cannot_open_petugas_inbox(): void
    {
        $warga = User::factory()->create(['role' => User::ROLE_WARGA]);

        $this->actingAs($warga)->get(route('petugas.whatsapp.index'))->assertForbidden();
    }

    public function test_admin_can_open_panel_and_petugas_can_open_inbox(): void
    {
        $this->actingAs($this->admin)->get(route('admin.whatsapp.index'))->assertOk();
        $this->actingAs($this->petugas)->get(route('petugas.whatsapp.index'))->assertOk();
    }

    // --- Balasan otomatis ---

    public function test_admin_can_manage_auto_replies(): void
    {
        $this->actingAs($this->admin)->post(route('admin.whatsapp.replies.store'), [
            'keyword' => 'syarat rujuk',
            'match_type' => AutoReply::MATCH_CONTAINS,
            'reply_body' => 'Bawa buku nikah dan KTP.',
            'is_active' => '1',
            'sort_order' => 5,
        ])->assertRedirect();

        $reply = AutoReply::first();
        $this->assertSame('syarat rujuk', $reply->keyword);

        $this->actingAs($this->admin)->put(route('admin.whatsapp.replies.update', $reply), [
            'keyword' => 'syarat rujuk',
            'match_type' => AutoReply::MATCH_EXACT,
            'reply_body' => 'Bawa buku nikah, KTP, dan surat pengantar.',
            'is_active' => '1',
            'sort_order' => 5,
        ])->assertRedirect();

        $this->assertStringContainsString('surat pengantar', $reply->fresh()->reply_body);

        $this->actingAs($this->admin)
            ->delete(route('admin.whatsapp.replies.destroy', $reply))
            ->assertRedirect();

        $this->assertDatabaseCount('auto_replies', 0);
    }

    public function test_auto_reply_keyword_must_be_unique(): void
    {
        AutoReply::create([
            'keyword' => 'lokasi', 'match_type' => AutoReply::MATCH_CONTAINS,
            'reply_body' => 'Jl. Contoh No. 1', 'is_active' => true, 'sort_order' => 0,
        ]);

        $this->actingAs($this->admin)->post(route('admin.whatsapp.replies.store'), [
            'keyword' => 'lokasi',
            'match_type' => AutoReply::MATCH_CONTAINS,
            'reply_body' => 'Duplikat',
        ])->assertSessionHasErrors('keyword');
    }

    public function test_admin_keyword_wins_over_default_menu(): void
    {
        AutoReply::create([
            'keyword' => 'status', 'match_type' => AutoReply::MATCH_CONTAINS,
            'reply_body' => 'Balasan khusus dari admin.', 'is_active' => true, 'sort_order' => 0,
        ]);

        $balasan = app(AutoReplyResolver::class)->resolve('6281234567890', 'status');

        $this->assertStringContainsString('Balasan khusus dari admin.', $balasan);
    }

    public function test_inactive_keyword_is_skipped(): void
    {
        AutoReply::create([
            'keyword' => 'status', 'match_type' => AutoReply::MATCH_CONTAINS,
            'reply_body' => 'Balasan nonaktif.', 'is_active' => false, 'sort_order' => 0,
        ]);

        $balasan = app(AutoReplyResolver::class)->resolve('6281234567890', 'status');

        $this->assertStringNotContainsString('Balasan nonaktif.', $balasan);
    }

    public function test_cooldown_prevents_reply_storm(): void
    {
        config(['whatsapp.auto_reply_cooldown_minutes' => 5]);

        $resolver = app(AutoReplyResolver::class);

        // Balasan pertama keluar, pesan kedua dari nomor yang sama diabaikan —
        // tanpa menunggu antrean memproses pengiriman yang pertama.
        $this->assertNotNull($resolver->resolve('6281234567890', 'halo'));
        $this->assertNull($resolver->resolve('081234567890', 'halo lagi'));

        // Nomor lain tetap dilayani.
        $this->assertNotNull($resolver->resolve('6289999999999', 'halo'));
    }

    public function test_cooldown_can_be_disabled(): void
    {
        config(['whatsapp.auto_reply_cooldown_minutes' => 0]);

        $resolver = app(AutoReplyResolver::class);

        $this->assertNotNull($resolver->resolve('6281234567890', 'halo'));
        $this->assertNotNull($resolver->resolve('6281234567890', 'halo'));
    }

    // --- Kirim uji & balasan manual petugas ---

    public function test_admin_test_message_rejects_invalid_number(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.whatsapp.test'), ['to' => 'abc'])
            ->assertSessionHasErrors('to');
    }

    public function test_admin_can_send_test_message(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.whatsapp.test'), ['to' => '081234567890'])
            ->assertSessionHas('status');

        $this->assertSame(1, WhatsAppMessage::outbound()->count());
    }

    public function test_petugas_reply_blocked_outside_session_window(): void
    {
        $this->actingAs($this->petugas)->post(route('petugas.whatsapp.reply'), [
            'nomor' => '081234567890',
            'body' => 'Halo, ada yang bisa dibantu?',
        ])->assertSessionHasErrors('body');

        $this->assertSame(0, WhatsAppMessage::outbound()->count());
    }

    public function test_petugas_can_reply_within_session_window(): void
    {
        WhatsAppMessage::record([
            'direction' => WhatsAppMessage::DIRECTION_IN,
            'wa_number' => '6281234567890',
            'body' => 'Pak, saya terlambat.',
            'status' => WhatsAppMessage::STATUS_RECEIVED,
        ]);

        $this->actingAs($this->petugas)->post(route('petugas.whatsapp.reply'), [
            'nomor' => '081234567890',
            'body' => 'Baik, silakan datang sebelum pukul 14.00.',
        ])->assertRedirect(route('petugas.whatsapp.index', ['nomor' => '6281234567890']));

        $balasan = WhatsAppMessage::outbound()->first();
        $this->assertStringContainsString('pukul 14.00', $balasan->body);
        $this->assertFalse($balasan->is_auto_reply);
    }

    public function test_inbox_shows_conversation(): void
    {
        WhatsAppMessage::record([
            'direction' => WhatsAppMessage::DIRECTION_IN,
            'wa_number' => '6281234567890',
            'body' => 'Pak, saya terlambat.',
            'status' => WhatsAppMessage::STATUS_RECEIVED,
        ]);

        $this->actingAs($this->petugas)
            ->get(route('petugas.whatsapp.index', ['nomor' => '081234567890']))
            ->assertOk()
            ->assertSee('Pak, saya terlambat.');
    }
}
