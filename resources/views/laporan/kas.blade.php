@extends('layouts.dashboard')

@section('title', 'Laporan Kas')
@section('eyebrow', 'Laporan')
@section('page-title', 'Laporan Kas')

@section('content')
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">{{ $akun->kode_akun }} &mdash; {{ $akun->nama_akun }}</h2>
                <p class="panel__subtitle">
                    Mutasi kas dari setoran dan penarikan nasabah.
                </p>
            </div>
            <span class="badge">Aset</span>
        </div>

        <div class="panel__body">
            <form method="GET" action="{{ route('laporan.kas') }}" class="toolbar">
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
                    <a class="btn btn--secondary" href="{{ route('laporan.kas') }}">Reset</a>
                @endif
            </form>
        </div>
    </section>

    <section class="panel section-gap">
        <div class="panel__body">
            @include('laporan.partials.ledger-table')
        </div>
    </section>
@endsection
