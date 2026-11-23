@extends('layouts.dashboard')

@section('title', 'Data Nasabah')
@section('eyebrow', 'Operasional')
@section('page-title', 'Data Nasabah')

@section('content')
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Nasabah BankMini</h2>
                <p class="panel__subtitle">Account number, customer, generation, dan study program.</p>
            </div>

            @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_CS))
                <a class="btn btn--small" href="{{ route('nasabah.create') }}">Tambah Nasabah</a>
            @endif
        </div>

        <div class="panel__body">
            <form method="GET" action="{{ route('nasabah.index') }}" class="toolbar">
                <div class="field" style="flex: 1 1 260px;">
                    <label class="label" for="q">Cari nasabah</label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        class="input"
                        value="{{ $q }}"
                        placeholder="Nama, NISN/account number, angkatan, atau program studi"
                    >
                </div>
                <button type="submit" class="btn">Cari</button>
                @if ($q !== '')
                    <a class="btn btn--secondary" href="{{ route('nasabah.index') }}">Reset</a>
                @endif
            </form>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Account Number</th>
                        <th>Customer</th>
                        <th>Generation</th>
                        <th>Study Program</th>
                        <th class="num">Saldo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($nasabah as $item)
                        <tr>
                            <td>
                                @forelse ($item->rekening as $rekening)
                                    {{ $rekening->no_rekening }}
                                    <span class="badge {{ $rekening->isAktif() ? '' : 'badge--muted' }}">
                                        {{ ucfirst($rekening->status) }}
                                    </span>
                                    @if (! $loop->last)
                                        <br>
                                    @endif
                                @empty
                                    <span class="muted">Belum ada rekening</span>
                                @endforelse
                            </td>
                            <td>{{ $item->nama }}</td>
                            <td>{{ $item->angkatan }}</td>
                            <td>{{ $item->program_studi }}</td>
                            <td class="num">
                                <x-rupiah :value="$item->rekening->sum('saldo')" />
                            </td>
                            <td class="num">
                                <a class="btn btn--secondary btn--small" href="{{ route('nasabah.show', $item) }}">Detail</a>
                                @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_CS))
                                    <a class="btn btn--secondary btn--small" href="{{ route('nasabah.edit', $item) }}">Ubah</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-row">Belum ada nasabah yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$nasabah" />
    </section>
@endsection
