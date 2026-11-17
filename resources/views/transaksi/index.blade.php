@extends('layouts.dashboard')

@section('title', 'Transaksi')
@section('eyebrow', 'Operasional')
@section('page-title', 'Transaksi')

@section('content')
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Riwayat Transaksi</h2>
                <p class="panel__subtitle">Setoran dan penarikan yang tercatat di BankMini.</p>
            </div>

            @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TELLER))
                <a class="btn btn--small" href="{{ route('transaksi.create') }}">Transaksi Baru</a>
            @endif
        </div>

        <div class="panel__body">
            <form method="GET" action="{{ route('transaksi.index') }}" class="toolbar">
                <div class="field">
                    <label class="label" for="dari">Dari tanggal</label>
                    <input id="dari" name="dari" type="date" class="input" value="{{ $dari }}">
                </div>
                <div class="field">
                    <label class="label" for="sampai">Sampai tanggal</label>
                    <input id="sampai" name="sampai" type="date" class="input" value="{{ $sampai }}">
                </div>
                <button type="submit" class="btn">Filter</button>
                @if ($dari || $sampai)
                    <a class="btn btn--secondary" href="{{ route('transaksi.index') }}">Reset</a>
                @endif
            </form>
        </div>

        @include('dashboard.partials.transaksi-table', ['transaksi' => $transaksi])

        <x-pagination :paginator="$transaksi" />
    </section>
@endsection
