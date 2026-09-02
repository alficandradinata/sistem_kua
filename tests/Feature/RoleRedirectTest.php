<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * [SISTEM KUA] Redirect pasca-login & pembatasan akses per peran.
 */
class RoleRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function attemptLogin(string $role): TestResponse
    {
        $user = User::factory()->create(['role' => $role]);

        return $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $this->attemptLogin(User::ROLE_ADMIN)->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_petugas_login_redirects_to_petugas_dashboard(): void
    {
        $this->attemptLogin(User::ROLE_PETUGAS)->assertRedirect(route('petugas.dashboard'));
    }

    public function test_warga_login_redirects_to_warga_dashboard(): void
    {
        $this->attemptLogin(User::ROLE_WARGA)->assertRedirect(route('dashboard'));
    }

    public function test_warga_cannot_access_admin_area(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_WARGA]);
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_petugas_can_access_petugas_area_but_not_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PETUGAS]);
        $this->actingAs($user)->get('/petugas')->assertOk();
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_guest_landing_page_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('Reservasi Antrean KUA');
    }
}
