<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\Rekening;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\LaporanService;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiTest extends TestCase
{
    use RefreshDatabase;

    private User $teller;

    private Rekening $rekening;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AkunSeeder::class);

        $this->teller = User::factory()->create(['role' => User::ROLE_TELLER]);
        $this->rekening = Rekening::factory()->create(['saldo' => '0.00']);
    }

    public function test_teller_can_record_a_deposit_with_a_balanced_journal(): void
    {
        $this->actingAs($this->teller)
            ->post(route('transaksi.store'), [
                'rekening_id' => $this->rekening->id,
                'jenis' => Transaksi::JENIS_SETORAN,
                'nominal' => '50000',
            ])
            ->assertRedirect(route('transaksi.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('50000.00', $this->rekening->refresh()->saldo);
        $this->assertSame(1, Transaksi::query()->count());
        $this->assertSame(1, JurnalUmum::query()->count());

        $transaksi = Transaksi::query()->sole();

        $this->assertSame(Transaksi::JENIS_SETORAN, $transaksi->jenis);
        $this->assertSame('50000.00', $transaksi->nominal);
        $this->assertSame(Transaksi::STATUS_BERHASIL, $transaksi->status);
        $this->assertStringStartsWith('TRX', $transaksi->kode_transaksi);
        $this->assertSame($this->teller->id, $transaksi->user_id);

        $jurnal = $transaksi->jurnal;

        $this->assertNotNull($jurnal);
        $this->assertCount(2, $jurnal->detail);
        $this->assertSame(
            0,
            bccomp((string) $jurnal->detail->sum('debit'), (string) $jurnal->detail->sum('kredit'), 2),
        );

        $laporan = app(LaporanService::class);

        $this->assertSame('50000.00', $laporan->saldoAkunByKode(Akun::KAS));
        $this->assertSame('50000.00', $laporan->saldoAkunByKode(Akun::SIMPANAN));
    }

    public function test_teller_can_record_a_withdrawal_and_balance_is_reduced(): void
    {
        $this->rekening->update(['saldo' => '100000.00']);

        $this->actingAs($this->teller)
            ->post(route('transaksi.store'), [
                'rekening_id' => $this->rekening->id,
                'jenis' => Transaksi::JENIS_PENARIKAN,
                'nominal' => '40000.50',
            ])
            ->assertRedirect(route('transaksi.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('59999.50', $this->rekening->refresh()->saldo);

        $laporan = app(LaporanService::class);

        $this->assertSame('-40000.50', $laporan->saldoAkunByKode(Akun::KAS));
        $this->assertSame('-40000.50', $laporan->saldoAkunByKode(Akun::SIMPANAN));
    }

    public function test_withdrawal_cannot_exceed_the_account_balance(): void
    {
        $this->rekening->update(['saldo' => '10000.00']);

        $this->actingAs($this->teller)
            ->post(route('transaksi.store'), [
                'rekening_id' => $this->rekening->id,
                'jenis' => Transaksi::JENIS_PENARIKAN,
                'nominal' => '20000',
            ])
            ->assertSessionHasErrors('nominal');

        $this->assertSame('10000.00', $this->rekening->refresh()->saldo);
        $this->assertSame(0, Transaksi::query()->count());
        $this->assertSame(0, JurnalUmum::query()->count());
    }

    public function test_inactive_account_cannot_receive_transactions(): void
    {
        $nonaktif = Rekening::factory()->nonaktif()->create();

        $this->actingAs($this->teller)
            ->post(route('transaksi.store'), [
                'rekening_id' => $nonaktif->id,
                'jenis' => Transaksi::JENIS_SETORAN,
                'nominal' => '10000',
            ])
            ->assertSessionHasErrors('rekening_id');

        $this->assertSame('0.00', $nonaktif->refresh()->saldo);
        $this->assertSame(0, Transaksi::query()->count());
    }

    public function test_nominal_must_be_positive_and_have_at_most_two_decimals(): void
    {
        foreach (['0', '-1000', '1000.555'] as $nominal) {
            $this->actingAs($this->teller)
                ->post(route('transaksi.store'), [
                    'rekening_id' => $this->rekening->id,
                    'jenis' => Transaksi::JENIS_SETORAN,
                    'nominal' => $nominal,
                ])
                ->assertSessionHasErrors('nominal');
        }

        $this->assertSame(0, Transaksi::query()->count());
    }

    public function test_only_admin_and_teller_can_record_transactions(): void
    {
        foreach ([User::ROLE_CS, User::ROLE_SUPERVISOR, User::ROLE_AKUNTANSI] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->post(route('transaksi.store'), [
                    'rekening_id' => $this->rekening->id,
                    'jenis' => Transaksi::JENIS_SETORAN,
                    'nominal' => '10000',
                ])
                ->assertForbidden();
        }

        $this->assertSame(0, Transaksi::query()->count());
    }

    public function test_admin_can_record_transactions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('transaksi.store'), [
                'rekening_id' => $this->rekening->id,
                'jenis' => Transaksi::JENIS_SETORAN,
                'nominal' => '25000',
            ])
            ->assertRedirect(route('transaksi.index'));

        $this->assertSame('25000.00', $this->rekening->refresh()->saldo);
    }
}
