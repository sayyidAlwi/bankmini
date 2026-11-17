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

    public function test_cs_can_register_a_nasabah_with_generated_codes(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);

        $this->actingAs($cs)
            ->post(route('nasabah.store'), [
                'nama' => 'Budi Santoso',
                'kelas' => 'X-1',
                'alamat' => 'Jl. Merdeka No. 1',
                'no_hp' => '081234567890',
            ])
            ->assertSessionHasNoErrors();

        $nasabah = Nasabah::query()->sole();

        // Codes follow the record id, which is portable across database engines.
        $this->assertSame('NSB'.str_pad((string) $nasabah->id, 5, '0', STR_PAD_LEFT), $nasabah->kode_nasabah);
        $this->assertSame('Budi Santoso', $nasabah->nama);

        $rekening = $nasabah->rekening()->sole();

        $this->assertSame('REK'.str_pad((string) $rekening->id, 5, '0', STR_PAD_LEFT), $rekening->no_rekening);
        $this->assertSame(Rekening::STATUS_AKTIF, $rekening->status);
        $this->assertSame('0.00', $rekening->saldo);
    }

    public function test_codes_are_sequential_for_each_new_nasabah(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);

        foreach (['Nasabah A', 'Nasabah B'] as $nama) {
            $this->actingAs($cs)->post(route('nasabah.store'), [
                'nama' => $nama,
                'kelas' => 'XI-2',
                'alamat' => 'Jl. Sudirman',
                'no_hp' => '081200000000',
            ])->assertSessionHasNoErrors();
        }

        $nasabah = Nasabah::query()->orderBy('id')->get();
        $rekening = Rekening::query()->orderBy('id')->get();

        $this->assertCount(2, $nasabah);
        $this->assertCount(2, $rekening);

        foreach ($nasabah as $item) {
            $this->assertSame('NSB'.str_pad((string) $item->id, 5, '0', STR_PAD_LEFT), $item->kode_nasabah);
        }

        foreach ($rekening as $item) {
            $this->assertSame('REK'.str_pad((string) $item->id, 5, '0', STR_PAD_LEFT), $item->no_rekening);
        }
    }

    public function test_nasabah_registration_requires_valid_data(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);

        $this->actingAs($cs)
            ->post(route('nasabah.store'), [
                'nama' => '',
                'kelas' => '',
                'alamat' => '',
                'no_hp' => '',
            ])
            ->assertSessionHasErrors(['nama', 'kelas', 'alamat', 'no_hp']);

        $this->assertSame(0, Nasabah::query()->count());
        $this->assertSame(0, Rekening::query()->count());
    }

    public function test_cs_can_update_nasabah_data(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);
        $nasabah = Nasabah::factory()->create(['kelas' => 'X-1']);

        $this->actingAs($cs)
            ->put(route('nasabah.update', $nasabah), [
                'nama' => 'Nama Baru',
                'kelas' => 'XII-3',
                'alamat' => 'Jl. Baru No. 9',
                'no_hp' => '089999999999',
            ])
            ->assertRedirect(route('nasabah.show', $nasabah))
            ->assertSessionHasNoErrors();

        $nasabah->refresh();

        $this->assertSame('Nama Baru', $nasabah->nama);
        $this->assertSame('XII-3', $nasabah->kelas);
        $this->assertSame('Jl. Baru No. 9', $nasabah->alamat);
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
                'nama' => 'Tidak Boleh',
                'kelas' => 'X-1',
                'alamat' => 'Jl. Test',
                'no_hp' => '0812',
            ])
            ->assertForbidden();
        $this->actingAs($teller)->get(route('nasabah.edit', $nasabah))->assertForbidden();
        $this->actingAs($teller)
            ->put(route('nasabah.update', $nasabah), [
                'nama' => 'Tidak Boleh',
                'kelas' => 'X-1',
                'alamat' => 'Jl. Test',
                'no_hp' => '0812',
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

    public function test_nasabah_list_can_be_searched_by_name_or_account_number(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);
        $target = Nasabah::factory()->create(['nama' => 'Siti Aminah']);
        $target->rekening()->create([
            'no_rekening' => 'REK00999',
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
            ->get(route('nasabah.index', ['q' => 'REK00999']))
            ->assertOk()
            ->assertSee('REK00999');
    }
}
