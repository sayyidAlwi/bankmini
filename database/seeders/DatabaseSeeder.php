<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'username' => 'admin',
                'name' => 'Admin',
                'password' => 'admin123',
                'role' => User::ROLE_ADMIN,
            ],
            [
                'username' => 'supervisor',
                'name' => 'Supervisor',
                'password' => 'supervisor123',
                'role' => User::ROLE_SUPERVISOR,
            ],
            [
                'username' => 'teller',
                'name' => 'Teller',
                'password' => 'teller123',
                'role' => User::ROLE_TELLER,
            ],
            [
                'username' => 'cs',
                'name' => 'Customer Service',
                'password' => 'cs123',
                'role' => User::ROLE_CS,
            ],
            [
                'username' => 'akuntansi',
                'name' => 'Akuntansi',
                'password' => 'akuntansi123',
                'role' => User::ROLE_AKUNTANSI,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['username' => $user['username']],
                $user,
            );
        }

        $this->call(AkunSeeder::class);
    }
}
