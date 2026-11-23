@extends('layouts.dashboard')

@section('title', 'Laporan Shift')
@section('eyebrow', 'Operasional')
@section('page-title', 'Laporan Shift')

@section('content')
    @if ($errors->any())
        <div class="notice notice--danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Laporan Shift Mingguan</h2>
                <p class="panel__subtitle">
                    Setiap shift dimulai sendiri oleh teller dengan mengetik namanya.
                    Satu minggu hanya untuk satu petugas, Senin sampai Jumat.
                </p>
            </div>
        </div>

        <div class="panel__body">
            <div class="ledger-summary">
                <div class="summary-box">
                    <p class="summary-box__label">Petugas minggu ini</p>
                    <p class="summary-box__value">
                        {{ $shiftMingguIni?->nama ?? 'Belum ada shift' }}
                    </p>
                </div>
                <div class="summary-box">
                    <p class="summary-box__label">Shift pada filter</p>
                    <p class="summary-box__value">{{ number_format($shifts->total(), 0, ',', '.') }} shift</p>
                </div>
                <div class="summary-box">
                    <p class="summary-box__label">Hari ini</p>
                    <p class="summary-box__value">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
                </div>
            </div>

            <form method="GET" action="{{ route('shift.index') }}" class="toolbar">
                <div class="field">
                    <label class="label" for="periode">Filter bulan</label>
                    <input id="periode" name="periode" type="month" class="input" value="{{ $periode }}">
                </div>
                <button type="submit" class="btn">Terapkan</button>
                <a class="btn btn--secondary" href="{{ route('shift.index', ['periode' => '']) }}">Semua</a>
            </form>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Minggu</th>
                        <th>Petugas</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th class="num">Durasi</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shifts as $item)
                        <tr>
                            <td>{{ $item->periodeLabel() }}</td>
                            <td>
                                {{ $item->nama }}
                                <br>
                                <span class="muted">Akun: {{ $item->user?->name ?? '—' }}</span>
                            </td>
                            <td>{{ $item->mulai_at->format('d M Y H:i') }}</td>
                            <td>{{ $item->selesai_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="num">{{ $item->durasiLabel() }}</td>
                            <td>
                                @if ($item->isAktif())
                                    <span class="badge badge--amber">Berlangsung</span>
                                @else
                                    <span class="badge">Selesai</span>
                                @endif
                            </td>
                            <td class="num">
                                <a class="btn btn--secondary btn--small" href="{{ route('shift.show', $item) }}">Detail</a>

                                @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN))
                                    @if ($item->isAktif())
                                        <form
                                            method="POST"
                                            action="{{ route('shift.tutup', $item) }}"
                                            style="display: inline;"
                                            onsubmit="return confirm('Tutup shift {{ $item->nama }} sekarang?');"
                                        >
                                            @csrf
                                            <button type="submit" class="btn btn--small">Tutup</button>
                                        </form>
                                    @endif

                                    <form
                                        method="POST"
                                        action="{{ route('shift.destroy', $item) }}"
                                        style="display: inline;"
                                        onsubmit="return confirm('Hapus catatan shift {{ $item->nama }}?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--small">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-row">Belum ada shift pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$shifts" />
    </section>
@endsection
