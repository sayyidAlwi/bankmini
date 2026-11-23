@extends('layouts.dashboard')

@section('title', 'Tambah Nasabah')
@section('eyebrow', 'Operasional')
@section('page-title', 'Tambah Nasabah')

@section('content')
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Pendaftaran Nasabah</h2>
                <p class="panel__subtitle">
                    Isi NISN pada Account Number. Nomor rekening harus unik untuk setiap nasabah.
                </p>
            </div>
        </div>

        <div class="panel__body">
            @include('nasabah.form')
        </div>
    </section>
@endsection
