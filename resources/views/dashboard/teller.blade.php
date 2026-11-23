@extends('layouts.dashboard')

@section('title', 'Dashboard Teller')
@section('eyebrow', 'Operasional')
@section('page-title', 'Dashboard Teller')

@section('content')
    <section class="hero">
        <div>
            <p class="hero__eyebrow">Selamat bertugas</p>
            <h2 class="hero__title">{{ auth()->user()->name }}</h2>
            <p class="hero__text">
                Catat setoran dan penarikan nasabah. Setiap transaksi otomatis membentuk
                jurnal debit-kredit yang seimbang.
            </p>
        </div>
        <span class="hero__badge">Teller</span>
    </section>

    @include('shift.partials.status', [
        'shiftMingguIni' => $shiftMingguIni,
        'shiftAktif' => $shiftAktif,
    ])

    <div class="stat-grid">
        <x-stat-card
            label="Transaksi Hari Ini"
            :value="number_format($transaksiHariIni, 0, ',', '.')"
            :hint="$totalNasabah.' nasabah terdaftar'"
            tone="green"
        />
        <x-stat-card
            label="Setoran Hari Ini"
            :value="'Rp '.number_format((float) $setoranHariIni, 0, ',', '.')"
            hint="Dana masuk"
            tone="blue"
        />
        <x-stat-card
            label="Penarikan Hari Ini"
            :value="'Rp '.number_format((float) $penarikanHariIni, 0, ',', '.')"
            hint="Dana keluar"
            tone="amber"
        />
        <x-stat-card
            label="Saldo Kas"
            :value="'Rp '.number_format((float) $kas, 0, ',', '.')"
            hint="Kas fisik saat ini"
            tone="rose"
        />
    </div>

    <div class="panel-grid">
        <section class="panel">
            <div class="panel__header">
                <div>
                    <h2 class="panel__title">Transaksi Terbaru</h2>
                    <p class="panel__subtitle">Lima transaksi terakhir yang Anda proses.</p>
                </div>
                <a class="panel__link" href="{{ route('transaksi.index') }}">Riwayat</a>
            </div>

            @include('dashboard.partials.transaksi-table', ['transaksi' => $recentTransaksi])
        </section>

        <section class="panel">
            <div class="panel__header">
                <div>
                    <h2 class="panel__title">Akses Cepat</h2>
                    <p class="panel__subtitle">Pintasan tugas harian teller.</p>
                </div>
            </div>

            <div class="panel__body">
                <div class="quick-list">
                    <a class="quick-card" href="{{ route('transaksi.create') }}">
                        <span>
                            <span class="quick-card__title">Transaksi Baru</span>
                            <span class="quick-card__desc">Catat setoran atau penarikan nasabah.</span>
                        </span>
                        <span class="quick-card__arrow" aria-hidden="true">&rarr;</span>
                    </a>
                    <a class="quick-card" href="{{ route('nasabah.index') }}">
                        <span>
                            <span class="quick-card__title">Data Nasabah</span>
                            <span class="quick-card__desc">Lihat saldo dan rekening nasabah.</span>
                        </span>
                        <span class="quick-card__arrow" aria-hidden="true">&rarr;</span>
                    </a>
                    <a class="quick-card" href="{{ route('shift.saya') }}">
                        <span>
                            <span class="quick-card__title">Shift Saya</span>
                            <span class="quick-card__desc">Lihat riwayat dan status shift mingguan.</span>
                        </span>
                        <span class="quick-card__arrow" aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>
        </section>
    </div>
@endsection
