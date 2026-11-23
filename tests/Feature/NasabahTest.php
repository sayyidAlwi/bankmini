<?php

namespace Tests\Feature;

use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\User;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NasabahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AkunSeeder::class);
    }

    public function test_cs_can_register_a_nasabah_with_nisn_as_account_number(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);

        $this->actingAs($cs)
            ->post(route('nasabah.store'), [
                'no_rekening' => '10435',
                'nama' => 'Azkia Renatha',
                'angkatan' => '41',
                'program_studi' => 'Teknik Informatika',
            ])
            ->assertSessionHasNoErrors();

        $nasabah = Nasabah::query()->sole();

        $this->assertSame('Azkia Renatha', $nasabah->nama);
        $this->assertSame('41', $nasabah->angkatan);
        $this->assertSame('Teknik Informatika', $nasabah->program_studi);

        $rekening = $nasabah->rekening()->sole();

        $this->assertSame('10435', $rekening->no_rekening);
        $this->assertSame(Rekening::STATUS_AKTIF, $rekening->status);
        $this->assertSame('0.00', $rekening->saldo);
    }

    public function test_account_number_must_be_unique(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);

        $this->actingAs($cs)->post(route('nasabah.store'), [
            'no_rekening' => '10435',
            'nama' => 'Azkia Renatha',
            'angkatan' => '41',
            'program_studi' => 'Teknik Informatika',
        ])->assertSessionHasNoErrors();

        $this->actingAs($cs)
            ->post(route('nasabah.store'), [
                'no_rekening' => '10435',
                'nama' => 'Nasabah Lain',
                'angkatan' => '42',
                'program_studi' => 'Akuntansi',
            ])
            ->assertSessionHasErrors('no_rekening');

        $this->assertSame(1, Nasabah::query()->count());
        $this->assertSame(1, Rekening::query()->count());
    }

    public function test_nasabah_registration_requires_valid_data(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);

        $this->actingAs($cs)
            ->post(route('nasabah.store'), [
                'no_rekening' => '',
                'nama' => '',
                'angkatan' => '',
                'program_studi' => '',
            ])
            ->assertSessionHasErrors(['no_rekening', 'nama', 'angkatan', 'program_studi']);

        $this->assertSame(0, Nasabah::query()->count());
        $this->assertSame(0, Rekening::query()->count());
    }

    public function test_cs_can_update_nasabah_data(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);
        $nasabah = Nasabah::factory()->create([
            'angkatan' => '41',
            'program_studi' => 'Teknik Informatika',
        ]);
        $nasabah->rekening()->create([
            'no_rekening' => '10435',
            'saldo' => '0.00',
            'status' => Rekening::STATUS_AKTIF,
        ]);

        $this->actingAs($cs)
            ->put(route('nasabah.update', $nasabah), [
                'no_rekening' => '10436',
                'nama' => 'Nama Baru',
                'angkatan' => '42',
                'program_studi' => 'Akuntansi',
            ])
            ->assertRedirect(route('nasabah.show', $nasabah))
            ->assertSessionHasNoErrors();

        $nasabah->refresh();

        $this->assertSame('Nama Baru', $nasabah->nama);
        $this->assertSame('42', $nasabah->angkatan);
        $this->assertSame('Akuntansi', $nasabah->program_studi);
        $this->assertSame('10436', $nasabah->rekening()->sole()->no_rekening);
    }

    public function test_teller_can_view_nasabah_but_cannot_modify_them(): void
    {
        $teller = User::factory()->create(['role' => User::ROLE_TELLER]);
        $nasabah = Nasabah::factory()->create();

        $this->actingAs($teller)->get(route('nasabah.index'))->assertOk();
        $this->actingAs($teller)->get(route('nasabah.show', $nasabah))->assertOk();

        $this->actingAs($teller)->get(route('nasabah.create'))->assertForbidden();
        $this->actingAs($teller)
            ->post(route('nasabah.store'), [
                'no_rekening' => '99999',
                'nama' => 'Tidak Boleh',
                'angkatan' => '41',
                'program_studi' => 'Akuntansi',
            ])
            ->assertForbidden();
        $this->actingAs($teller)->get(route('nasabah.edit', $nasabah))->assertForbidden();
        $this->actingAs($teller)
            ->put(route('nasabah.update', $nasabah), [
                'no_rekening' => '99999',
                'nama' => 'Tidak Boleh',
                'angkatan' => '41',
                'program_studi' => 'Akuntansi',
            ])
            ->assertForbidden();
    }

    public function test_reports_only_roles_cannot_access_nasabah_pages(): void
    {
        $akuntansi = User::factory()->create(['role' => User::ROLE_AKUNTANSI]);
        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

        $this->actingAs($akuntansi)->get(route('nasabah.index'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('nasabah.index'))->assertForbidden();
    }

    public function test_nasabah_list_can_be_searched_by_name_account_number_or_study_program(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);
        $target = Nasabah::factory()->create([
            'nama' => 'Siti Aminah',
            'angkatan' => '41',
            'program_studi' => 'Akuntansi',
        ]);
        $target->rekening()->create([
            'no_rekening' => '10435',
            'saldo' => '0.00',
            'status' => Rekening::STATUS_AKTIF,
        ]);
        Nasabah::factory()->create(['nama' => 'Orang Lain']);

        $this->actingAs($cs)
            ->get(route('nasabah.index', ['q' => 'Siti']))
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertDontSee('Orang Lain');

        $this->actingAs($cs)
            ->get(route('nasabah.index', ['q' => '10435']))
            ->assertOk()
            ->assertSee('10435');

        $this->actingAs($cs)
            ->get(route('nasabah.index', ['q' => 'Akuntansi']))
            ->assertOk()
            ->assertSee('Siti Aminah');
    }
}
