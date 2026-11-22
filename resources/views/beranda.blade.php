@extends('layouts.app')

@section('judul', 'Beranda')

@section('isi')
    @if ($warung->pemesanan_dibuka)
        <div class="banner banner--buka">
            <strong>Pemesanan dibuka</strong>
            @if ($warung->jam_buka)
                <span>Jam buka: {{ $warung->jam_buka }}</span>
            @endif
        </div>
    @else
        <div class="banner banner--tutup">
            <strong>Pemesanan sedang ditutup.</strong>
            <span>Menu tetap bisa dilihat, tetapi belum bisa dipesan.</span>
        </div>
    @endif

    <section class="hero-tata">
        <div class="hero">
            <span class="eyebrow">Pesan, lalu ambil di warung</span>
            <h1>Masakan Jawa, tinggal pilih dan ambil</h1>
            <p class="muted">Lihat menu dan harga, pilih sendiri atau cari kombinasi sesuai budget, lalu ambil pesananmu di warung.</p>
            <a href="{{ route('menu') }}" class="btn btn--utama btn--besar">Lihat Menu</a>
        </div>
        <div class="kartu-budget">
            <span class="eyebrow">Fitur andalan</span>
            <h2>Makan Enak Sesuai Budget</h2>
            <p>Masukkan nominal uangmu, kami susun kombinasi menu yang totalnya tidak melebihi budget.</p>
            <a href="{{ route('budget') }}" class="btn btn--garis btn--besar">Coba Cari Makan Sesuai Budget</a>
        </div>
    </section>

    <section class="info-tata" aria-label="Informasi warung">
        <div class="info">
            <div class="info__label">Nama warung</div>
            <div class="info__nilai">{{ $warung->nama_resmi }}</div>
        </div>
        <div class="info">
            <div class="info__label">Lokasi pengambilan</div>
            <div class="info__nilai">{{ $warung->alamat ?: 'Alamat warung' }}</div>
        </div>
        <div class="info">
            <div class="info__label">Kontak</div>
            <div class="info__nilai">{{ $warung->kontak ?: 'Nomor kontak warung' }}</div>
        </div>
    </section>

    <section>
        <div class="bagian-kepala">
            <h2>Pilihan menu</h2>
            <a href="{{ route('menu') }}">Lihat seluruh menu</a>
        </div>
        <div class="grid-menu grid-menu--4">
            @forelse ($pratinjau as $item)
                @include('partials.kartu-menu', ['item' => $item, 'dibuka' => $warung->pemesanan_dibuka])
            @empty
                <p class="muted">Belum ada menu yang tersedia.</p>
            @endforelse
        </div>
    </section>

    <section class="catatan-tata">
        <div class="catatan">
            <strong>Konfirmasi warung</strong>
            Pesanan baru pasti setelah dikonfirmasi warung.
        </div>
        <div class="catatan">
            <strong>Pengambilan dan pembayaran</strong>
            {{ $warung->info_pengambilan ?: 'Pesanan diambil di warung. Bayar saat mengambil pesanan.' }}
        </div>
    </section>
@endsection
