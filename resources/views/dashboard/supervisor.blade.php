@extends('layouts.dashboard')

@section('title', 'Dashboard Supervisor')
@section('eyebrow', 'Pengawasan')
@section('page-title', 'Dashboard Supervisor')

@section('content')
    <section class="hero">
        <div>
            <p class="hero__eyebrow">Pengawasan operasional</p>
            <h2 class="hero__title">{{ auth()->user()->name }}</h2>
            <p class="hero__text">
                Awasi arus kas, simpanan nasabah, dan jurnal keuangan BankMini.
                Seluruh laporan tersedia dalam mode baca.
            </p>
        </div>
        <span class="hero__badge">Supervisor</span>
    </section>

    <div class="stat-grid">
        <x-stat-card
            label="Saldo Kas"
            :value="'Rp '.number_format((float) $kas, 0, ',', '.')"
            hint="Saldo buku akun kas"
            tone="green"
        />
        <x-stat-card
            label="Total Simpanan"
            :value="'Rp '.number_format((float) $totalSimpanan, 0, ',', '.')"
            :hint="$totalRekening.' rekening'"
            tone="blue"
        />
        <x-stat-card
            label="Transaksi Hari Ini"
            :value="number_format($transaksiHariIni, 0, ',', '.')"
            :hint="'Setoran Rp '.number_format((float) $setoranHariIni, 0, ',', '.')"
            tone="amber"
        />
        <x-stat-card
            label="Penarikan Hari Ini"
            :value="'Rp '.number_format((float) $penarikanHariIni, 0, ',', '.')"
            hint="Dana keluar hari ini"
            tone="rose"
        />
    </div>

    <div class="panel-grid">
        <section class="panel">
            <div class="panel__header">
                <div>
                    <h2 class="panel__title">Jurnal Terbaru</h2>
                    <p class="panel__subtitle">Pencatatan terakhir untuk pemeriksaan.</p>
                </div>
                <a class="panel__link" href="{{ route('laporan.jurnal') }}">Lihat jurnal</a>
            </div>

            @include('dashboard.partials.jurnal-table', ['jurnal' => $recentJurnal])
        </section>

        <section class="panel">
            <div class="panel__header">
                <div>
                    <h2 class="panel__title">Laporan</h2>
                    <p class="panel__subtitle">Akses cepat ke laporan pengawasan.</p>
                </div>
            </div>

            <div class="panel__body">
                <div class="quick-list">
                    <a class="quick-card" href="{{ route('laporan.kas') }}">
                        <span>
                            <span class="quick-card__title">Laporan Kas</span>
                            <span class="quick-card__desc">Mutasi dan saldo kas.</span>
                        </span>
                        <span class="quick-card__arrow" aria-hidden="true">&rarr;</span>
                    </a>
                    <a class="quick-card" href="{{ route('laporan.simpanan') }}">
                        <span>
                            <span class="quick-card__title">Laporan Simpanan</span>
                            <span class="quick-card__desc">Saldo seluruh rekening nasabah.</span>
                        </span>
                        <span class="quick-card__arrow" aria-hidden="true">&rarr;</span>
                    </a>
                    <a class="quick-card" href="{{ route('laporan.jurnal') }}">
                        <span>
                            <span class="quick-card__title">Jurnal Umum</span>
                            <span class="quick-card__desc">Seluruh jurnal debit-kredit.</span>
                        </span>
                        <span class="quick-card__arrow" aria-hidden="true">&rarr;</span>
                    </a>
                    <a class="quick-card" href="{{ route('laporan.buku-besar') }}">
                        <span>
                            <span class="quick-card__title">Buku Besar</span>
                            <span class="quick-card__desc">Mutasi per akun dengan saldo berjalan.</span>
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
                <h2 class="panel__title">Transaksi Terbaru</h2>
                <p class="panel__subtitle">Aktivitas transaksi terakhir seluruh teller.</p>
            </div>
            <a class="panel__link" href="{{ route('transaksi.index') }}">Lihat semua</a>
        </div>

        @include('dashboard.partials.transaksi-table', ['transaksi' => $recentTransaksi])
    </section>
@endsection
