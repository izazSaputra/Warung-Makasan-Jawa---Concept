@php
    $warung = $warung ?? \App\Support\Warung::pengaturan();
    $jumlahKeranjang = app(\App\Services\KeranjangService::class)->jumlahItem();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Beranda') | {{ $warung->nama_resmi }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/warung.css') }}">
</head>
<body>
<header class="header">
    <div class="wadah header__isi">
        <a href="{{ route('beranda') }}" class="logo">{{ $warung->nama_resmi }}</a>
        <span class="pil {{ $warung->pemesanan_dibuka ? 'pil--buka' : 'pil--tutup' }}">
            {{ $warung->pemesanan_dibuka ? 'Pemesanan dibuka' : 'Pemesanan ditutup' }}
        </span>
        <nav class="nav" aria-label="Navigasi utama">
            <a href="{{ route('beranda') }}" @class(['nav__link', 'nav__link--aktif' => request()->routeIs('beranda')])>Beranda</a>
            <a href="{{ route('menu') }}" @class(['nav__link', 'nav__link--aktif' => request()->routeIs('menu')])>Menu</a>
            <a href="{{ route('budget') }}" @class(['nav__link', 'nav__link--aktif' => request()->routeIs('budget')])>Budget</a>
            <a href="{{ route('cek-pesanan') }}" @class(['nav__link', 'nav__link--aktif' => request()->routeIs('cek-pesanan')])>Cek Pesanan</a>
        </nav>
        <a href="{{ route('checkout') }}" class="keranjang-tombol">
            @include('partials.ikon', ['nama' => 'keranjang'])
            <span>{{ $jumlahKeranjang }} item</span>
        </a>
    </div>
</header>

<main class="wadah">
    @if (session('pesan'))
        <div class="notif notif--ok" role="status">{{ session('pesan') }}</div>
    @endif
    @if (session('galat'))
        <div class="notif notif--galat" role="alert">{{ session('galat') }}</div>
    @endif
    @if ($errors->any())
        <div class="notif notif--galat" role="alert">{{ $errors->first() }}</div>
    @endif

    @yield('isi')
</main>

<footer class="footer">
    <div class="wadah footer__tata">
        <div>
            <h2>{{ $warung->nama_resmi }}</h2>
            <p class="muted">Pesan lewat website, ambil di warung.</p>
        </div>
        <div>
            <h2>Alamat dan kontak</h2>
            <p>{{ $warung->alamat ?: 'Alamat warung' }}</p>
            <p>{{ $warung->kontak ?: 'Nomor kontak warung' }}</p>
            @if ($warung->jam_buka)
                <p>Jam buka: {{ $warung->jam_buka }}</p>
            @endif
        </div>
        <div>
            <h2>Ketentuan pemesanan</h2>
            <p>{{ $warung->info_pengambilan ?: 'Pesanan diambil di warung. Bayar saat mengambil pesanan.' }}</p>
        </div>
    </div>
</footer>
</body>
</html>
