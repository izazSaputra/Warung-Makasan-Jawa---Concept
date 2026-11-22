@extends('admin.layout')

@php $baru = ! $item->exists; @endphp
@section('judul', $baru ? 'Tambah Menu' : 'Ubah Menu')

@section('isi')
    <div class="admin__kepala">
        <h1>{{ $baru ? 'Tambah Menu' : 'Ubah Menu' }}</h1>
        @unless ($baru)
            <p class="muted">Perubahan harga hanya berlaku untuk pesanan baru. Pesanan yang sudah masuk tetap memakai harga saat dipesan.</p>
        @endunless
    </div>

    <form method="POST" action="{{ $baru ? route('admin.menu.simpan') : route('admin.menu.perbarui', $item) }}" class="form-kartu form-sempit">
        @csrf
        @unless ($baru) @method('PUT') @endunless

        <div class="form-grup">
            <label for="nama">Nama menu</label>
            <input type="text" id="nama" name="nama" value="{{ old('nama', $item->nama) }}" maxlength="120" required>
            @error('nama')<span class="galat-teks">{{ $message }}</span>@enderror
        </div>

        <div class="form-grup">
            <label for="jenis">Jenis</label>
            <select id="jenis" name="jenis" required>
                <option value="">Pilih jenis</option>
                @foreach ($daftarJenis as $j)
                    <option value="{{ $j->value }}" @selected(old('jenis', $item->jenis?->value) === $j->value)>{{ $j->label() }}</option>
                @endforeach
            </select>
            <span class="bantu">Jenis menentukan kombinasi di fitur Cari Makan Sesuai Budget.</span>
            @error('jenis')<span class="galat-teks">{{ $message }}</span>@enderror
        </div>

        <div class="form-grup">
            <label for="harga">Harga (Rp)</label>
            <input type="text" id="harga" name="harga" value="{{ old('harga', $item->harga) }}" inputmode="numeric" required>
            <span class="bantu">Angka saja, tanpa titik. Contoh: 12000</span>
            @error('harga')<span class="galat-teks">{{ $message }}</span>@enderror
        </div>

        <div class="form-grup">
            <label for="keterangan">Keterangan (opsional)</label>
            <input type="text" id="keterangan" name="keterangan" value="{{ old('keterangan', $item->keterangan) }}" maxlength="255">
            @error('keterangan')<span class="galat-teks">{{ $message }}</span>@enderror
        </div>

        @php $labelSekarang = old('label', $item->label[0] ?? ''); @endphp
        <fieldset class="form-grup">
            <legend>Label rasa</legend>
            <div class="pilihan-waktu pilihan-waktu--3">
                <label class="pilihan"><input type="radio" name="label" value="" @checked($labelSekarang === '')> Tanpa label</label>
                <label class="pilihan"><input type="radio" name="label" value="Pedas" @checked($labelSekarang === 'Pedas')> Pedas</label>
                <label class="pilihan"><input type="radio" name="label" value="Tidak pedas" @checked($labelSekarang === 'Tidak pedas')> Tidak pedas</label>
            </div>
            <span class="bantu">Isi hanya jika Anda yakin. Label tampil ke pelanggan dan dipakai sebagai penyaring.</span>
            @error('label')<span class="galat-teks">{{ $message }}</span>@enderror
        </fieldset>

        <fieldset class="form-grup">
            <legend>Status</legend>
            <label class="pilihan"><input type="checkbox" name="tersedia" value="1" @checked(old('tersedia', $baru ? true : $item->tersedia))> Tersedia (bisa dipesan)</label>
            <label class="pilihan"><input type="checkbox" name="aktif" value="1" @checked(old('aktif', $baru ? true : $item->aktif))> Aktif (tampil di menu)</label>
        </fieldset>

        <div class="aksi-baris">
            <button type="submit" class="btn btn--utama btn--besar">Simpan</button>
            <a href="{{ route('admin.menu') }}" class="btn btn--netral">Batal</a>
        </div>
    </form>
@endsection
