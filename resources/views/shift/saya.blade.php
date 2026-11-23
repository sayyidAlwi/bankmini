@extends('layouts.dashboard')

@section('title', 'Shift Saya')
@section('eyebrow', 'Operasional')
@section('page-title', 'Shift Saya')

@section('content')
    @include('shift.partials.status', [
        'shiftMingguIni' => $shiftMingguIni,
        'shiftAktif' => $shiftAktif,
    ])

    <section class="panel section-gap">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Riwayat Shift</h2>
                <p class="panel__subtitle">Sepuluh shift terakhir yang Anda mulai.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Minggu</th>
                        <th>Nama</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th class="num">Durasi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayat as $item)
                        <tr>
                            <td>{{ $item->periodeLabel() }}</td>
                            <td>{{ $item->nama }}</td>
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-row">Belum ada shift yang Anda mulai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$riwayat" />
    </section>
@endsection
