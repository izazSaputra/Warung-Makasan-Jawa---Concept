@extends('admin.layout')

@section('judul', 'Kelola Menu')

@section('isi')
    @php use App\Support\Format; @endphp

    <div class="admin__kepala admin__kepala--baris">
        <div>
            <h1>Kelola Menu</h1>
            <p class="muted">Semua menu di bawah ini masih data contoh sampai mitra mengonfirmasi menu dan harga asli.</p>
        </div>
        <a href="{{ route('admin.menu.buat') }}" class="btn btn--utama">+ Tambah Menu</a>
    </div>

    <p class="bantu" style="margin-bottom:12px">
        <strong>Tersedia/Habis</strong>: menu habis tetap tampil tetapi tidak bisa dipesan.
        <strong>Aktif/Nonaktif</strong>: menu nonaktif disembunyikan dari pelanggan.
    </p>

    @if ($items->isEmpty())
        <div class="kosong"><h2>Belum ada menu</h2><a href="{{ route('admin.menu.buat') }}" class="btn btn--garis">Tambah Menu Pertama</a></div>
    @endif

    <div class="admin-daftar">
        @foreach ($items as $it)
            <article @class(['kartu-putih', 'menu-baris', 'menu-baris--nonaktif' => ! $it->aktif])>
                <div class="menu-baris__info">
                    <h2 style="font-size:18px">{{ $it->nama }}</h2>
                    <div class="muted" style="font-size:14px">
                        {{ $it->jenis->label() }}
                        @foreach (($it->label ?? []) as $lb) &middot; {{ $lb }} @endforeach
                    </div>
                    @if ($it->keterangan)<p class="muted" style="font-size:14px">{{ $it->keterangan }}</p>@endif
                </div>
                <div class="harga">{{ Format::rupiah($it->harga) }}</div>

                <div class="menu-baris__aksi">
                    <form method="POST" action="{{ route('admin.menu.tersedia', $it) }}">
                        @csrf @method('PATCH')
                        <button type="submit" @class(['saklar', 'saklar--hidup' => $it->tersedia]) aria-label="Ubah ketersediaan {{ $it->nama }}. Sekarang {{ $it->tersedia ? 'tersedia' : 'habis' }}">
                            <span aria-hidden="true">{{ $it->tersedia ? '✓' : '✕' }}</span> {{ $it->tersedia ? 'Tersedia' : 'Habis' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.menu.aktif', $it) }}">
                        @csrf @method('PATCH')
                        <button type="submit" @class(['saklar', 'saklar--hidup' => $it->aktif]) aria-label="Ubah status aktif {{ $it->nama }}. Sekarang {{ $it->aktif ? 'aktif' : 'nonaktif' }}">
                            <span aria-hidden="true">{{ $it->aktif ? '✓' : '✕' }}</span> {{ $it->aktif ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </form>
                    <a href="{{ route('admin.menu.ubah', $it) }}" class="btn btn--garis">Ubah</a>
                </div>
            </article>
        @endforeach
    </div>
@endsection
