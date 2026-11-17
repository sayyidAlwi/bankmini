<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'password', 'role'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_SUPERVISOR = 'supervisor';

    public const ROLE_TELLER = 'teller';

    public const ROLE_CS = 'cs';

    public const ROLE_AKUNTANSI = 'akuntansi';

    /**
     * Get every staff role available in the application.
     *
     * @return array<int, string>
     */
    public static function roles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_SUPERVISOR,
            self::ROLE_TELLER,
            self::ROLE_CS,
            self::ROLE_AKUNTANSI,
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Get the human readable labels for every staff role.
     *
     * @return array<string, string>
     */
    public static function roleLabels(): array
    {
        return [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_SUPERVISOR => 'Supervisor',
            self::ROLE_TELLER => 'Teller',
            self::ROLE_CS => 'Customer Service',
            self::ROLE_AKUNTANSI => 'Akuntansi',
        ];
    }

    /**
     * Determine whether the user has one of the given roles.
     */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Get the dashboard route name for the user's role.
     */
    public function dashboardRouteName(): ?string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_SUPERVISOR => 'supervisor.dashboard',
            self::ROLE_TELLER => 'teller.dashboard',
            self::ROLE_CS => 'cs.dashboard',
            self::ROLE_AKUNTANSI => 'akuntansi.dashboard',
            default => null,
        };
    }

    /**
     * Get the human readable label for the user's role.
     */
    public function roleLabel(): string
    {
        return self::roleLabels()[$this->role] ?? 'Pengguna';
    }
}
