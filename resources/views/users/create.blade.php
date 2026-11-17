@extends('layouts.dashboard')

@section('title', 'Tambah Pengguna')
@section('eyebrow', 'Sistem')
@section('page-title', 'Tambah Pengguna')

@section('content')
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Akun Staf Baru</h2>
                <p class="panel__subtitle">Tentukan role agar akses menu sesuai tugasnya.</p>
            </div>
        </div>

        <div class="panel__body">
            @include('users.form')
        </div>
    </section>
@endsection
