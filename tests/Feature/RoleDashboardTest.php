<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AkunSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_root_url_shows_the_login_page_instead_of_the_laravel_template(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk');
    }

    public function test_authenticated_user_is_redirected_from_root_to_their_dashboard(): void
    {
        $teller = User::factory()->create(['role' => User::ROLE_TELLER]);

        $this->actingAs($teller)
            ->get('/')
            ->assertRedirect(route('teller.dashboard'));
    }

    public function test_each_role_is_redirected_from_home_to_its_dashboard(): void
    {
        $dashboards = [
            User::ROLE_ADMIN => ['admin.dashboard', 'dashboard.admin'],
            User::ROLE_SUPERVISOR => ['supervisor.dashboard', 'dashboard.supervisor'],
            User::ROLE_TELLER => ['teller.dashboard', 'dashboard.teller'],
            User::ROLE_CS => ['cs.dashboard', 'dashboard.cs'],
            User::ROLE_AKUNTANSI => ['akuntansi.dashboard', 'dashboard.akuntansi'],
        ];

        foreach ($dashboards as $role => [$route, $view]) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('home'))
                ->assertRedirect(route($route));

            $this->actingAs($user)
                ->get(route($route))
                ->assertOk()
                ->assertViewIs($view);
        }
    }

    public function test_role_cannot_open_another_roles_dashboard(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teller = User::factory()->create(['role' => User::ROLE_TELLER]);
        $cs = User::factory()->create(['role' => User::ROLE_CS]);

        $this->actingAs($admin)->get(route('teller.dashboard'))->assertForbidden();
        $this->actingAs($teller)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($cs)->get(route('akuntansi.dashboard'))->assertForbidden();
    }

    public function test_admin_can_access_management_and_reports(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $routes = [
            'admin.users.index',
            'nasabah.index',
            'nasabah.create',
            'transaksi.index',
            'transaksi.create',
            'laporan.kas',
            'laporan.simpanan',
            'laporan.jurnal',
            'laporan.buku-besar',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_cs_can_only_manage_nasabah(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);

        $this->actingAs($cs)->get(route('nasabah.index'))->assertOk();
        $this->actingAs($cs)->get(route('nasabah.create'))->assertOk();

        $this->actingAs($cs)->get(route('transaksi.index'))->assertForbidden();
        $this->actingAs($cs)->get(route('laporan.kas'))->assertForbidden();
        $this->actingAs($cs)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_teller_can_record_transactions_but_not_manage_users_or_reports(): void
    {
        $teller = User::factory()->create(['role' => User::ROLE_TELLER]);

        $this->actingAs($teller)->get(route('transaksi.index'))->assertOk();
        $this->actingAs($teller)->get(route('transaksi.create'))->assertOk();
        $this->actingAs($teller)->get(route('nasabah.index'))->assertOk();

        $this->actingAs($teller)->get(route('nasabah.create'))->assertForbidden();
        $this->actingAs($teller)->get(route('laporan.kas'))->assertForbidden();
        $this->actingAs($teller)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_supervisor_can_review_transactions_and_reports(): void
    {
        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

        $this->actingAs($supervisor)->get(route('transaksi.index'))->assertOk();
        $this->actingAs($supervisor)->get(route('laporan.jurnal'))->assertOk();

        $this->actingAs($supervisor)->get(route('transaksi.create'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('nasabah.create'))->assertForbidden();
    }

    public function test_akuntansi_can_access_reports_but_not_operational_actions(): void
    {
        $akuntansi = User::factory()->create(['role' => User::ROLE_AKUNTANSI]);

        $this->actingAs($akuntansi)->get(route('laporan.kas'))->assertOk();
        $this->actingAs($akuntansi)->get(route('laporan.buku-besar'))->assertOk();

        $this->actingAs($akuntansi)->get(route('transaksi.index'))->assertForbidden();
        $this->actingAs($akuntansi)->get(route('transaksi.create'))->assertForbidden();
        $this->actingAs($akuntansi)->get(route('nasabah.index'))->assertForbidden();
    }

    public function test_login_redirects_to_the_dashboard_for_the_users_role(): void
    {
        $teller = User::factory()->create([
            'username' => 'teller-test',
            'password' => 'rahasia123',
            'role' => User::ROLE_TELLER,
        ]);

        $this->post(route('login'), [
            'username' => 'teller-test',
            'password' => 'rahasia123',
        ])->assertRedirect(route('teller.dashboard'));

        $this->assertAuthenticatedAs($teller);
    }
}
