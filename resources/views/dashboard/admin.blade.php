@extends('layouts.dashboard')

@section('title', 'Dashboard Admin')
@section('eyebrow', 'Ringkasan')
@section('page-title', 'Dashboard Admin')

@section('content')
    <section class="hero">
        <div>
            <p class="hero__eyebrow">Selamat datang kembali</p>
            <h2 class="hero__title">{{ auth()->user()->name }}</h2>
            <p class="hero__text">
                Pantau seluruh operasional BankMini: nasabah, transaksi, kas, dan jurnal
                keuangan dalam satu tempat.
            </p>
        </div>
        <span class="hero__badge">Admin</span>
    </section>

    <div class="stat-grid">
        <x-stat-card
            label="Total Nasabah"
            :value="number_format($totalNasabah, 0, ',', '.')"
            :hint="$rekeningAktif.' rekening aktif'"
            tone="green"
        />
        <x-stat-card
            label="Total Simpanan"
            :value="'Rp '.number_format((float) $totalSimpanan, 0, ',', '.')"
            :hint="$totalRekening.' rekening terdaftar'"
            tone="blue"
        />
        <x-stat-card
            label="Saldo Kas"
            :value="'Rp '.number_format((float) $kas, 0, ',', '.')"
            hint="Kas fisik saat ini"
            tone="amber"
        />
        <x-stat-card
            label="Transaksi Hari Ini"
            :value="number_format($transaksiHariIni, 0, ',', '.')"
            :hint="'Masuk Rp '.number_format((float) $setoranHariIni, 0, ',', '.').' · Keluar Rp '.number_format((float) $penarikanHariIni, 0, ',', '.')"
            tone="rose"
        />
    </div>

    <div class="panel-grid">
        <section class="panel">
            <div class="panel__header">
                <div>
                    <h2 class="panel__title">Transaksi Terbaru</h2>
                    <p class="panel__subtitle">Lima transaksi terakhir yang tercatat.</p>
                </div>
                <a class="panel__link" href="{{ route('transaksi.index') }}">Lihat semua</a>
            </div>

            @include('dashboard.partials.transaksi-table', ['transaksi' => $recentTransaksi])
        </section>

        <section class="panel">
            <div class="panel__header">
                <div>
                    <h2 class="panel__title">Akses Cepat</h2>
                    <p class="panel__subtitle">Pintasan menu operasional dan laporan.</p>
                </div>
            </div>

            <div class="panel__body">
                <div class="quick-list">
                    <a class="quick-card" href="{{ route('nasabah.index') }}">
                        <span>
                            <span class="quick-card__title">Data Nasabah</span>
                            <span class="quick-card__desc">Kelola nasabah dan rekening simpanan.</span>
                        </span>
                        <span class="quick-card__arrow" aria-hidden="true">&rarr;</span>
                    </a>
                    <a class="quick-card" href="{{ route('transaksi.create') }}">
                        <span>
                            <span class="quick-card__title">Transaksi Baru</span>
                            <span class="quick-card__desc">Catat setoran atau penarikan.</span>
                        </span>
                        <span class="quick-card__arrow" aria-hidden="true">&rarr;</span>
                    </a>
                    <a class="quick-card" href="{{ route('laporan.kas') }}">
                        <span>
                            <span class="quick-card__title">Laporan Kas</span>
                            <span class="quick-card__desc">Pantau arus kas masuk dan keluar.</span>
                        </span>
                        <span class="quick-card__arrow" aria-hidden="true">&rarr;</span>
                    </a>
                    <a class="quick-card" href="{{ route('admin.users.index') }}">
                        <span>
                            <span class="quick-card__title">Pengguna</span>
                            <span class="quick-card__desc">Atur akun staf dan hak aksesnya.</span>
                        </span>
                        <span class="quick-card__arrow" aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>
        </section>
    </div>

    <section class="panel section-gap">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Jurnal Terbaru</h2>
                <p class="panel__subtitle">Pencatatan debit dan kredit dari transaksi terakhir.</p>
            </div>
            <a class="panel__link" href="{{ route('laporan.jurnal') }}">Lihat jurnal</a>
        </div>

        @include('dashboard.partials.jurnal-table', ['jurnal' => $recentJurnal])
    </section>
@endsection
