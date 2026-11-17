@php
    $user = $user ?? null;
    $editing = $user !== null;
@endphp

<form method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="form-grid">
        <div class="field">
            <label class="label" for="name">Nama Lengkap</label>
            <input
                id="name"
                name="name"
                type="text"
                class="input @error('name') is-invalid @enderror"
                value="{{ old('name', $user?->name) }}"
                maxlength="100"
                required
            >
            @error('name')
                <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label class="label" for="username">Username</label>
            <input
                id="username"
                name="username"
                type="text"
                class="input @error('username') is-invalid @enderror"
                value="{{ old('username', $user?->username) }}"
                maxlength="50"
                autocomplete="off"
                required
            >
            @error('username')
                <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label class="label" for="role">Role</label>
            <select id="role" name="role" class="select @error('role') is-invalid @enderror" required>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(old('role', $user?->role) === $role)>
                        {{ \App\Models\User::roleLabels()[$role] }}
                    </option>
                @endforeach
            </select>
            @error('role')
                <span class="error-text">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label class="label" for="password">
                {{ $editing ? 'Kata Sandi Baru (opsional)' : 'Kata Sandi' }}
            </label>
            <input
                id="password"
                name="password"
                type="password"
                class="input @error('password') is-invalid @enderror"
                autocomplete="new-password"
                @if (! $editing) required @endif
            >
            @error('password')
                <span class="error-text">{{ $message }}</span>
            @enderror
            @if ($editing)
                <span class="muted" style="font-size: 0.72rem;">
                    Kosongkan jika kata sandi tidak diubah.
                </span>
            @endif
        </div>

        <div class="field">
            <label class="label" for="password_confirmation">Konfirmasi Kata Sandi</label>
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                class="input"
                autocomplete="new-password"
                @if (! $editing) required @endif
            >
        </div>
    </div>

    <div class="form-actions">
        <a class="btn btn--secondary" href="{{ route('admin.users.index') }}">Batal</a>
        <button type="submit" class="btn">
            {{ $editing ? 'Simpan Perubahan' : 'Tambah Pengguna' }}
        </button>
    </div>
</form>
