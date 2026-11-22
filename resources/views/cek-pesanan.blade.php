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
@endsection
