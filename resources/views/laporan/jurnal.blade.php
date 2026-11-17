@extends('layouts.dashboard')

@section('title', 'Jurnal Umum')
@section('eyebrow', 'Laporan')
@section('page-title', 'Jurnal Umum')

@section('content')
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Jurnal Debit &amp; Kredit</h2>
                <p class="panel__subtitle">Setiap transaksi menghasilkan jurnal yang seimbang.</p>
            </div>
        </div>

        <div class="panel__body">
            <form method="GET" action="{{ route('laporan.jurnal') }}" class="toolbar">
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
                    <a class="btn btn--secondary" href="{{ route('laporan.jurnal') }}">Reset</a>
                @endif
            </form>
        </div>

        @include('dashboard.partials.jurnal-table', ['jurnal' => $jurnal])

        <x-pagination :paginator="$jurnal" />
    </section>
@endsection
