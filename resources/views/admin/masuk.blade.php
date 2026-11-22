<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk Pengelola | {{ $warung->nama_resmi }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/warung.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
<main class="masuk">
    <form method="POST" action="{{ route('admin.masuk.proses') }}" class="form-kartu masuk__kartu">
        @csrf
        <div>
            <div class="logo">{{ $warung->nama_resmi }}</div>
            <h1 style="margin-top:8px">Masuk Pengelola</h1>
            <p class="muted">Halaman ini khusus pengelola warung.</p>
        </div>

        @if (session('pesan'))<div class="notif notif--ok" role="status" style="margin:0">{{ session('pesan') }}</div>@endif
        @if (session('galat'))<div class="notif notif--galat" role="alert" style="margin:0">{{ session('galat') }}</div>@endif
        @if ($errors->any())<div class="notif notif--galat" role="alert" style="margin:0">{{ $errors->first() }}</div>@endif

        <div class="form-grup">
            <label for="email">Email pengelola</label>
            <input type="text" id="email" name="email" value="{{ old('email') }}" autocomplete="username" inputmode="email" required autofocus>
        </div>
        <div class="form-grup">
            <label for="password">Kata sandi</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn--utama btn--besar">Masuk</button>
        <a href="{{ route('beranda') }}" class="bantu">Kembali ke situs warung</a>
    </form>
</main>
</body>
</html>
