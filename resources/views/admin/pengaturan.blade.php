@extends('admin.layout')

@section('judul', 'Pengaturan Warung')

@section('isi')
    <div class="admin__kepala">
        <h1>Pengaturan Warung</h1>
    </div>

    <section class="kartu-putih form-sempit" style="margin-bottom:24px" aria-label="Buka atau tutup pemesanan">
        <h2>Pemesanan</h2>
        <div class="banner {{ $pengaturan->pemesanan_dibuka ? 'banner--buka' : 'banner--tutup' }}" style="margin:0">
            <strong>{{ $pengaturan->pemesanan_dibuka ? 'Pemesanan sedang dibuka.' : 'Pemesanan sedang ditutup.' }}</strong>
            <span>{{ $pengaturan->pemesanan_dibuka ? 'Pelanggan bisa mengirim pesanan.' : 'Pelanggan tidak bisa mengirim pesanan baru.' }}</span>
        </div>
        <p class="muted">Pesanan yang sudah masuk tidak berubah dan tetap bisa Anda proses.</p>
        <form method="POST" action="{{ route('admin.pengaturan.pemesanan') }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn {{ $pengaturan->pemesanan_dibuka ? 'btn--netral' : 'btn--garis' }}">
                {{ $pengaturan->pemesanan_dibuka ? 'Tutup Pemesanan' : 'Buka Pemesanan' }}
            </button>
        </form>
    </section>

    <form method="POST" action="{{ route('admin.pengaturan.perbarui') }}" class="form-kartu form-sempit">
        @csrf @method('PUT')
        <h2>Info warung</h2>
        <p class="muted">Tampil di beranda dan footer. Nama resmi, alamat, dan kontak masih menunggu konfirmasi mitra.</p>

        <div class="form-grup">
            <label for="nama_resmi">Nama warung</label>
            <input type="text" id="nama_resmi" name="nama_resmi" value="{{ old('nama_resmi', $pengaturan->nama_resmi) }}" maxlength="120" required>
            @error('nama_resmi')<span class="galat-teks">{{ $message }}</span>@enderror
        </div>
        <div class="form-grup">
            <label for="alamat">Alamat</label>
            <input type="text" id="alamat" name="alamat" value="{{ old('alamat', $pengaturan->alamat) }}" maxlength="255">
            @error('alamat')<span class="galat-teks">{{ $message }}</span>@enderror
        </div>
        <div class="form-grup">
            <label for="kontak">Nomor kontak warung</label>
            <input type="text" id="kontak" name="kontak" value="{{ old('kontak', $pengaturan->kontak) }}" maxlength="40">
            <span class="bantu">Tampil publik. Isi hanya nomor yang boleh dilihat pelanggan.</span>
            @error('kontak')<span class="galat-teks">{{ $message }}</span>@enderror
        </div>
        <div class="form-grup">
            <label for="jam_buka">Jam buka</label>
            <input type="text" id="jam_buka" name="jam_buka" value="{{ old('jam_buka', $pengaturan->jam_buka) }}" maxlength="60">
            @error('jam_buka')<span class="galat-teks">{{ $message }}</span>@enderror
        </div>
        <div class="form-grup">
            <label for="info_pengambilan">Info pengambilan dan pembayaran</label>
            <textarea id="info_pengambilan" name="info_pengambilan" maxlength="1000">{{ old('info_pengambilan', $pengaturan->info_pengambilan) }}</textarea>
            @error('info_pengambilan')<span class="galat-teks">{{ $message }}</span>@enderror
        </div>
        <button type="submit" class="btn btn--utama btn--besar">Simpan Info Warung</button>
    </form>
@endsection
