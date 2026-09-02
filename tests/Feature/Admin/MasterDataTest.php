<?php

namespace Tests\Feature\Admin;

use App\Models\Holiday;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\ServiceSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * [SISTEM KUA] Panel admin master data.
 */
class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->service = Service::create([
            'name' => 'Pendaftaran Nikah', 'description' => 'x',
            'duration' => 60, 'fee' => 600000, 'is_active' => true,
        ]);
    }

    // --- Hak akses ---

    public function test_petugas_cannot_access_admin_master_data(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas)->get(route('admin.services.index'))->assertForbidden();
        $this->actingAs($petugas)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_can_open_all_master_data_pages(): void
    {
        foreach (['services.index', 'schedules.index', 'slots.index', 'holidays.index', 'users.index'] as $route) {
            $this->actingAs($this->admin)->get(route("admin.{$route}"))->assertOk();
        }
    }

    // --- Layanan ---

    public function test_admin_can_create_service(): void
    {
        $this->actingAs($this->admin)->post(route('admin.services.store'), [
            'name' => 'Legalisir', 'description' => 'Tes',
            'duration' => 15, 'fee' => 0, 'is_active' => '1',
        ])->assertRedirect(route('admin.services.index'));

        $this->assertDatabaseHas('services', ['name' => 'Legalisir', 'duration' => 15, 'is_active' => true]);
    }

    public function test_service_name_must_be_unique(): void
    {
        $this->actingAs($this->admin)->post(route('admin.services.store'), [
            'name' => 'Pendaftaran Nikah', 'duration' => 30, 'fee' => 0,
        ])->assertSessionHasErrors('name');
    }

    public function test_service_used_by_reservation_cannot_be_deleted(): void
    {
        Reservation::create([
            'user_id' => User::factory()->create()->id,
            'service_id' => $this->service->id,
            'reservation_date' => Carbon::tomorrow()->toDateString(),
            'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.services.destroy', $this->service))
            ->assertSessionHasErrors('service');

        $this->assertDatabaseHas('services', ['id' => $this->service->id]);
    }

    public function test_unused_service_can_be_deleted(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('admin.services.destroy', $this->service))
            ->assertRedirect(route('admin.services.index'));

        $this->assertDatabaseMissing('services', ['id' => $this->service->id]);
    }

    // --- Jam operasional ---

    public function test_admin_can_update_schedules(): void
    {
        $days = [];
        foreach (range(0, 6) as $d) {
            $days[$d] = ['is_active' => $d >= 1 && $d <= 5 ? '1' : '0', 'open_time' => '07:30', 'close_time' => '14:30'];
        }

        $this->actingAs($this->admin)->put(route('admin.schedules.update'), ['days' => $days])->assertRedirect();

        $this->assertDatabaseHas('schedules', ['day_of_week' => 1, 'is_active' => true, 'open_time' => '07:30:00']);
        $this->assertDatabaseHas('schedules', ['day_of_week' => 0, 'is_active' => false, 'open_time' => null]);
    }

    public function test_active_day_requires_open_and_close_time(): void
    {
        $days = [];
        foreach (range(0, 6) as $d) {
            $days[$d] = ['is_active' => $d === 1 ? '1' : '0', 'open_time' => '', 'close_time' => ''];
        }

        $this->actingAs($this->admin)->put(route('admin.schedules.update'), ['days' => $days])
            ->assertSessionHasErrors('days.1.open_time');
    }

    // --- Slot ---

    public function test_admin_can_create_slot(): void
    {
        $this->actingAs($this->admin)->post(route('admin.slots.store'), [
            'service_id' => $this->service->id, 'slot_start_time' => '08:00',
            'slot_duration' => 60, 'quota_per_day' => 5, 'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('service_slots', ['service_id' => $this->service->id, 'slot_start_time' => '08:00:00']);
    }

    public function test_duplicate_slot_time_for_same_service_is_rejected(): void
    {
        ServiceSlot::create([
            'service_id' => $this->service->id, 'slot_start_time' => '08:00:00',
            'slot_duration' => 60, 'quota_per_day' => 5, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post(route('admin.slots.store'), [
            'service_id' => $this->service->id, 'slot_start_time' => '08:00',
            'slot_duration' => 60, 'quota_per_day' => 5,
        ])->assertSessionHasErrors('slot_start_time');
    }

    public function test_slot_time_cannot_change_while_active_reservations_exist(): void
    {
        $slot = ServiceSlot::create([
            'service_id' => $this->service->id, 'slot_start_time' => '08:00:00',
            'slot_duration' => 60, 'quota_per_day' => 5, 'is_active' => true,
        ]);
        Reservation::create([
            'user_id' => User::factory()->create()->id,
            'service_id' => $this->service->id,
            'reservation_date' => Carbon::tomorrow()->toDateString(),
            'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_APPROVED,
        ]);

        $this->actingAs($this->admin)->put(route('admin.slots.update', $slot), [
            'service_id' => $this->service->id, 'slot_start_time' => '09:00',
            'slot_duration' => 60, 'quota_per_day' => 5,
        ])->assertSessionHasErrors('slot_start_time');

        $this->assertSame('08:00:00', $slot->fresh()->slot_start_time);
    }

    // --- Hari libur ---

    public function test_admin_can_create_holiday_and_it_blocks_reservation_date(): void
    {
        $date = Carbon::tomorrow()->addWeek()->toDateString();

        $this->actingAs($this->admin)->post(route('admin.holidays.store'), [
            'holiday_date' => $date, 'description' => 'Cuti Bersama', 'is_active' => '1',
        ])->assertRedirect();

        $this->assertTrue(Holiday::isHoliday($date));
    }

    public function test_duplicate_holiday_date_is_rejected(): void
    {
        Holiday::create(['holiday_date' => '2026-12-25', 'description' => 'Natal', 'is_active' => true]);

        $this->actingAs($this->admin)->post(route('admin.holidays.store'), [
            'holiday_date' => '2026-12-25', 'description' => 'Duplikat',
        ])->assertSessionHasErrors('holiday_date');
    }

    public function test_holiday_clashing_with_existing_reservations_warns_admin(): void
    {
        $date = Carbon::tomorrow()->addWeek()->toDateString();
        Reservation::create([
            'user_id' => User::factory()->create()->id,
            'service_id' => $this->service->id,
            'reservation_date' => $date,
            'reservation_time' => '08:00:00',
            'status' => Reservation::STATUS_APPROVED,
        ]);

        $this->actingAs($this->admin)->post(route('admin.holidays.store'), [
            'holiday_date' => $date, 'description' => 'Libur Dadakan', 'is_active' => '1',
        ])->assertSessionHas('status', fn (string $msg) => str_contains($msg, '1 reservasi aktif'));
    }

    // --- Pengguna ---

    public function test_admin_can_create_petugas_account(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Petugas Baru', 'email' => 'baru@kua.test', 'phone' => '0812',
            'role' => User::ROLE_PETUGAS, 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'baru@kua.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isPetugas());
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name, 'email' => $this->admin->email,
            'role' => User::ROLE_WARGA,
        ])->assertSessionHasErrors('role');

        $this->assertTrue($this->admin->fresh()->isAdmin());
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->admin))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_user_with_reservations_cannot_be_deleted(): void
    {
        $warga = User::factory()->create(['role' => User::ROLE_WARGA]);
        Reservation::create([
            'user_id' => $warga->id, 'service_id' => $this->service->id,
            'reservation_date' => Carbon::tomorrow()->toDateString(),
            'reservation_time' => '08:00:00', 'status' => Reservation::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin)->delete(route('admin.users.destroy', $warga))
            ->assertSessionHasErrors('user');
    }

    public function test_updating_user_without_password_keeps_old_password(): void
    {
        $warga = User::factory()->create(['role' => User::ROLE_WARGA]);
        $hash = $warga->password;

        $this->actingAs($this->admin)->put(route('admin.users.update', $warga), [
            'name' => 'Nama Baru', 'email' => $warga->email, 'role' => User::ROLE_PETUGAS, 'password' => '',
        ])->assertRedirect();

        $warga->refresh();
        $this->assertSame('Nama Baru', $warga->name);
        $this->assertTrue($warga->isPetugas());
        $this->assertSame($hash, $warga->password);
    }

    // --- Integrasi: perubahan master data langsung terasa di alur warga ---

    public function test_deactivating_service_removes_it_from_warga_form(): void
    {
        Schedule::create(['day_of_week' => Carbon::tomorrow()->addDay()->dayOfWeek, 'open_time' => '08:00:00', 'close_time' => '15:00:00', 'is_active' => true]);
        $warga = User::factory()->create(['role' => User::ROLE_WARGA]);

        $this->actingAs($warga)->get(route('reservations.create'))->assertSee('Pendaftaran Nikah');

        $this->service->update(['is_active' => false]);

        $this->actingAs($warga)->get(route('reservations.create'))->assertDontSee('Pendaftaran Nikah');
    }
}
