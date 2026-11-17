@extends('layouts.dashboard')

@section('title', 'Buku Besar')
@section('eyebrow', 'Laporan')
@section('page-title', 'Buku Besar')

@section('content')
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Buku Besar Akun</h2>
                <p class="panel__subtitle">Mutasi per akun dengan saldo berjalan.</p>
            </div>
            <span class="badge">{{ ucfirst($akun->kategori) }}</span>
        </div>

        <div class="panel__body">
            <form method="GET" action="{{ route('laporan.buku-besar') }}" class="toolbar">
                <div class="field" style="flex: 1 1 260px;">
                    <label class="label" for="akun_id">Akun</label>
                    <select id="akun_id" name="akun_id" class="select">
                        @foreach ($akuns as $item)
                            <option value="{{ $item->id }}" @selected($item->id === $akun->id)>
                                {{ $item->kode_akun }} &mdash; {{ $item->nama_akun }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="label" for="dari">Dari tanggal</label>
                    <input id="dari" name="dari" type="date" class="input" value="{{ $dari }}">
                </div>
                <div class="field">
                    <label class="label" for="sampai">Sampai tanggal</label>
                    <input id="sampai" name="sampai" type="date" class="input" value="{{ $sampai }}">
                </div>
                <button type="submit" class="btn">Terapkan</button>
                @if ($dari || $sampai)
                    <a class="btn btn--secondary" href="{{ route('laporan.buku-besar', ['akun_id' => $akun->id]) }}">Reset</a>
                @endif
            </form>
        </div>
    </section>

    <section class="panel section-gap">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">{{ $akun->kode_akun }} &mdash; {{ $akun->nama_akun }}</h2>
                <p class="panel__subtitle">
                    Saldo normal: {{ $akun->isDebitNormal() ? 'Debit' : 'Kredit' }}
                </p>
            </div>
        </div>

        <div class="panel__body">
            @include('laporan.partials.ledger-table')
        </div>
    </section>
@endsection
