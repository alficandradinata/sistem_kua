<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * [SISTEM KUA] Kotak notifikasi in-app.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $warga;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warga = User::factory()->create(['role' => User::ROLE_WARGA]);
    }

    public function test_guest_cannot_open_notifications(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    public function test_user_sees_only_own_notifications(): void
    {
        $lain = User::factory()->create(['role' => User::ROLE_WARGA]);

        Notification::send($this->warga->id, 'Reservasi Anda disetujui.');
        Notification::send($lain->id, 'Notifikasi milik orang lain.');

        $this->actingAs($this->warga)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Reservasi Anda disetujui.')
            ->assertDontSee('Notifikasi milik orang lain.');
    }

    public function test_unread_filter_hides_read_notifications(): void
    {
        Notification::send($this->warga->id, 'Pesan belum dibaca.');
        Notification::send($this->warga->id, 'Pesan sudah dibaca.')->markAsRead();

        $this->actingAs($this->warga)
            ->get(route('notifications.index', ['filter' => 'unread']))
            ->assertOk()
            ->assertSee('Pesan belum dibaca.')
            ->assertDontSee('Pesan sudah dibaca.');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $notification = Notification::send($this->warga->id, 'Reservasi disetujui.');

        $this->actingAs($this->warga)
            ->patch(route('notifications.read', $notification))
            ->assertRedirect();

        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_user_cannot_mark_others_notification_as_read(): void
    {
        $lain = User::factory()->create(['role' => User::ROLE_WARGA]);
        $notification = Notification::send($lain->id, 'Punya orang lain.');

        $this->actingAs($this->warga)
            ->patch(route('notifications.read', $notification))
            ->assertForbidden();

        $this->assertFalse($notification->fresh()->is_read);
    }

    public function test_user_can_mark_all_as_read(): void
    {
        Notification::send($this->warga->id, 'Satu.');
        Notification::send($this->warga->id, 'Dua.');
        $lain = User::factory()->create(['role' => User::ROLE_WARGA]);
        $milikLain = Notification::send($lain->id, 'Punya orang lain.');

        $this->actingAs($this->warga)
            ->patch(route('notifications.readAll'))
            ->assertRedirect();

        $this->assertSame(0, $this->warga->unreadNotificationCount());
        $this->assertFalse($milikLain->fresh()->is_read);
    }

    public function test_user_can_delete_own_notification(): void
    {
        $notification = Notification::send($this->warga->id, 'Hapus saya.');

        $this->actingAs($this->warga)
            ->delete(route('notifications.destroy', $notification))
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_user_cannot_delete_others_notification(): void
    {
        $lain = User::factory()->create(['role' => User::ROLE_WARGA]);
        $notification = Notification::send($lain->id, 'Punya orang lain.');

        $this->actingAs($this->warga)
            ->delete(route('notifications.destroy', $notification))
            ->assertForbidden();

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }

    public function test_navbar_shows_unread_badge(): void
    {
        Notification::send($this->warga->id, 'Reservasi disetujui.');

        $this->actingAs($this->warga)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('notifications.index'));

        $this->assertSame(1, $this->warga->unreadNotificationCount());
    }
}
