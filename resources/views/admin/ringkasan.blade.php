@extends('admin.layout')

@section('judul', 'Ringkasan')

@section('isi')
    <div class="admin__kepala">
        <h1>Ringkasan</h1>
        <p class="muted">Hanya pesanan website yang berstatus Selesai. Penjualan langsung di warung tidak tercatat.</p>
    </div>

    <form method="GET" action="{{ route('admin.ringkasan') }}" class="kartu-putih filter-tanggal">
        <div class="form-grup">
            <label for="dari">Dari tanggal</label>
            <input type="date" id="dari" name="dari" value="{{ $dari->format('Y-m-d') }}" required>
        </div>
        <div class="form-grup">
            <label for="sampai">Sampai tanggal</label>
            <input type="date" id="sampai" name="sampai" value="{{ $sampai->format('Y-m-d') }}" required>
        </div>
        <button type="submit" class="btn btn--utama">Tampilkan</button>
    </form>

    <div class="ringkasan-tata">
        <section class="kartu-putih" aria-label="Pesanan selesai">
            <div class="muted" style="font-weight:600">Pesanan selesai</div>
            <div class="angka-besar">{{ $hasil['selesai'] }}</div>
            <p class="bantu">{{ $dari->format('d/m/Y') }} sampai {{ $sampai->format('d/m/Y') }}, dihitung dari tanggal pesanan ditandai Selesai.</p>
            <a href="{{ route('admin.pesanan', ['status' => 'selesai']) }}" class="btn btn--garis">Lihat Daftar Pesanan Selesai</a>
        </section>

        <section class="kartu-putih" aria-label="Item terlaris">
            <h2>Item paling sering dipesan</h2>
            @if ($hasil['item']->isEmpty())
                <p class="muted">Belum ada pesanan selesai pada rentang tanggal ini.</p>
            @else
                <ol class="terlaris">
                    @foreach ($hasil['item'] as $i => $it)
                        <li>
                            <span class="terlaris__no">{{ $i + 1 }}</span>
                            <span class="terlaris__nama">{{ $it->nama }}</span>
                            <strong>{{ $it->jumlah }} porsi</strong>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
@endsection
