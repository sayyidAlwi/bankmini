@php
    $shiftAktif = $shiftAktif ?? null;
    $shiftMingguIni = $shiftMingguIni ?? null;
@endphp

@error('shift')
    <div class="notice notice--danger" role="alert">{{ $message }}</div>
@enderror

<section class="panel">
    <div class="panel__header">
        <div>
            <h2 class="panel__title">Shift Mingguan</h2>
            <p class="panel__subtitle">
                Senin&ndash;Jumat &middot; satu shift untuk satu minggu
            </p>
        </div>

        @if ($shiftAktif)
            <span class="badge badge--amber">Sedang bertugas</span>
        @elseif ($shiftMingguIni)
            <span class="badge badge--muted">Shift minggu ini selesai</span>
        @else
            <span class="badge badge--blue">Belum ada shift</span>
        @endif
    </div>

    <div class="panel__body">
        @if ($shiftAktif)
            <div class="ledger-summary">
                <div class="summary-box">
                    <p class="summary-box__label">Petugas</p>
                    <p class="summary-box__value">{{ $shiftAktif->nama }}</p>
                </div>
                <div class="summary-box">
                    <p class="summary-box__label">Periode</p>
                    <p class="summary-box__value">{{ $shiftAktif->periodeLabel() }}</p>
                </div>
                <div class="summary-box">
                    <p class="summary-box__label">Mulai</p>
                    <p class="summary-box__value">
                        {{ $shiftAktif->mulai_at->locale('id')->translatedFormat('l, d M Y H:i') }}
                    </p>
                </div>
                <div class="summary-box">
                    <p class="summary-box__label">Durasi berjalan</p>
                    <p class="summary-box__value">{{ $shiftAktif->durasiLabel() }}</p>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('shift.selesai') }}"
                onsubmit="return confirm('Selesaikan shift sekarang?')"
            >
                @csrf
                <button type="submit" class="btn">Selesai Shift</button>
            </form>
        @elseif ($shiftMingguIni)
            <div class="ledger-summary">
                <div class="summary-box">
                    <p class="summary-box__label">Petugas</p>
                    <p class="summary-box__value">{{ $shiftMingguIni->nama }}</p>
                </div>
                <div class="summary-box">
                    <p class="summary-box__label">Periode</p>
                    <p class="summary-box__value">{{ $shiftMingguIni->periodeLabel() }}</p>
                </div>
                <div class="summary-box">
                    <p class="summary-box__label">Mulai</p>
                    <p class="summary-box__value">
                        {{ $shiftMingguIni->mulai_at->locale('id')->translatedFormat('l, d M Y H:i') }}
                    </p>
                </div>
                <div class="summary-box">
                    <p class="summary-box__label">Durasi</p>
                    <p class="summary-box__value">{{ $shiftMingguIni->durasiLabel() }}</p>
                </div>
            </div>

            @if ($shiftMingguIni->user_id === auth()->id())
                <div class="notice notice--success">
                    Shift Anda minggu ini sudah selesai. Terima kasih, {{ $shiftMingguIni->nama }}.
                </div>
            @else
                <div class="notice notice--info">
                    Shift minggu ini sudah dimulai oleh
                    <strong>{{ $shiftMingguIni->nama }}</strong>.
                    Satu minggu hanya untuk satu petugas.
                </div>
            @endif
        @else
            <form method="POST" action="{{ route('shift.mulai') }}">
                @csrf

                <div class="field" style="max-width: 420px;">
                    <label class="label" for="nama">Nama Anda</label>
                    <input
                        id="nama"
                        name="nama"
                        type="text"
                        class="input @error('nama') is-invalid @enderror"
                        value="{{ old('nama', auth()->user()->name) }}"
                        maxlength="100"
                        placeholder="Ketik nama Anda"
                        required
                    >
                    @error('nama')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                    <span class="muted">
                        Ketik nama Anda untuk memulai shift minggu ini. Shift berakhir saat Anda
                        menekan Selesai Shift.
                    </span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Mulai Shift</button>
                </div>
            </form>
        @endif
    </div>
</section>
