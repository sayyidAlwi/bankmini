@extends('layouts.dashboard')

@section('title', 'Transaksi Baru')
@section('eyebrow', 'Operasional')
@section('page-title', 'Transaksi Baru')

@section('content')
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Catat Setoran / Penarikan</h2>
                <p class="panel__subtitle">
                    Dana diproses secara nyata: saldo rekening dan jurnal kas diperbarui bersamaan.
                </p>
            </div>
        </div>

        <div class="panel__body">
            @if ($rekening->isEmpty())
                <x-empty-state
                    title="Belum ada rekening aktif"
                    description="Buat nasabah terlebih dahulu agar rekening aktif tersedia untuk transaksi."
                    icon="⇄"
                >
                    @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_CS))
                        <a class="btn btn--small" href="{{ route('nasabah.create') }}" style="margin-top: 1rem;">
                            Tambah Nasabah
                        </a>
                    @endif
                </x-empty-state>
            @else
                <form method="POST" action="{{ route('transaksi.store') }}">
                    @csrf

                    <div class="form-grid">
                        <div class="field field--full">
                            <label class="label" for="rekening_id">Rekening Nasabah</label>
                            <select
                                id="rekening_id"
                                name="rekening_id"
                                class="select @error('rekening_id') is-invalid @enderror"
                                required
                            >
                                <option value="">&mdash; Pilih rekening &mdash;</option>
                                @foreach ($rekening as $item)
                                    <option
                                        value="{{ $item->id }}"
                                        @selected((string) old('rekening_id', $selected) === (string) $item->id)
                                    >
                                        {{ $item->no_rekening }} &mdash; {{ $item->nasabah->nama }}
                                        ({{ $item->nasabah->angkatan }} &middot; {{ $item->nasabah->program_studi }}) &mdash;
                                        saldo Rp {{ number_format((float) $item->saldo, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('rekening_id')
                                <span class="error-text">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field">
                            <label class="label" for="jenis">Jenis Transaksi</label>
                            <select id="jenis" name="jenis" class="select @error('jenis') is-invalid @enderror" required>
                                <option value="setoran" @selected(old('jenis', 'setoran') === 'setoran')>Setoran</option>
                                <option value="penarikan" @selected(old('jenis') === 'penarikan')>Penarikan</option>
                            </select>
                            @error('jenis')
                                <span class="error-text">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field">
                            <label class="label" for="nominal">Nominal</label>
                            <input
                                id="nominal"
                                name="nominal"
                                type="number"
                                class="input @error('nominal') is-invalid @enderror"
                                value="{{ old('nominal') }}"
                                min="0.01"
                                step="0.01"
                                inputmode="decimal"
                                placeholder="Contoh: 50000"
                                required
                            >
                            <span class="muted" style="font-size: 0.72rem;">
                                Gunakan titik untuk desimal, contoh 50000.50
                            </span>
                            @error('nominal')
                                <span class="error-text">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="panel section-gap" style="box-shadow: none;">
                        <div class="panel__body">
                            <p class="muted" style="font-size: 0.82rem;">
                                <strong>Setoran:</strong> Debit Kas, Kredit Simpanan.
                                <br>
                                <strong>Penarikan:</strong> Debit Simpanan, Kredit Kas.
                            </p>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a class="btn btn--secondary" href="{{ route('transaksi.index') }}">Batal</a>
                        <button type="submit" class="btn">Simpan Transaksi</button>
                    </div>
                </form>
            @endif
        </div>
    </section>
@endsection
