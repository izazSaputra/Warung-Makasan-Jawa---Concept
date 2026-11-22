@extends('layouts.app')

@section('judul', 'Checkout')

@section('isi')
    @php
        $dibuka = $warung->pemesanan_dibuka;
        $adaHabis = $baris->contains(fn ($b) => ! $b['item']->tersedia);
        $bisaKirim = $dibuka && $baris->isNotEmpty() && ! $adaHabis;
    @endphp

    <h1 style="margin-bottom:16px">Checkout</h1>

    @unless ($dibuka)
        <div class="banner banner--tutup">
            <strong>Pemesanan sedang ditutup.</strong>
            <span>Pesanan baru belum bisa dikirim.</span>
        </div>
    @endunless

    @if ($baris->isEmpty())
        <div class="kosong">
            <p>Keranjang masih kosong. Pilih menu terlebih dahulu.</p>
            <a href="{{ route('menu') }}" class="btn btn--utama">Lihat Menu</a>
        </div>
    @else
        <div class="notif notif--peringatan" role="note">Pesanan belum pasti sampai dikonfirmasi oleh warung.</div>

        <div class="checkout-tata">
            <section aria-label="Data pemesan">
                <form id="form-checkout" method="POST" action="{{ route('checkout.kirim') }}" class="form-kartu" novalidate>
                    @csrf

                    <div class="form-grup">
                        <label for="nama">Nama pemesan <span aria-hidden="true">*</span></label>
                        <input id="nama" name="nama" type="text" value="{{ old('nama') }}" maxlength="100" autocomplete="name" required>
                        @error('nama') <p class="galat-teks">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-grup">
                        <label for="kontak">Nomor kontak <span aria-hidden="true">*</span></label>
                        <input id="kontak" name="kontak" type="tel" inputmode="tel" value="{{ old('kontak') }}" placeholder="Contoh: 0812-3456-7890" autocomplete="tel" required>
                        <p class="bantu">Dipakai untuk mengecek status pesanan.</p>
                        @error('kontak') <p class="galat-teks">{{ $message }}</p> @enderror
                    </div>

                    <fieldset class="form-grup">
                        <legend>Waktu ambil yang diminta <span aria-hidden="true">*</span></legend>
                        <div class="pilihan-waktu">
                            @foreach (['sekarang' => 'Sekarang', '30' => '30 menit lagi', '60' => '1 jam lagi', 'jam' => 'Pilih jam'] as $nilai => $teks)
                                <label class="pilihan">
                                    <input type="radio" name="waktu_pilihan" value="{{ $nilai }}" @checked(old('waktu_pilihan', 'sekarang') === (string) $nilai)>
                                    <span>{{ $teks }}</span>
                                </label>
                            @endforeach
                        </div>
                        <label for="jam_ambil" class="bantu">Jam ambil (isi jika memilih "Pilih jam")</label>
                        <input id="jam_ambil" name="jam_ambil" type="time" value="{{ old('jam_ambil') }}">
                        @error('waktu_pilihan') <p class="galat-teks">{{ $message }}</p> @enderror
                        @error('jam_ambil') <p class="galat-teks">{{ $message }}</p> @enderror
                        <p class="bantu">Waktu ini hanya permintaan. Baru disepakati setelah warung menerima pesanan.</p>
                    </fieldset>

                    <div class="form-grup">
                        <label for="catatan">Catatan untuk dapur <span class="bantu">(opsional)</span></label>
                        <textarea id="catatan" name="catatan" maxlength="500" placeholder="Contoh: sambal dipisah">{{ old('catatan') }}</textarea>
                        @error('catatan') <p class="galat-teks">{{ $message }}</p> @enderror
                    </div>
                </form>
            </section>

            <aside class="panel" aria-label="Ringkasan pesanan">
                <h2 class="panel__judul">
                    Ringkasan Pesanan
                    <span class="lencana lencana--aman">{{ $baris->sum('jumlah') }} item</span>
                </h2>

                @foreach ($baris as $b)
                    @php $item = $b['item']; @endphp
                    <div class="ringkas-baris">
                        <div class="ringkas-baris__atas">
                            <strong>{{ $item->nama }}</strong>
                            <strong>{{ \App\Support\Format::rupiah($b['subtotal']) }}</strong>
                        </div>
                        <span class="muted">{{ \App\Support\Format::rupiah($item->harga) }} x {{ $b['jumlah'] }}</span>

                        @unless ($item->tersedia)
                            <span class="lencana lencana--habis">Habis, hapus dari keranjang</span>
                        @endunless
                        @if ($b['harga_berubah'])
                            <span class="peringatan-harga">Harga berubah: dari {{ \App\Support\Format::rupiah($b['harga_dilihat']) }} menjadi {{ \App\Support\Format::rupiah($item->harga) }}</span>
                        @endif

                        <div class="ringkas-baris__aksi">
                            <form method="POST" action="{{ route('keranjang.ubah', $item->id) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="jumlah" value="{{ $b['jumlah'] - 1 }}">
                                <button type="submit" class="btn-ikon" aria-label="Kurangi jumlah {{ $item->nama }}">&minus;</button>
                            </form>
                            <span aria-label="Jumlah">{{ $b['jumlah'] }}</span>
                            <form method="POST" action="{{ route('keranjang.ubah', $item->id) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="jumlah" value="{{ $b['jumlah'] + 1 }}">
                                <button type="submit" class="btn-ikon" aria-label="Tambah jumlah {{ $item->nama }}">+</button>
                            </form>
                            <form method="POST" action="{{ route('keranjang.hapus', $item->id) }}" style="margin-left:auto">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn--garis">Hapus</button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('keranjang.catatan', $item->id) }}" class="catatan-form">
                            @csrf @method('PATCH')
                            <label class="sr-only" for="cat-{{ $item->id }}">Catatan untuk {{ $item->nama }}</label>
                            <input id="cat-{{ $item->id }}" type="text" name="catatan" value="{{ $b['catatan'] }}" maxlength="255" placeholder="Catatan item (opsional)">
                            <button type="submit" class="btn btn--netral">Simpan</button>
                        </form>
                    </div>
                @endforeach

                <div class="total">
                    <span>Total</span>
                    <span class="total__nilai">{{ \App\Support\Format::rupiah($total) }}</span>
                </div>
                <p class="muted" style="font-size:14px">Ambil di warung. Bayar saat mengambil pesanan. Total final dihitung ulang saat pesanan dikirim.</p>

                @if ($bisaKirim)
                    <button type="submit" form="form-checkout" class="btn btn--utama btn--besar">Kirim Pesanan</button>
                @else
                    <button type="button" class="btn btn--utama btn--besar" disabled>
                        {{ $dibuka ? 'Hapus item yang habis' : 'Pemesanan ditutup' }}
                    </button>
                @endif
            </aside>
        </div>
    @endif
@endsection
