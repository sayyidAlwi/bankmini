@extends('layouts.dashboard')

@section('title', 'Ubah Pengguna')
@section('eyebrow', 'Sistem')
@section('page-title', 'Ubah Pengguna')

@section('content')
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">{{ $user->name }}</h2>
                <p class="panel__subtitle">{{ $user->username }} &middot; {{ $user->roleLabel() }}</p>
            </div>
        </div>

        <div class="panel__body">
            @include('users.form', ['user' => $user])
        </div>
    </section>
@endsection
