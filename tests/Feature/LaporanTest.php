<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\Rekening;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\LaporanService;
use App\Services\TransaksiService;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private User $teller;

    private User $admin;

    private Rekening $rekening;

    private LaporanService $laporan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AkunSeeder::class);

        $this->teller = User::factory()->create(['role' => User::ROLE_TELLER]);
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->rekening = Rekening::factory()->create(['saldo' => '0.00']);
        $this->laporan = app(LaporanService::class);

        $service = app(TransaksiService::class);
        $service->catat($this->rekening, $this->teller, Transaksi::JENIS_SETORAN, '100000');
        $service->catat($this->rekening, $this->teller, Transaksi::JENIS_PENARIKAN, '25000');
    }

    public function test_cash_and_savings_balances_follow_deposits_and_withdrawals(): void
    {
        $this->assertSame('75000.00', $this->laporan->saldoAkunByKode(Akun::KAS));
        $this->assertSame('75000.00', $this->laporan->saldoAkunByKode(Akun::SIMPANAN));

        $this->assertSame('75000.00', $this->rekening->refresh()->saldo);
        $this->assertSame(2, Transaksi::query()->count());
        $this->assertSame(2, JurnalUmum::query()->count());
    }

    public function test_ledger_rows_carry_a_running_balance(): void
    {
        $kas = Akun::query()->where('kode_akun', Akun::KAS)->firstOrFail();
        $bukuBesar = $this->laporan->bukuBesar($kas);

        $this->assertSame('0.00', $bukuBesar['opening']);
        $this->assertCount(2, $bukuBesar['rows']);
        $this->assertSame('100000.00', $bukuBesar['rows'][0]['saldo']);
        $this->assertSame('75000.00', $bukuBesar['rows'][1]['saldo']);
        $this->assertSame('100000.00', $bukuBesar['total_debit']);
        $this->assertSame('25000.00', $bukuBesar['total_kredit']);
        $this->assertSame('75000.00', $bukuBesar['ending']);
    }

    public function test_each_journal_has_balanced_debit_and_credit(): void
    {
        foreach (JurnalUmum::query()->with('detail')->get() as $jurnal) {
            $this->assertCount(2, $jurnal->detail);
            $this->assertSame(
                0,
                bccomp((string) $jurnal->detail->sum('debit'), (string) $jurnal->detail->sum('kredit'), 2),
            );
        }
    }

    public function test_admin_and_accounting_roles_can_open_every_report(): void
    {
        $akuntansi = User::factory()->create(['role' => User::ROLE_AKUNTANSI]);
        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

        $routes = [
            'laporan.kas',
            'laporan.simpanan',
            'laporan.jurnal',
            'laporan.buku-besar',
        ];

        foreach ([$this->admin, $akuntansi, $supervisor] as $user) {
            foreach ($routes as $route) {
                $this->actingAs($user)->get(route($route))->assertOk();
            }
        }
    }

    public function test_teller_and_cs_cannot_open_reports(): void
    {
        $cs = User::factory()->create(['role' => User::ROLE_CS]);

        foreach (['laporan.kas', 'laporan.simpanan', 'laporan.jurnal', 'laporan.buku-besar'] as $route) {
            $this->actingAs($this->teller)->get(route($route))->assertForbidden();
            $this->actingAs($cs)->get(route($route))->assertForbidden();
        }
    }

    public function test_cash_report_shows_the_current_cash_balance(): void
    {
        $this->actingAs($this->admin)
            ->get(route('laporan.kas'))
            ->assertOk()
            ->assertSee('100.000')
            ->assertSee('75.000');
    }

    public function test_date_filter_excludes_transactions_outside_the_period(): void
    {
        $kas = Akun::query()->where('kode_akun', Akun::KAS)->firstOrFail();

        $besok = now()->addDay()->toDateString();
        $bukuBesar = $this->laporan->bukuBesar($kas, $besok, $besok);

        $this->assertSame('75000.00', $bukuBesar['opening']);
        $this->assertCount(0, $bukuBesar['rows']);
        $this->assertSame('75000.00', $bukuBesar['ending']);
    }

    public function test_report_rejects_an_invalid_date_range(): void
    {
        $this->actingAs($this->admin)
            ->get(route('laporan.kas', [
                'dari' => now()->toDateString(),
                'sampai' => now()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('sampai');
    }
}
