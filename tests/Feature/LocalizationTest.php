<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * [SISTEM KUA] Pesan validasi & UI berbahasa Indonesia.
 */
class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_is_indonesian(): void
    {
        $this->assertSame('id', config('app.locale'));
        $this->assertSame('en', config('app.fallback_locale'));
    }

    public function test_required_message_is_in_indonesian(): void
    {
        $this->post(route('login'), ['email' => '', 'password' => ''])
            ->assertSessionHasErrors([
                'email' => 'Kolom email wajib diisi.',
                'password' => 'Kolom kata sandi wajib diisi.',
            ]);
    }

    public function test_login_failure_message_is_in_indonesian(): void
    {
        User::factory()->create(['email' => 'warga@contoh.test', 'role' => User::ROLE_WARGA]);

        $this->post(route('login'), [
            'email' => 'warga@contoh.test',
            'password' => 'salah-total',
        ])->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
    }

    public function test_unique_and_email_messages_are_in_indonesian(): void
    {
        User::factory()->create(['email' => 'sudah@ada.test']);

        $this->post(route('register'), [
            'name' => 'Uji',
            'email' => 'sudah@ada.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors(['email' => 'Kolom email sudah digunakan.']);

        $this->post(route('register'), [
            'name' => 'Uji',
            'email' => 'bukan-email',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors(['email' => 'Kolom email harus berupa alamat email yang valid.']);
    }

    public function test_custom_message_for_reservation_date(): void
    {
        $service = Service::create([
            'name' => 'Pendaftaran Nikah', 'description' => 'x',
            'duration' => 60, 'fee' => 0, 'is_active' => true,
        ]);
        $warga = User::factory()->create(['role' => User::ROLE_WARGA]);

        $this->actingAs($warga)->post(route('reservations.store'), [
            'service_id' => $service->id,
            'reservation_date' => today()->subDay()->toDateString(),
            'slot_id' => 1,
        ])->assertSessionHasErrors(['reservation_date' => 'Tanggal reservasi harus setelah hari ini.']);
    }

    public function test_ui_strings_are_translated(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Kata Sandi')
            ->assertSee('Ingat saya')
            ->assertSee('Lupa kata sandi?');
    }

    public function test_attribute_names_use_indonesian_labels(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // report_date sengaja dikosongkan; labelnya harus "tanggal acuan".
        $this->actingAs($admin)->post(route('admin.reports.store'), [
            'report_type' => 'daily',
        ])->assertSessionHasErrors(['report_date' => 'Kolom tanggal acuan wajib diisi.']);
    }
}
