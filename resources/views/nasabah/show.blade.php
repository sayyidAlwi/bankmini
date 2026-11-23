@extends('layouts.dashboard')

@section('title', 'Detail Nasabah')
@section('eyebrow', 'Operasional')
@section('page-title', 'Detail Nasabah')

@section('content')
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">{{ $nasabah->nama }}</h2>
                <p class="panel__subtitle">{{ $nasabah->angkatan }} &middot; {{ $nasabah->program_studi }}</p>
            </div>

            <div class="toolbar">
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TELLER))
                    @php($rekeningAktif = $nasabah->rekening->firstWhere('status', \App\Models\Rekening::STATUS_AKTIF))
                    @if ($rekeningAktif)
                        <a
                            class="btn btn--small"
                            href="{{ route('transaksi.create', ['rekening' => $rekeningAktif->id]) }}"
                        >
                            Transaksi Baru
                        </a>
                    @endif
                @endif

                @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_CS))
                    <a class="btn btn--secondary btn--small" href="{{ route('nasabah.edit', $nasabah) }}">Ubah Data</a>
                @endif
            </div>
        </div>

        <div class="panel__body">
            <dl class="detail-list">
                <div>
                    <dt>Account Number</dt>
                    <dd>
                        @forelse ($nasabah->rekening as $rekening)
                            {{ $rekening->no_rekening }}@if (! $loop->last), @endif
                        @empty
                            &mdash;
                        @endforelse
                    </dd>
                </div>
                <div>
                    <dt>Customer</dt>
                    <dd>{{ $nasabah->nama }}</dd>
                </div>
                <div>
                    <dt>Generation</dt>
                    <dd>{{ $nasabah->angkatan }}</dd>
                </div>
                <div>
                    <dt>Study Program</dt>
                    <dd>{{ $nasabah->program_studi }}</dd>
                </div>
                <div>
                    <dt>Total Saldo</dt>
                    <dd><x-rupiah :value="$nasabah->rekening->sum('saldo')" /></dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="panel section-gap">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Rekening</h2>
                <p class="panel__subtitle">Rekening simpanan milik nasabah.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. Rekening</th>
                        <th>Status</th>
                        <th class="num">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($nasabah->rekening as $rekening)
                        <tr>
                            <td>{{ $rekening->no_rekening }}</td>
                            <td>
                                <span class="badge {{ $rekening->isAktif() ? '' : 'badge--muted' }}">
                                    {{ ucfirst($rekening->status) }}
                                </span>
                            </td>
                            <td class="num"><x-rupiah :value="$rekening->saldo" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="empty-row">Nasabah belum memiliki rekening.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel section-gap">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Riwayat Transaksi</h2>
                <p class="panel__subtitle">Dua puluh transaksi terakhir nasabah ini.</p>
            </div>
        </div>

        @include('dashboard.partials.transaksi-table', ['transaksi' => $transaksi])
    </section>
@endsection
