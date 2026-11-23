@extends('layouts.dashboard')

@section('title', 'Ubah Nasabah')
@section('eyebrow', 'Operasional')
@section('page-title', 'Ubah Nasabah')

@section('content')
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">{{ $nasabah->nama }}</h2>
                <p class="panel__subtitle">{{ $nasabah->angkatan }} &middot; {{ $nasabah->program_studi }}</p>
            </div>
        </div>

        <div class="panel__body">
            @include('nasabah.form', ['nasabah' => $nasabah])
        </div>
    </section>
@endsection
