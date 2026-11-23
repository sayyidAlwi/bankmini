@extends('layouts.dashboard')

@section('title', 'Pratinjau Impor')
@section('eyebrow', 'Sistem')
@section('page-title', 'Pratinjau Impor')

@section('content')
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Pratinjau Data</h2>
                <p class="panel__subtitle">
                    {{ $namaFile }} &middot; tanggal saldo awal {{ \Illuminate\Support\Carbon::parse($tanggal)->format('d/m/Y') }}
                </p>
            </div>
            <span class="badge {{ $hasil['ada_kesalahan'] ? 'badge--danger' : '' }}">
                {{ $hasil['ada_kesalahan'] ? 'Perlu Perbaikan' : 'Siap Disimpan' }}
            </span>
        </div>

        <div class="panel__body">
            <div class="ledger-summary">
                <div class="summary-box">
                    <p class="summary-box__label">Jumlah Nasabah</p>
                    <p class="summary-box__value">{{ number_format($hasil['jumlah'], 0, ',', '.') }}</p>
                </div>
                <div class="summary-box">
                    <p class="summary-box__label">Total Saldo Awal</p>
                    <p class="summary-box__value">
                        Rp {{ number_format((float) $hasil['total'], 2, ',', '.') }}
                    </p>
                </div>
                <div class="summary-box">
                    <p class="summary-box__label">Baris Bermasalah</p>
                    <p class="summary-box__value">
                        {{ number_format(collect($hasil['rows'])->where('errors', '!=', [])->count(), 0, ',', '.') }}
                    </p>
                </div>
            </div>

            @if ($hasil['ada_kesalahan'])
                <div class="notice notice--danger">
                    Ada baris yang belum valid. Perbaiki file Excel lalu unggah ulang.
                    Tidak ada data yang disimpan sampai semua baris benar.
                </div>
            @else
                <div class="notice notice--success">
                    Semua baris valid. Periksa kembali data di bawah, lalu tekan
                    <strong>Simpan Sekarang</strong>. Tindakan ini menambah nasabah, rekening,
                    dan transaksi saldo awal secara permanen.
                </div>
            @endif
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Sheet</th>
                        <th>Baris</th>
                        <th>NIS</th>
                        <th>Customer</th>
                        <th>Generation</th>
                        <th>Study Program</th>
                        <th class="num">Saldo Awal</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hasil['rows'] as $row)
                        <tr>
                            <td>{{ $row['sheet'] }}</td>
                            <td>{{ $row['nomor'] }}</td>
                            <td>{{ $row['no_rekening'] !== '' ? $row['no_rekening'] : '—' }}</td>
                            <td>{{ $row['nama'] !== '' ? $row['nama'] : '—' }}</td>
                            <td>{{ $row['angkatan'] !== '' ? $row['angkatan'] : '—' }}</td>
                            <td>{{ $row['program_studi'] !== '' ? $row['program_studi'] : '—' }}</td>
                            <td class="num">Rp {{ $row['saldo_tampil'] }}</td>
                            <td>
                                <span class="badge {{ $row['status'] === 'aktif' ? '' : 'badge--muted' }}">
                                    {{ $row['status_tampil'] }}
                                </span>
                            </td>
                            <td>
                                @if ($row['errors'] !== [])
                                    <ul class="error-list">
                                        @foreach ($row['errors'] as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="badge">Valid</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="panel__body">
            <div class="form-actions">
                <form method="POST" action="{{ route('admin.impor.cancel') }}">
                    @csrf
                    <button type="submit" class="btn btn--secondary">Batalkan</button>
                </form>

                @unless ($hasil['ada_kesalahan'])
                    <form method="POST" action="{{ route('admin.impor.store') }}">
                        @csrf
                        <button type="submit" class="btn">Simpan Sekarang</button>
                    </form>
                @else
                    <a class="btn" href="{{ route('admin.impor.create') }}">Unggah Ulang</a>
                @endunless
            </div>
        </div>
    </section>
@endsection
