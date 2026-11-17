<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AkunSeeder::class);
    }

    public function test_admin_can_create_a_staff_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Teller Baru',
                'username' => 'teller-baru',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'role' => User::ROLE_TELLER,
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors();

        $user = User::query()->where('username', 'teller-baru')->sole();

        $this->assertSame('Teller Baru', $user->name);
        $this->assertSame(User::ROLE_TELLER, $user->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_staff_account_creation_validates_input(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => '',
                'username' => '',
                'password' => 'pendek',
                'password_confirmation' => 'beda',
                'role' => 'direktur',
            ])
            ->assertSessionHasErrors(['name', 'username', 'password', 'role']);
    }

    public function test_username_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['username' => 'teller']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Duplikat',
                'username' => 'teller',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'role' => User::ROLE_TELLER,
            ])
            ->assertSessionHasErrors('username');
    }

    public function test_admin_can_update_a_staff_account_without_changing_the_password(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create([
            'username' => 'cs-lama',
            'password' => 'passwordlama',
            'role' => User::ROLE_CS,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'CS Diperbarui',
                'username' => 'cs-lama',
                'password' => '',
                'password_confirmation' => '',
                'role' => User::ROLE_AKUNTANSI,
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('CS Diperbarui', $user->name);
        $this->assertSame(User::ROLE_AKUNTANSI, $user->role);
        $this->assertTrue(Hash::check('passwordlama', $user->password));
    }

    public function test_admin_cannot_demote_their_own_account(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'username' => $admin->username,
                'password' => '',
                'password_confirmation' => '',
                'role' => User::ROLE_TELLER,
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame(User::ROLE_ADMIN, $admin->refresh()->role);
    }

    public function test_non_admin_roles_cannot_manage_users(): void
    {
        $target = User::factory()->create();

        foreach ([
            User::ROLE_TELLER,
            User::ROLE_CS,
            User::ROLE_SUPERVISOR,
            User::ROLE_AKUNTANSI,
        ] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.users.create'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.users.edit', $target))->assertForbidden();
            $this->actingAs($user)
                ->post(route('admin.users.store'), [
                    'name' => 'Tidak Boleh',
                    'username' => 'tidak-boleh-'.$role,
                    'password' => 'rahasia123',
                    'password_confirmation' => 'rahasia123',
                    'role' => User::ROLE_ADMIN,
                ])
                ->assertForbidden();
        }
    }

    public function test_user_index_lists_staff_accounts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create([
            'name' => 'Siti Teller',
            'username' => 'siti-teller',
            'role' => User::ROLE_TELLER,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Siti Teller')
            ->assertSee('siti-teller');
    }
}
