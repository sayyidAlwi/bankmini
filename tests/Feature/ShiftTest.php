<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AkunSeeder::class);

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->teller = User::factory()->create(['role' => User::ROLE_TELLER]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_teller_starts_a_weekly_shift_by_typing_their_name(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        $this->actingAs($this->teller)
            ->post(route('shift.mulai'), ['nama' => 'Budi Santoso'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $shift = Shift::query()->sole();

        $this->assertSame($this->teller->id, $shift->user_id);
        $this->assertSame('Budi Santoso', $shift->nama);
        $this->assertSame('2026-11-16', $shift->tanggal_mulai->toDateString());
        $this->assertSame('2026-11-20', $shift->tanggal_selesai->toDateString());
        $this->assertTrue($shift->mulai_at->equalTo(now()));
        $this->assertNull($shift->selesai_at);
        $this->assertTrue($shift->isAktif());
    }

    public function test_shift_started_midweek_always_covers_monday_to_friday(): void
    {
        Carbon::setTestNow('2026-11-18 09:15:00');

        $this->actingAs($this->teller)
            ->post(route('shift.mulai'), ['nama' => 'Budi Santoso'])
            ->assertSessionHasNoErrors();

        $shift = Shift::query()->sole();

        $this->assertSame('2026-11-16', $shift->tanggal_mulai->toDateString());
        $this->assertSame('2026-11-20', $shift->tanggal_selesai->toDateString());
    }

    public function test_only_one_shift_can_be_started_per_week(): void
    {
        $lain = User::factory()->create(['role' => User::ROLE_TELLER]);

        Carbon::setTestNow('2026-11-16 08:00:00');

        $this->actingAs($this->teller)
            ->post(route('shift.mulai'), ['nama' => 'Budi Santoso'])
            ->assertSessionHasNoErrors();

        Carbon::setTestNow('2026-11-18 08:00:00');

        $this->actingAs($lain)
            ->post(route('shift.mulai'), ['nama' => 'Siti Aminah'])
            ->assertSessionHasErrors('nama');

        $this->assertSame(1, Shift::query()->count());
        $this->assertSame('Budi Santoso', Shift::query()->sole()->nama);
    }

    public function test_teller_cannot_start_two_shifts_in_the_same_week(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        $this->actingAs($this->teller)
            ->post(route('shift.mulai'), ['nama' => 'Budi Santoso'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->teller)
            ->post(route('shift.mulai'), ['nama' => 'Budi Santoso'])
            ->assertSessionHasErrors('nama');

        $this->assertSame(1, Shift::query()->count());
    }

    public function test_teller_ends_their_shift_and_can_start_again_the_next_week(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        $this->actingAs($this->teller)
            ->post(route('shift.mulai'), ['nama' => 'Budi Santoso'])
            ->assertSessionHasNoErrors();

        Carbon::setTestNow('2026-11-16 15:30:00');

        $this->actingAs($this->teller)
            ->post(route('shift.selesai'))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $shift = Shift::query()->sole();

        $this->assertTrue($shift->selesai_at->equalTo(now()));
        $this->assertSame(450, $shift->durasiMenit());
        $this->assertSame('7 jam 30 menit', $shift->durasiLabel());
        $this->assertFalse($shift->isAktif());

        Carbon::setTestNow('2026-11-23 08:00:00');

        $this->actingAs($this->teller)
            ->post(route('shift.mulai'), ['nama' => 'Budi Santoso'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Shift::query()->count());
    }

    public function test_shift_can_only_be_ended_once(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        $this->actingAs($this->teller)->post(route('shift.mulai'), ['nama' => 'Budi Santoso']);
        $this->actingAs($this->teller)->post(route('shift.selesai'));

        $this->actingAs($this->teller)
            ->post(route('shift.selesai'))
            ->assertSessionHasErrors('shift');

        $this->assertNotNull(Shift::query()->sole()->selesai_at);
    }

    public function test_teller_cannot_end_a_shift_started_by_someone_else(): void
    {
        $lain = User::factory()->create(['role' => User::ROLE_TELLER]);

        Carbon::setTestNow('2026-11-16 08:00:00');

        $this->actingAs($this->teller)->post(route('shift.mulai'), ['nama' => 'Budi Santoso']);

        $this->actingAs($lain)
            ->post(route('shift.selesai'))
            ->assertSessionHasErrors('shift');

        $this->assertNull(Shift::query()->sole()->selesai_at);
    }

    public function test_teller_must_end_an_open_shift_before_starting_a_new_week(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        $this->actingAs($this->teller)->post(route('shift.mulai'), ['nama' => 'Budi Santoso']);

        Carbon::setTestNow('2026-11-23 08:00:00');

        $this->actingAs($this->teller)
            ->post(route('shift.mulai'), ['nama' => 'Budi Santoso'])
            ->assertSessionHasErrors('nama');

        $this->assertSame(1, Shift::query()->count());

        $this->actingAs($this->teller)->post(route('shift.selesai'));

        $this->actingAs($this->teller)
            ->post(route('shift.mulai'), ['nama' => 'Budi Santoso'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Shift::query()->count());
    }

    public function test_name_is_required_before_starting_a_shift(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        $this->actingAs($this->teller)
            ->post(route('shift.mulai'), ['nama' => ''])
            ->assertSessionHasErrors('nama');

        $this->assertSame(0, Shift::query()->count());
    }

    public function test_shift_page_shows_start_then_finish_controls(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        $this->actingAs($this->teller)
            ->get(route('shift.saya'))
            ->assertOk()
            ->assertSee('Mulai Shift');

        $this->actingAs($this->teller)->post(route('shift.mulai'), ['nama' => 'Budi Santoso']);

        $this->actingAs($this->teller)
            ->get(route('shift.saya'))
            ->assertOk()
            ->assertSee('Selesai Shift')
            ->assertSee('Budi Santoso');

        $this->actingAs($this->teller)->post(route('shift.selesai'));

        $this->actingAs($this->teller)
            ->get(route('shift.saya'))
            ->assertOk()
            ->assertSee('Shift Anda minggu ini sudah selesai');
    }

    public function test_teller_dashboard_shows_the_shift_panel(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        $this->actingAs($this->teller)
            ->get(route('teller.dashboard'))
            ->assertOk()
            ->assertSee('Mulai Shift');

        $this->actingAs($this->teller)->post(route('shift.mulai'), ['nama' => 'Budi Santoso']);

        $this->actingAs($this->teller)
            ->get(route('teller.dashboard'))
            ->assertOk()
            ->assertSee('Selesai Shift')
            ->assertSee('Budi Santoso');
    }

    public function test_admin_and_supervisor_can_view_the_shift_report(): void
    {
        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

        Carbon::setTestNow('2026-11-16 08:00:00');

        $this->actingAs($this->teller)
            ->post(route('shift.mulai'), ['nama' => 'Budi Santoso'])
            ->assertSessionHasNoErrors();

        $shift = Shift::query()->sole();

        foreach ([$this->admin, $supervisor] as $pengguna) {
            $this->actingAs($pengguna)
                ->get(route('shift.index', ['periode' => '2026-11']))
                ->assertOk()
                ->assertSee('Budi Santoso');

            $this->actingAs($pengguna)
                ->get(route('shift.show', $shift))
                ->assertOk()
                ->assertSee('Budi Santoso')
                ->assertSee('Aktif');
        }
    }

    public function test_admin_can_close_and_delete_a_shift(): void
    {
        Carbon::setTestNow('2026-11-16 08:00:00');

        $this->actingAs($this->teller)->post(route('shift.mulai'), ['nama' => 'Budi Santoso']);

        $shift = Shift::query()->sole();

        Carbon::setTestNow('2026-11-18 12:00:00');

        $this->actingAs($this->admin)
            ->post(route('shift.tutup', $shift))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($shift->refresh()->selesai_at);

        $this->actingAs($this->admin)
            ->delete(route('shift.destroy', $shift))
            ->assertRedirect();

        $this->assertSame(0, Shift::query()->count());
    }

    public function test_teller_cannot_open_the_shift_report(): void
    {
        $this->actingAs($this->teller)
            ->get(route('shift.index'))
            ->assertForbidden();
    }

    public function test_supervisor_cannot_close_or_delete_a_shift(): void
    {
        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

        Carbon::setTestNow('2026-11-16 08:00:00');

        $this->actingAs($this->teller)->post(route('shift.mulai'), ['nama' => 'Budi Santoso']);

        $shift = Shift::query()->sole();

        $this->actingAs($supervisor)
            ->post(route('shift.tutup', $shift))
            ->assertForbidden();

        $this->actingAs($supervisor)
            ->delete(route('shift.destroy', $shift))
            ->assertForbidden();

        $this->assertNull($shift->refresh()->selesai_at);
    }

    public function test_non_teller_roles_cannot_open_the_teller_shift_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('shift.saya'))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->post(route('shift.mulai'), ['nama' => 'Admin'])
            ->assertForbidden();
    }
}
