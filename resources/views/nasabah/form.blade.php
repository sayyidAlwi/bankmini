@php
    $nasabah = $nasabah ?? null;
    $editing = $nasabah !== null;
@endphp

<form method="POST" action="{{ $editing ? route('nasabah.update', $nasabah) : route('nasabah.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="form-grid">
        <div class="field">
            <label class="label" for="nama">Nama Nasabah</label>
            <input
                id="nama"
                name="nama"
                type="text"
                class="input @error('nama') is-invalid @enderror"
                value="{{ old('nama', $nasabah?->nama) }}"
                maxlength="100"
                required
            >
            @error('nama')
                <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label class="label" for="kelas">Kelas</label>
            <input
                id="kelas"
                name="kelas"
                type="text"
                class="input @error('kelas') is-invalid @enderror"
                value="{{ old('kelas', $nasabah?->kelas) }}"
                maxlength="30"
                required
            >
            @error('kelas')
                <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label class="label" for="no_hp">Nomor HP</label>
            <input
                id="no_hp"
                name="no_hp"
                type="text"
                class="input @error('no_hp') is-invalid @enderror"
                value="{{ old('no_hp', $nasabah?->no_hp) }}"
                maxlength="20"
                required
            >
            @error('no_hp')
                <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <div class="field field--full">
            <label class="label" for="alamat">Alamat</label>
            <textarea
                id="alamat"
                name="alamat"
                class="input @error('alamat') is-invalid @enderror"
                maxlength="500"
                required
            >{{ old('alamat', $nasabah?->alamat) }}</textarea>
            @error('alamat')
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
