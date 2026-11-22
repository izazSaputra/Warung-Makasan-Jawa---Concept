@extends('layouts.app')

@section('judul', 'Cek Pesanan')

@section('isi')
    <h1 style="margin-bottom:8px">Cek Status Pesanan</h1>
    <p class="muted" style="margin-bottom:16px">Masukkan kode pesanan dan nomor kontak yang dipakai saat memesan. Tidak perlu akun.</p>

    <form method="POST" action="{{ route('cek-pesanan.proses') }}" class="form-kartu" style="max-width:560px" novalidate>
        @csrf
        <div class="form-grup">
            <label for="kode">Kode pesanan</label>
            <input id="kode" name="kode" type="text" value="{{ old('kode') }}" placeholder="Contoh: WMJ-7K3Q9F" autocomplete="off" required>
            @error('kode') <p class="galat-teks">{{ $message }}</p> @enderror
        </div>
        <div class="form-grup">
            <label for="kontak">Nomor kontak</label>
            <input id="kontak" name="kontak" type="tel" inputmode="tel" placeholder="Contoh: 0812-3456-7890" autocomplete="tel" required>
            @error('kontak') <p class="galat-teks">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn btn--utama btn--besar">Cek Pesanan</button>
    </form>

    <section id="lupa-kode" class="form-kartu" style="max-width:560px;margin-top:24px" aria-labelledby="judul-lupa-kode">
        <div>
            <h2 id="judul-lupa-kode">Lupa kode pesanan?</h2>
            <p class="muted">Masukkan nama dan nomor kontak yang sama seperti saat memesan. Kami tampilkan kode dan status pesanan 24 jam terakhir.</p>
        </div>

        @if (session('hasil_lupa'))
            <div class="notif notif--ok" role="status" style="margin-bottom:0">
                <p>Pesanan ditemukan. Masukkan kode di atas bersama nomor kontak untuk melihat rinciannya.</p>
                <ul style="margin:8px 0 0;padding-left:0;list-style:none;display:flex;flex-direction:column;gap:8px">
                    @foreach (session('hasil_lupa') as $ditemukan)
                        <li style="display:flex;flex-wrap:wrap;align-items:center;gap:8px">
                            <strong>{{ $ditemukan['kode'] }}</strong>
                            @include('admin._badge', ['status' => \App\Enums\StatusPesanan::from($ditemukan['status'])])
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('galat_lupa'))
            <p class="galat-teks" role="alert">{{ session('galat_lupa') }}</p>
        @endif

        <form method="POST" action="{{ route('cek-pesanan.lupa-kode') }}" novalidate style="display:flex;flex-direction:column;gap:20px">
            @csrf
            <div class="form-grup">
                <label for="lupa_nama">Nama pemesan</label>
                <input id="lupa_nama" name="lupa_nama" type="text" value="{{ old('lupa_nama') }}" autocomplete="name" required>
                @error('lupa_nama') <p class="galat-teks">{{ $message }}</p> @enderror
            </div>
            <div class="form-grup">
                <label for="lupa_kontak">Nomor kontak</label>
                <input id="lupa_kontak" name="lupa_kontak" type="tel" inputmode="tel" placeholder="Contoh: 0812-3456-7890" autocomplete="tel" required>
                @error('lupa_kontak') <p class="galat-teks">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn--garis">Cari Kode Pesanan</button>
        </form>

        <p class="bantu">Tetap tidak ketemu? Pesanan tetap bisa diambil. Sebutkan nama dan nomor kontak saat mengambil di warung.</p>
    </section>
@endsection
