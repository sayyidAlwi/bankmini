@extends('layouts.dashboard')

@section('title', 'Laporan Simpanan')
@section('eyebrow', 'Laporan')
@section('page-title', 'Laporan Simpanan')

@section('content')
    <div class="stat-grid">
        <x-stat-card
            label="Total Rekening"
            :value="number_format($totalRekening, 0, ',', '.')"
            hint="Sesuai filter"
            tone="green"
        />
        <x-stat-card
            label="Total Simpanan"
            :value="'Rp '.number_format((float) $totalSaldo, 0, ',', '.')"
            hint="Saldo seluruh rekening"
            tone="blue"
        />
    </div>

    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Saldo Simpanan Nasabah</h2>
                <p class="panel__subtitle">Daftar rekening beserta saldo terkini.</p>
            </div>
        </div>

        <div class="panel__body">
            <form method="GET" action="{{ route('laporan.simpanan') }}" class="toolbar">
                <div class="field">
                    <label class="label" for="status">Status rekening</label>
                    <select id="status" name="status" class="select">
                        <option value="">Semua status</option>
                        <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
                    </select>
                </div>
                <button type="submit" class="btn">Terapkan</button>
                @if ($status)
                    <a class="btn btn--secondary" href="{{ route('laporan.simpanan') }}">Reset</a>
                @endif
            </form>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. Rekening</th>
                        <th>Nasabah</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th class="num">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekening as $item)
                        <tr>
                            <td>{{ $item->no_rekening }}</td>
                            <td>
                                {{ $item->nasabah->nama }}
                                <br>
                                <span class="muted">{{ $item->nasabah->kode_nasabah }}</span>
                            </td>
                            <td>{{ $item->nasabah->kelas }}</td>
                            <td>
                                <span class="badge {{ $item->isAktif() ? '' : 'badge--muted' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td class="num"><x-rupiah :value="$item->saldo" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-row">Belum ada rekening.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$rekening" />
    </section>
@endsection
