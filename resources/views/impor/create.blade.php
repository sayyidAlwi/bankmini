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
                        Pindahkan data nasabah lama beserta saldo simpanannya, satu sheet per program studi.
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
                    <li>Unduh <strong>template</strong>. Template sudah berisi satu sheet untuk setiap program studi.</li>
                    <li>Salin data dari Excel lama ke sheet prodi yang sesuai, lalu sesuaikan tahun pada nama sheet.</li>
                    <li>Unggah file. Sistem memeriksa setiap baris di semua sheet tanpa menyimpan apa pun.</li>
                    <li>Periksa pratinjau. Bila sudah benar, tekan <strong>Simpan Sekarang</strong>.</li>
                </ol>

                <p class="muted" style="font-size: 0.8rem; margin-top: 1rem;">
                    Saldo awal dicatat sebagai transaksi <strong>setoran "Saldo awal"</strong> pada tanggal
                    yang dipilih: Debit Kas dan Kredit Simpanan. Dengan begitu laporan Kas, Simpanan,
                    dan Buku Besar tetap seimbang.
                </p>

                <div class="notice notice--info" style="margin-top: 0.9rem;">
                    <strong>Nama sheet menentukan program studi dan angkatan.</strong> Gunakan format
                    kode prodi diikuti tahun, contoh <em>AKL 2025</em>. NIS wajib diisi dan harus unik;
                    sistem tidak membuat nomor rekening otomatis saat impor.
                </div>
            </div>
        </section>
    </div>

    <section class="panel section-gap">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Format Kolom</h2>
                <p class="panel__subtitle">
                    Satu sheet berisi satu program studi. Urutan kolom bebas selama nama kolom dikenali.
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
                        <td><strong>NO</strong></td>
                        <td><span class="badge badge--muted">Opsional</span></td>
                        <td>Nomor urut. Hanya untuk memudahkan penomoran, tidak disimpan.</td>
                    </tr>
                    <tr>
                        <td><strong>NIS</strong></td>
                        <td><span class="badge badge--amber">Ya</span></td>
                        <td>NISN / account number, maksimal 30 karakter dan harus unik.</td>
                    </tr>
                    <tr>
                        <td><strong>NAMA</strong></td>
                        <td><span class="badge badge--amber">Ya</span></td>
                        <td>Nama customer, maksimal 100 karakter.</td>
                    </tr>
                    <tr>
                        <td><strong>SALDO AWAL</strong></td>
                        <td><span class="badge badge--amber">Ya</span></td>
                        <td>Angka 0 atau lebih, contoh <em>50000</em> atau <em>50000,50</em>. Boleh dikosongkan, artinya 0.</td>
                    </tr>
                    <tr>
                        <td><strong>STATUS</strong></td>
                        <td><span class="badge badge--muted">Opsional</span></td>
                        <td><em>aktif</em> (bawaan) atau <em>nonaktif</em>. Kolom boleh tidak ada.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="panel__body">
            <p class="muted" style="font-size: 0.8rem; margin: 0 0 0.6rem;">
                Program studi dan angkatan tidak perlu ditulis di kolom. Keduanya dibaca dari nama sheet:
            </p>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Sheet</th>
                            <th>Program Studi Tersimpan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($programStudi as $kode => $label)
                            <tr>
                                <td><strong>{{ $kode }} 2025</strong></td>
                                <td>{{ $label }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="muted" style="font-size: 0.8rem; margin: 0.6rem 0 0;">
                Ganti <em>2025</em> sesuai tahun angkatan. Bila nama sheet tidak memuat tahun, sistem
                memakai tahun pada nama file (contoh: <em>NAMA NASABAH ANGKATAN 2025.xlsx</em>).
                Maksimal 5 MB dan 2.000 baris nasabah per file (dihitung dari seluruh sheet).
            </p>
        </div>
    </section>
@endsection
