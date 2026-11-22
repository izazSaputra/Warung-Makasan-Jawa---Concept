@extends('layouts.app')

@section('judul', 'Status Pesanan '.$pesanan->kode_pesanan)

@section('isi')
    @php
        $s = $pesanan->status->value;
        $ikon = ['menunggu_konfirmasi' => '&#9203;', 'diterima' => '&#10003;', 'disiapkan' => '&#9832;', 'siap_diambil' => '&#9873;', 'selesai' => '&#10003;', 'ditolak' => '&#10005;'][$s];
        $penjelasan = [
            'menunggu_konfirmasi' => 'Pesanan terkirim. Belum pasti sampai dikonfirmasi warung.',
            'diterima' => 'Warung menerima pesananmu. Waktu ambil kini disepakati.',
            'disiapkan' => 'Pesanan sedang disiapkan.',
            'siap_diambil' => 'Pesanan siap diambil di warung.',
            'selesai' => 'Pesanan selesai. Terima kasih.',
            'ditolak' => 'Pesanan ditolak oleh warung.',
        ][$s];
        $ketTahap = [
            'menunggu_konfirmasi' => 'Pesanan masuk dan menunggu diperiksa warung.',
            'diterima' => 'Warung menyetujui pesanan.',
            'disiapkan' => 'Pesanan sedang disiapkan.',
            'siap_diambil' => 'Pesanan bisa diambil di warung.',
            'selesai' => 'Pesanan diserahkan.',
            'ditolak' => 'Pesanan tidak dapat dipenuhi.',
        ];
    @endphp

    <h1 style="margin-bottom:16px">Status Pesanan</h1>

    <div class="dua-kolom">
        <section class="kartu-putih" aria-label="Status">
            <div>
                <div class="muted" style="font-size:14px;font-weight:600">Kode pesanan</div>
                <div class="kode-besar">{{ $pesanan->kode_pesanan }}</div>
            </div>

            <div>
                <span class="status-badge status--{{ $s }}"><span aria-hidden="true">{!! $ikon !!}</span> {{ $pesanan->status->label() }}</span>
                <p style="margin-top:8px">{{ $penjelasan }}</p>
            </div>

            @if ($pesanan->status === \App\Enums\StatusPesanan::Ditolak && $pesanan->alasan_penolakan)
                <div class="kotak-alasan"><strong>Alasan penolakan:</strong> {{ $pesanan->alasan_penolakan }}</div>
                <a href="{{ route('menu') }}" class="btn btn--garis">Pilih Menu Lain</a>
            @endif

            <ol class="tahap-daftar" aria-label="Alur pesanan">
                @foreach ($tahap as $t)
                    <li class="tahap tahap--{{ $t['kondisi'] }}">
                        <span class="tahap__tanda" aria-hidden="true">{{ $t['kondisi'] === 'selesai' ? '✓' : ($t['kondisi'] === 'sekarang' ? '●' : '') }}</span>
                        <div>
                            <div class="tahap__nama">{{ $t['status']->label() }}</div>
                            <div class="tahap__ket">{{ $ketTahap[$t['status']->value] }}</div>
                        </div>
                        <div class="tahap__ket">
                            @if ($t['kondisi'] === 'sekarang') <strong>Sekarang</strong>
                            @elseif ($t['kondisi'] === 'selesai') Selesai
                            @else Belum
                            @endif
                            @if ($t['waktu'])<br>{{ $t['waktu']->format('H.i') }}@endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        <aside class="kartu-putih" aria-label="Rincian pesanan">
            <h2>Rincian Pesanan</h2>
            <div class="rincian-baris"><span class="muted">Pemesan</span><strong>{{ $pesanan->nama_pelanggan }}</strong></div>
            <div class="rincian-baris">
                <span class="muted">Waktu ambil {{ $pesanan->status === \App\Enums\StatusPesanan::MenungguKonfirmasi ? 'diminta' : '' }}</span>
                <strong>{{ $pesanan->waktu_ambil_diminta->format('d/m/Y H.i') }}</strong>
            </div>
            @if ($pesanan->status === \App\Enums\StatusPesanan::MenungguKonfirmasi)
                <p class="bantu">Waktu ini masih permintaan sampai pesanan diterima warung.</p>
            @endif

            @foreach ($pesanan->items as $it)
                <div class="rincian-baris">
                    <span>
                        {{ $it->jumlah }}x {{ $it->nama_snapshot }}
                        @if ($it->catatan)<br><span class="muted" style="font-size:14px">Catatan: {{ $it->catatan }}</span>@endif
                    </span>
                    <strong>{{ \App\Support\Format::rupiah($it->subtotal()) }}</strong>
                </div>
            @endforeach

            @if ($pesanan->catatan)
                <p class="muted"><strong>Catatan untuk dapur:</strong> {{ $pesanan->catatan }}</p>
            @endif

            <div class="total">
                <span>Total</span>
                <span class="total__nilai">{{ \App\Support\Format::rupiah($pesanan->total_harga) }}</span>
            </div>
            <p class="muted" style="font-size:14px">Ambil di warung. Bayar saat mengambil pesanan.</p>
            <p>Butuh bantuan? Hubungi warung: <strong>{{ $warung->kontak ?: 'Nomor kontak warung' }}</strong></p>
        </aside>
    </div>
@endsection
