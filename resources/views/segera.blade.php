@extends('layouts.app')

@section('judul', $judul)

@section('isi')
    <div class="segera">
        <h1>{{ $judul }}</h1>
        <p class="muted">Halaman ini sedang dikerjakan dan segera hadir.</p>
        <a href="{{ route('menu') }}" class="btn btn--utama">Kembali ke Menu</a>
    </div>
@endsection
