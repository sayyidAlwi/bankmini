@extends('layouts.dashboard')

@section('title', 'Impor Data Excel')
@section('eyebrow', 'Sistem')
@section('page-title', 'Impor Data Excel')

@section('content')
    <div class="panel-grid">
        <section class="panel">
            <div class="panel__header">
                <div>
                    <h2 class="panel__title">Unggah File Excel</h2>
                    <p class="panel__subtitle">
                        Pindahkan data nasabah lama beserta saldo simpanannya.
                    </p>
                </div>
                <a class="btn btn--secondary btn--small" href="{{ route('admin.impor.template') }}">
                    Unduh Template
                </a>
            </div>

            <div class="panel__body">
                @if (session('status'))
                    <div class="notice notice--success">{{ session('status') }}</div>
                @endif

                @error('file')
                    <div class="notice notice--danger">{{ $message }}</div>
                @enderror

                @if ($namaFile)
                    <div class="notice notice--info">
                        File terakhir: <strong>{{ $namaFile }}</strong>. Pratinjau sebelumnya belum disimpan.
                        Unggah file (boleh file yang sama) untuk melanjutkan.
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.impor.preview') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="form-grid">
                        <div class="field field--full">
                            <label class="label" for="file">File Excel (.xlsx)</label>
                            <input
                                id="file"
                                name="file"
                                type="file"
                                class="input @error('file') is-invalid @enderror"
                                accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                required
                            >
                            <span class="muted" style="font-size: 0.72rem;">
                                Maksimal 5 MB dan 2.000 baris nasabah per file.
                            </span>
                        </div>

                        <div class="field">
                            <label class="label" for="tanggal">Tanggal Saldo Awal</label>
                            <input
                                id="tanggal"
                                name="tanggal"
                                type="date"
                                class="input @error('tanggal') is-invalid @enderror"
                                value="{{ old('tanggal', now()->toDateString()) }}"
                                max="{{ now()->toDateString() }}"
                                required
                            >
                            @error('tanggal')
                                <span class="error-text">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">Pratinjau Data</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel__header">
                <div>
                    <h2 class="panel__title">Cara Kerja</h2>
                    <p class="panel__subtitle">Data diperiksa dulu sebelum disimpan.</p>
                </div>
            </div>

            <div class="panel__body">
                <ol class="step-list">
                    <li>Unduh <strong>template</strong>, lalu isi data dari Excel lama.</li>
                    <li>Unggah file. Sistem memeriksa setiap baris tanpa menyimpan apa pun.</li>
                    <li>Periksa pratinjau. Bila sudah benar, tekan <strong>Simpan Sekarang</strong>.</li>
                </ol>

                <p class="muted" style="font-size: 0.8rem; margin-top: 1rem;">
                    Saldo awal dicatat sebagai transaksi <strong>setoran "Saldo awal"</strong> pada tanggal
                    yang dipilih: Debit Kas dan Kredit Simpanan. Dengan begitu laporan Kas, Simpanan,
                    dan Buku Besar tetap seimbang.
                </p>

                <div class="notice notice--info" style="margin-top: 0.9rem;">
                    Baris dengan kode nasabah kosong akan memperoleh kode otomatis
                    <strong>NSBxxxxx</strong> dan nomor rekening <strong>REKxxxxx</strong>.
                </div>
            </div>
        </section>
    </div>

    <section class="panel section-gap">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Format Kolom</h2>
                <p class="panel__subtitle">
                    Urutan kolom bebas. Nama kolom berikut dikenali sistem.
                </p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kolom</th>
                        <th>Wajib</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>nama</strong></td>
                        <td><span class="badge badge--amber">Ya</span></td>
                        <td>Nama lengkap nasabah, maksimal 100 karakter.</td>
                    </tr>
                    <tr>
                        <td><strong>kelas</strong></td>
                        <td><span class="badge badge--amber">Ya</span></td>
                        <td>Kelas nasabah, contoh <em>X-1</em>.</td>
                    </tr>
                    <tr>
                        <td><strong>alamat</strong></td>
                        <td><span class="badge badge--amber">Ya</span></td>
                        <td>Alamat lengkap.</td>
                    </tr>
                    <tr>
                        <td><strong>no_hp</strong></td>
                        <td><span class="badge badge--amber">Ya</span></td>
                        <td>Nomor HP/WhatsApp, maksimal 20 karakter.</td>
                    </tr>
                    <tr>
                        <td><strong>saldo_awal</strong></td>
                        <td><span class="badge badge--amber">Ya</span></td>
                        <td>Angka 0 atau lebih, contoh <em>50000</em> atau <em>50000,50</em>.</td>
                    </tr>
                    <tr>
                        <td><strong>kode_nasabah</strong></td>
                        <td><span class="badge badge--muted">Opsional</span></td>
                        <td>Kode lama bila ingin dipertahankan. Harus unik.</td>
                    </tr>
                    <tr>
                        <td><strong>status</strong></td>
                        <td><span class="badge badge--muted">Opsional</span></td>
                        <td><em>aktif</em> (bawaan) atau <em>nonaktif</em>.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
@endsection
