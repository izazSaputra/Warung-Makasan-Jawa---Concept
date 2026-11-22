@extends('layouts.app')

@section('judul', 'Cari Makan Sesuai Budget')

@section('isi')
    @php
        use App\Enums\JenisHasil;
        use App\Support\Format;

        $dibuka = $warung->pemesanan_dibuka;
        $penjelasan = [
            'makanan_lengkap' => 'Nasi, lauk, dan sayur, atau satu paket.',
            'lauk_saja' => 'Lauk saja. Cocok bila nasi sudah ada di rumah.',
            'termasuk_minuman' => 'Makanan lengkap ditambah satu minuman.',
        ];
        $rumus = fn (array $items) => collect($items)->map(fn ($i) => $i->nama.' '.Format::rupiah($i->harga))->implode(' + ');
    @endphp
    <link rel="stylesheet" href="{{ asset('css/budget.css') }}">

    @unless ($dibuka)
        <div class="banner banner--tutup">
            <strong>Pemesanan sedang ditutup.</strong>
            <span>Kamu tetap bisa mencari kombinasi, tetapi belum bisa memasukkannya ke keranjang.</span>
        </div>
    @endunless

    <h1 style="margin-bottom:8px">Cari Makan Sesuai Budget</h1>
    <p class="muted" style="margin-bottom:20px">Masukkan nominal uangmu. Kami susun kombinasi menu yang tersedia dengan total tidak melebihi budget.</p>

    <form method="GET" action="{{ route('budget') }}" class="form-kartu budget-form" novalidate>
        <div class="form-grup">
            <label for="budget">Budget maksimal (Rp)</label>
            <input type="text" id="budget" name="budget" value="{{ $nilaiInput }}" inputmode="numeric" autocomplete="off" placeholder="Contoh: 15000" aria-describedby="bantu-budget{{ $galat ? ' galat-budget' : '' }}" @if ($galat) aria-invalid="true" @endif>
            <span class="bantu" id="bantu-budget">Angka saja. 15000 dan 15.000 sama saja.</span>
            @if ($galat)<span class="galat-teks" id="galat-budget" role="alert">{{ $galat }}</span>@endif
            <div class="chip-baris" aria-label="Pilih nominal cepat">
                @foreach ($cepat as $n)
                    <a href="{{ route('budget', array_filter(['budget' => $n, 'jenis' => $jenis->value, 'label' => $label])) }}"
                       @class(['chip', 'chip--aktif' => $hasil && $hasil['budget'] === $n])>{{ Format::rupiah($n) }}</a>
                @endforeach
            </div>
        </div>

        <fieldset class="form-grup">
            <legend>Mau cari apa?</legend>
            <div class="pilihan-kolom">
                @foreach ($daftarJenis as $j)
                    <label class="pilihan pilihan--tinggi">
                        <input type="radio" name="jenis" value="{{ $j->value }}" @checked($jenis === $j)>
                        <span><strong>{{ $j->label() }}</strong><br><span class="muted" style="font-size:14px;font-weight:400">{{ $penjelasan[$j->value] }}</span></span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="form-grup">
            <legend>Preferensi rasa (boleh dikosongkan)</legend>
            <div class="pilihan-baris">
                <label class="pilihan"><input type="radio" name="label" value="" @checked($label === null)> Tanpa preferensi</label>
                <label class="pilihan"><input type="radio" name="label" value="Pedas" @checked($label === 'Pedas')> Pedas</label>
                <label class="pilihan"><input type="radio" name="label" value="Tidak pedas" @checked($label === 'Tidak pedas')> Tidak pedas</label>
            </div>
            <span class="bantu">Berlaku untuk lauk dan paket. Hanya menu yang tersedia saat ini yang dipakai.</span>
        </fieldset>

        <button type="submit" class="btn btn--utama btn--besar">Cari</button>
    </form>

    @if ($hasil)
        <section class="hasil-bagian" aria-label="Hasil pencarian" aria-live="polite">
            @if ($hasil['belum_dikonfirmasi'])
                <div class="notif notif--peringatan" role="note">
                    Susunan kombinasi dan menu di sini masih contoh dan belum dikonfirmasi warung. Hasil bisa berubah setelah menu dan harga resmi ditetapkan.
                </div>
            @endif

            @if (count($hasil['kombinasi']) > 0)
                <h2>Pilihan untuk budget {{ Format::rupiah($hasil['budget']) }}</h2>
                <p class="muted" style="margin-bottom:16px">
                    {{ $hasil['jenis']->label() }}@if ($hasil['label']), {{ strtolower($hasil['label']) }}@endif.
                    Diurutkan dari yang paling mendekati budget.
                    @if ($hasil['jumlah_ditemukan'] > count($hasil['kombinasi']))
                        Menampilkan {{ count($hasil['kombinasi']) }} dari {{ $hasil['jumlah_ditemukan'] }} kombinasi.
                    @endif
                </p>

                <div class="hasil-daftar">
                    @foreach ($hasil['kombinasi'] as $i => $k)
                        <article class="kartu-putih hasil" aria-label="Pilihan {{ $i + 1 }}">
                            <header class="hasil__atas">
                                <h3>Pilihan {{ $i + 1 }}</h3>
                                <span class="pil hasil__sisa">Sisa {{ Format::rupiah($k['sisa']) }}</span>
                            </header>

                            <ul class="hasil__daftar">
                                @foreach ($k['items'] as $it)
                                    <li>
                                        <span>{{ $it->nama }}<small class="muted"> &middot; {{ $it->jenis->label() }}</small></span>
                                        <strong>{{ Format::rupiah($it->harga) }}</strong>
                                    </li>
                                @endforeach
                            </ul>

                            <p class="hasil__rumus muted">{{ $rumus($k['items']) }} = {{ Format::rupiah($k['total']) }}</p>
                            @if ($hasil['jenis'] === JenisHasil::LaukSaja)
                                <p class="bantu">Harga ini hanya untuk lauk, belum termasuk nasi.</p>
                            @endif

                            <div class="total">
                                <span>Total</span>
                                <span class="total__nilai">{{ Format::rupiah($k['total']) }}</span>
                            </div>

                            @if ($dibuka)
                                <form method="POST" action="{{ route('budget.keranjang') }}">
                                    @csrf
                                    <input type="hidden" name="budget" value="{{ $hasil['budget'] }}">
                                    @foreach ($k['items'] as $it)
                                        <input type="hidden" name="item_ids[]" value="{{ $it->id }}">
                                    @endforeach
                                    <button type="submit" class="btn btn--garis">Masukkan ke Keranjang</button>
                                </form>
                            @else
                                <button type="button" class="btn btn--netral" disabled>Pemesanan ditutup</button>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <div class="kosong" role="status">
                    @if ($hasil['alasan_kosong'] === 'budget_kurang')
                        <h2>Budget belum cukup</h2>
                        <p>Untuk {{ strtolower($hasil['jenis']->label()) }}@if ($hasil['label']) ({{ strtolower($hasil['label']) }})@endif, pilihan termurah yang tersedia:</p>
                        <p class="hasil__rumus"><strong>{{ $rumus($hasil['termurah']['items']) }} = {{ Format::rupiah($hasil['termurah']['total']) }}</strong></p>
                        <p class="muted">Naikkan budget minimal menjadi {{ Format::rupiah($hasil['termurah']['total']) }}.</p>
                        <a class="btn btn--garis" href="{{ route('budget', array_filter(['budget' => $hasil['termurah']['total'], 'jenis' => $hasil['jenis']->value, 'label' => $hasil['label']])) }}">Coba {{ Format::rupiah($hasil['termurah']['total']) }}</a>
                    @elseif ($hasil['alasan_kosong'] === 'preferensi')
                        <h2>Belum ada yang cocok dengan preferensimu</h2>
                        <p>Tidak ada menu tersedia yang berlabel "{{ $hasil['label'] }}" untuk pilihan ini. Coba tanpa preferensi rasa.</p>
                        <a class="btn btn--garis" href="{{ route('budget', ['budget' => $hasil['budget'], 'jenis' => $hasil['jenis']->value]) }}">Cari Tanpa Preferensi</a>
                    @else
                        <h2>Belum ada menu yang cocok</h2>
                        <p>Saat ini belum ada menu tersedia untuk pilihan "{{ $hasil['jenis']->label() }}". Coba jenis lain atau lihat daftar menu.</p>
                        <a class="btn btn--garis" href="{{ route('menu') }}">Lihat Menu</a>
                    @endif
                </div>
            @endif

            <p class="bantu" style="margin-top:16px">Harga bisa berubah sampai pesanan dikirim. Total final dihitung ulang saat checkout.</p>
        </section>
    @endif
@endsection
