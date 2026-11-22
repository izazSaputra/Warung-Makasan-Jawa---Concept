@php
    $warung = \App\Support\Warung::pengaturan();
    $menunggu = \App\Models\Pesanan::query()->where('status', \App\Enums\StatusPesanan::MenungguKonfirmasi->value)->count();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('judul', 'Pengelola') | Pengelola {{ $warung->nama_resmi }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/warung.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="admin-body">
<div class="admin">
    <aside class="admin__samping">
        <div class="admin__merek">
            <a href="{{ route('admin.pesanan') }}" class="logo">{{ $warung->nama_resmi }}</a>
            <span class="admin__peran">Pengelola</span>
        </div>

        <nav class="admin__nav" aria-label="Navigasi pengelola">
            <a href="{{ route('admin.pesanan') }}" @class(['admin__link', 'admin__link--aktif' => request()->routeIs('admin.pesanan*')])>
                Pesanan
                @if ($menunggu > 0)<span class="admin__angka" aria-label="{{ $menunggu }} menunggu konfirmasi">{{ $menunggu }}</span>@endif
            </a>
            <a href="{{ route('admin.menu') }}" @class(['admin__link', 'admin__link--aktif' => request()->routeIs('admin.menu*')])>Menu</a>
            <a href="{{ route('admin.pengaturan') }}" @class(['admin__link', 'admin__link--aktif' => request()->routeIs('admin.pengaturan*')])>Pengaturan</a>
            <a href="{{ route('admin.ringkasan') }}" @class(['admin__link', 'admin__link--aktif' => request()->routeIs('admin.ringkasan')])>Ringkasan</a>
        </nav>

        <div class="admin__bawah">
            <span class="pil {{ $warung->pemesanan_dibuka ? 'pil--buka' : 'pil--tutup' }}">
                {{ $warung->pemesanan_dibuka ? 'Pemesanan dibuka' : 'Pemesanan ditutup' }}
            </span>
            <a href="{{ route('beranda') }}" class="admin__link" target="_blank" rel="noopener">Lihat situs</a>
            <form method="POST" action="{{ route('admin.keluar') }}">
                @csrf
                <button type="submit" class="admin__link admin__keluar">Keluar</button>
            </form>
        </div>
    </aside>

    <main class="admin__isi">
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
</div>
</body>
</html>
