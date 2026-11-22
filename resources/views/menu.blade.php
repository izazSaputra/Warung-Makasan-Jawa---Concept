@extends('layouts.app')

@section('judul', 'Menu')

@section('isi')
    @php
        $dibuka = $warung->pemesanan_dibuka;
        $dasar = array_filter(['q' => $q !== '' ? $q : null, 'label' => $label]);
    @endphp

    @unless ($dibuka)
        <div class="banner banner--tutup">
            <strong>Pemesanan sedang ditutup.</strong>
            <span>Menu tetap bisa dilihat, tombol tambah dinonaktifkan sementara.</span>
        </div>
    @endunless

    <h1 style="margin-bottom:16px">Menu</h1>

    <form method="GET" action="{{ route('menu') }}" class="cari" role="search">
        @if ($jenis)
            <input type="hidden" name="jenis" value="{{ $jenis->value }}">
        @endif
        @if ($label)
            <input type="hidden" name="label" value="{{ $label }}">
        @endif
        <label for="q" class="sr-only">Cari menu</label>
        <input id="q" type="search" name="q" value="{{ $q }}" placeholder="Cari lauk, paket, atau minuman">
        <button type="submit" class="btn btn--utama">Cari</button>
    </form>

    <div class="chip-baris" aria-label="Penyaring jenis">
        <a href="{{ route('menu', $dasar) }}" @class(['chip', 'chip--aktif' => ! $jenis])>Semua</a>
        @foreach ($daftarJenis as $j)
            <a href="{{ route('menu', $dasar + ['jenis' => $j->value]) }}" @class(['chip', 'chip--aktif' => $jenis === $j])>{{ $j->label() }}</a>
        @endforeach
    </div>

    @php $dasarJenis = array_filter(['q' => $q !== '' ? $q : null, 'jenis' => $jenis?->value]); @endphp
    <div class="chip-baris" aria-label="Penyaring rasa">
        <span class="muted">Rasa:</span>
        @foreach (['Pedas', 'Tidak pedas'] as $rasa)
            <a href="{{ $label === $rasa ? route('menu', $dasarJenis) : route('menu', $dasarJenis + ['label' => $rasa]) }}"
               @class(['chip', 'chip--aktif' => $label === $rasa])>{{ $rasa }}</a>
        @endforeach
    </div>

    <div class="menu-tata">
        <section aria-label="Daftar menu">
            @if ($items->isEmpty())
                <div class="kosong">
                    <p>Tidak ada menu yang cocok. Coba ubah kata kunci atau penyaring.</p>
                    <a class="btn btn--garis" href="{{ route('menu') }}">Tampilkan semua menu</a>
                </div>
            @else
                <div class="grid-menu">
                    @foreach ($items as $item)
                        @include('partials.kartu-menu', ['item' => $item, 'dibuka' => $dibuka])
                    @endforeach
                </div>
            @endif
        </section>

        @include('partials.panel-keranjang', ['baris' => $baris, 'total' => $total, 'dibuka' => $dibuka])
    </div>
@endsection
