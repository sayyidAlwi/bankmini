@extends('layouts.dashboard')

@section('title', 'Pengguna')
@section('eyebrow', 'Sistem')
@section('page-title', 'Pengguna')

@section('content')
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Akun Staf</h2>
                <p class="panel__subtitle">Kelola akun dan hak akses staf BankMini.</p>
            </div>
            <a class="btn btn--small" href="{{ route('admin.users.create') }}">Tambah Pengguna</a>
        </div>

        <div class="panel__body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="toolbar">
                <div class="field" style="flex: 1 1 260px;">
                    <label class="label" for="q">Cari pengguna</label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        class="input"
                        value="{{ $q }}"
                        placeholder="Nama atau username"
                    >
                </div>
                <button type="submit" class="btn">Cari</button>
                @if ($q !== '')
                    <a class="btn btn--secondary" href="{{ route('admin.users.index') }}">Reset</a>
                @endif
            </form>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $item)
                        @php
                            $badge = match ($item->role) {
                                \App\Models\User::ROLE_ADMIN => 'badge--blue',
                                \App\Models\User::ROLE_TELLER => 'badge--amber',
                                \App\Models\User::ROLE_CS => 'badge--muted',
                                default => '',
                            };
                        @endphp
                        <tr>
                            <td>
                                {{ $item->name }}
                                @if ($item->is(auth()->user()))
                                    <span class="badge badge--muted">Akun saya</span>
                                @endif
                            </td>
                            <td>{{ $item->username }}</td>
                            <td>
                                <span class="badge {{ $badge }}">{{ $item->roleLabel() }}</span>
                            </td>
                            <td class="num">
                                <a class="btn btn--secondary btn--small" href="{{ route('admin.users.edit', $item) }}">
                                    Ubah
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-row">Belum ada pengguna yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$users" />
    </section>
@endsection
