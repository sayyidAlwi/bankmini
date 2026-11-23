@extends('layouts.dashboard')

@section('title', 'Dashboard CS')
@section('eyebrow', 'Operasional')
@section('page-title', 'Dashboard Customer Service')

@section('content')
    <section class="hero">
        <div>
            <p class="hero__eyebrow">Selamat bertugas</p>
            <h2 class="hero__title">{{ auth()->user()->name }}</h2>
            <p class="hero__text">
                Kelola pendaftaran nasabah. Nomor nasabah dan nomor rekening dibuat
                otomatis oleh sistem.
            </p>
        </div>
        <span class="hero__badge">Customer Service</span>
    </section>

    <div class="stat-grid">
        <x-stat-card
            label="Total Nasabah"
            :value="number_format($totalNasabah, 0, ',', '.')"
            hint="Nasabah terdaftar"
            tone="green"
        />
        <x-stat-card
            label="Total Rekening"
            :value="number_format($totalRekening, 0, ',', '.')"
            hint="Seluruh rekening"
            tone="blue"
        />
        <x-stat-card
            label="Rekening Aktif"
            :value="number_format($rekeningAktif, 0, ',', '.')"
            hint="Siap menerima transaksi"
            tone="amber"
        />
    </div>

    <div class="panel-grid">
        <section class="panel">
            <div class="panel__header">
                <div>
                    <h2 class="panel__title">Nasabah Terbaru</h2>
                    <p class="panel__subtitle">Lima nasabah yang paling baru didaftarkan.</p>
                </div>
                <a class="panel__link" href="{{ route('nasabah.index') }}">Lihat semua</a>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Account Number</th>
                            <th>Customer</th>
                            <th>Generation</th>
                            <th>Study Program</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentNasabah as $item)
                            <tr>
                                <td>
                                    @foreach ($item->rekening as $rekening)
                                        {{ $rekening->no_rekening }}@if (! $loop->last), @endif
                                    @endforeach
                                </td>
                                <td>{{ $item->nama }}</td>
                                <td>{{ $item->angkatan }}</td>
                                <td>{{ $item->program_studi }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-row">Belum ada nasabah.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="panel__header">
                <div>
                    <h2 class="panel__title">Akses Cepat</h2>
                    <p class="panel__subtitle">Pintasan pendaftaran dan pencarian nasabah.</p>
                </div>
            </div>

            <div class="panel__body">
                <div class="quick-list">
                    <a class="quick-card" href="{{ route('nasabah.create') }}">
                        <span>
                            <span class="quick-card__title">Tambah Nasabah</span>
                            <span class="quick-card__desc">Daftarkan nasabah dan rekening baru.</span>
                        </span>
                        <span class="quick-card__arrow" aria-hidden="true">&rarr;</span>
                    </a>
                    <a class="quick-card" href="{{ route('nasabah.index') }}">
                        <span>
                            <span class="quick-card__title">Cari Nasabah</span>
                            <span class="quick-card__desc">Telusuri berdasarkan nama atau nomor rekening.</span>
                        </span>
                        <span class="quick-card__arrow" aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>
        </section>
    </div>
@endsection
