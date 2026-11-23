@php
    $nasabah = $nasabah ?? null;
    $editing = $nasabah !== null;
    $noRekening = old('no_rekening', $nasabah?->rekening->first()?->no_rekening);
@endphp

<form method="POST" action="{{ $editing ? route('nasabah.update', $nasabah) : route('nasabah.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="form-grid">
        <div class="field">
            <label class="label" for="no_rekening">Account Number (NISN)</label>
            <input
                id="no_rekening"
                name="no_rekening"
                type="text"
                class="input @error('no_rekening') is-invalid @enderror"
                value="{{ $noRekening }}"
                maxlength="30"
                placeholder="Contoh: 10435"
                required
            >
            @error('no_rekening')
                <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label class="label" for="nama">Customer</label>
            <input
                id="nama"
                name="nama"
                type="text"
                class="input @error('nama') is-invalid @enderror"
                value="{{ old('nama', $nasabah?->nama) }}"
                maxlength="100"
                placeholder="Nama lengkap nasabah"
                required
            >
            @error('nama')
                <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label class="label" for="angkatan">Generation (Angkatan)</label>
            <input
                id="angkatan"
                name="angkatan"
                type="text"
                class="input @error('angkatan') is-invalid @enderror"
                value="{{ old('angkatan', $nasabah?->angkatan) }}"
                maxlength="30"
                required
            >
            @error('angkatan')
                <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label class="label" for="program_studi">Study Program (Program Studi)</label>
            <input
                id="program_studi"
                name="program_studi"
                type="text"
                class="input @error('program_studi') is-invalid @enderror"
                value="{{ old('program_studi', $nasabah?->program_studi) }}"
                maxlength="100"
                required
            >
            @error('program_studi')
                <span class="error-text">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="form-actions">
        <a class="btn btn--secondary" href="{{ $editing ? route('nasabah.show', $nasabah) : route('nasabah.index') }}">
            Batal
        </a>
        <button type="submit" class="btn">
            {{ $editing ? 'Simpan Perubahan' : 'Simpan Nasabah' }}
        </button>
    </div>
</form>
