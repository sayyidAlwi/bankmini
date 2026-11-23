@extends('layouts.dashboard')

@section('title', 'Detail Shift')
@section('eyebrow', 'Operasional')
@section('page-title', 'Detail Shift')

@section('content')
    @if ($errors->any())
        <div class="notice notice--danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">{{ $shift->nama }}</h2>
                <p class="panel__subtitle">
                    {{ $shift->periodeLabel() }} &middot; akun {{ $shift->user?->name ?? '—' }}
                </p>
            </div>

            <div class="toolbar">
                <a class="btn btn--secondary btn--small" href="{{ route('shift.index') }}">Kembali</a>

                @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN))
                    @if ($shift->isAktif())
                        <form
                            method="POST"
                            action="{{ route('shift.tutup', $shift) }}"
                            onsubmit="return confirm('Tutup shift {{ $shift->nama }} sekarang?');"
                        >
                            @csrf
                            <button type="submit" class="btn btn--small">Tutup Shift</button>
                        </form>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('shift.destroy', $shift) }}"
                        onsubmit="return confirm('Hapus catatan shift {{ $shift->nama }}?');"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn--small">Hapus</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="panel__body">
            <div class="ledger-summary">
                <div class="summary-box">
                    <p class="summary-box__label">Status</p>
                    <p class="summary-box__value">{{ $shift->statusLabel() }}</p>
                </div>
                <div class="summary-box">
                    <p class="summary-box__label">Mulai</p>
                    <p class="summary-box__value">
                        {{ $shift->mulai_at->locale('id')->translatedFormat('l, d M Y H:i') }}
                    </p>
                </div>
                <div class="summary-box">
                    <p class="summary-box__label">Selesai</p>
                    <p class="summary-box__value">
                        {{ $shift->selesai_at?->locale('id')->translatedFormat('l, d M Y H:i') ?? 'Belum selesai' }}
                    </p>
                </div>
                <div class="summary-box">
                    <p class="summary-box__label">Durasi</p>
                    <p class="summary-box__value">{{ $shift->durasiLabel() }}</p>
                </div>
            </div>

            @if ($shift->catatan)
                <div class="notice notice--info">
                    {{ $shift->catatan }}
                </div>
            @endif
        </div>
    </section>
@endsection
