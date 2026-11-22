@extends('admin.layout')

@section('judul', 'Kelola Pesanan')

@section('isi')
    @php
        use App\Enums\StatusPesanan as S;
        use App\Support\Format;

        // Tombol aksi per status tujuan. Urutan dan keabsahan tetap ditentukan StatusPesanan::transisiSah().
        $labelAksi = [
            'diterima' => 'Terima',
            'disiapkan' => 'Mulai Siapkan',
            'siap_diambil' => 'Tandai Siap Diambil',
            'selesai' => 'Tandai Selesai',
        ];
    @endphp

    <div class="admin__kepala">
        <h1>Kelola Pesanan</h1>
        <p class="muted">Hanya pesanan dari website. Pesan yang dibeli langsung di warung tidak tercatat di sini.</p>
    </div>

    <nav class="chip-baris" aria-label="Penyaring status">
        @foreach ($tab as $s)
            <a href="{{ route('admin.pesanan', ['status' => $s->value]) }}"
               @class(['chip', 'chip--aktif' => $s === $aktif])
               @if ($s === $aktif) aria-current="page" @endif>
                {{ $s->label() }} ({{ $hitung[$s->value] ?? 0 }})
            </a>
        @endforeach
    </nav>

    @if ($pesanan->isEmpty())
        <div class="kosong">
            <h2>Tidak ada pesanan "{{ $aktif->label() }}"</h2>
            <p class="muted">Pesanan baru dari website akan muncul di sini setelah halaman dimuat ulang.</p>
        </div>
    @endif

    @if ($totalStatus > $batas)
        <p class="bantu" style="margin-bottom:12px">Menampilkan {{ $batas }} pesanan teratas dari {{ $totalStatus }}.</p>
    @endif

    <div class="admin-daftar">
        @foreach ($pesanan as $p)
            <article class="kartu-putih pesanan-kartu" aria-label="Pesanan {{ $p->kode_pesanan }}">
                <header class="pesanan-kartu__atas">
                    <div>
                        <div class="kode-sedang">{{ $p->kode_pesanan }}</div>
                        <div class="muted" style="font-size:14px">Masuk {{ $p->created_at->format('d/m/Y H.i') }}</div>
                    </div>
                    @include('admin._badge', ['status' => $p->status])
                </header>

                <dl class="info-pesanan">
                    <div><dt>Pemesan</dt><dd>{{ $p->nama_pelanggan }}</dd></div>
                    <div><dt>Kontak</dt><dd><a href="tel:{{ $p->kontak }}">{{ $p->kontak }}</a></dd></div>
                    <div>
                        <dt>Waktu ambil {{ $p->status === S::MenungguKonfirmasi ? 'diminta' : '' }}</dt>
                        <dd>{{ $p->waktu_ambil_diminta->format('d/m/Y H.i') }}</dd>
                    </div>
                </dl>

                <div>
                    @foreach ($p->items as $it)
                        <div class="rincian-baris">
                            <span>
                                {{ $it->jumlah }}x {{ $it->nama_snapshot }}
                                @if ($it->catatan)<br><span class="muted" style="font-size:14px">Catatan: {{ $it->catatan }}</span>@endif
                            </span>
                            <strong>{{ Format::rupiah($it->subtotal()) }}</strong>
                        </div>
                    @endforeach
                    @if ($p->catatan)
                        <p class="kotak-catatan"><strong>Catatan pelanggan:</strong> {{ $p->catatan }}</p>
                    @endif
                    <div class="total" style="margin-top:12px">
                        <span>Total (bayar saat ambil)</span>
                        <span class="total__nilai">{{ Format::rupiah($p->total_harga) }}</span>
                    </div>
                </div>

                @if ($p->status === S::Ditolak && $p->alasan_penolakan)
                    <div class="kotak-alasan"><strong>Alasan penolakan:</strong> {{ $p->alasan_penolakan }}</div>
                @endif

                @unless ($p->status->final())
                    <div class="aksi-baris">
                        @foreach ($p->status->transisiSah() as $tujuan)
                            @if ($tujuan === S::Ditolak)
                                <details class="tolak">
                                    <summary class="btn btn--garis">Tolak</summary>
                                    <form method="POST" action="{{ route('admin.pesanan.status', $p) }}" class="tolak__form">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ S::Ditolak->value }}">
                                        <input type="hidden" name="dari" value="{{ $p->status->value }}">
                                        <label for="alasan-{{ $p->id }}"><strong>Alasan penolakan (wajib)</strong></label>
                                        <input type="text" id="alasan-{{ $p->id }}" name="alasan" maxlength="255" required placeholder="Contoh: Ayam goreng sudah habis">
                                        <p class="bantu">Alasan ini akan terlihat oleh pelanggan.</p>
                                        <button type="submit" class="btn btn--netral">Kirim Penolakan</button>
                                    </form>
                                </details>
                            @else
                                <form method="POST" action="{{ route('admin.pesanan.status', $p) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $tujuan->value }}">
                                    <input type="hidden" name="dari" value="{{ $p->status->value }}">
                                    <button type="submit" class="btn btn--utama">{{ $labelAksi[$tujuan->value] ?? $tujuan->label() }}</button>
                                </form>
                            @endif
                        @endforeach
                    </div>
                @endunless
            </article>
        @endforeach
    </div>
@endsection
