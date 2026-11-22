<article class="kartu {{ $item->tersedia ? '' : 'kartu--habis' }}">
    <div class="kartu__foto">
        @if ($item->foto)
            <img src="{{ asset('storage/'.$item->foto) }}" alt="{{ $item->nama }}" loading="lazy">
        @else
            @include('partials.ikon', ['nama' => 'piring'])
        @endif
        <span class="kartu__jenis">{{ $item->jenis->label() }}</span>
    </div>

    <h3>{{ $item->nama }}</h3>
    <p class="kartu__ket">{{ $item->keterangan }}</p>

    <div class="lencana-baris">
        @foreach (($item->label ?? []) as $lb)
            <span class="lencana {{ $lb === 'Pedas' ? 'lencana--pedas' : 'lencana--aman' }}">{{ $lb }}</span>
        @endforeach
        @unless ($item->tersedia)
            <span class="lencana lencana--habis">Habis</span>
        @endunless
    </div>

    <div class="kartu__bawah">
        <span class="harga">{{ \App\Support\Format::rupiah($item->harga) }}</span>
        @if ($item->tersedia && $dibuka)
            <form method="POST" action="{{ route('keranjang.tambah') }}">
                @csrf
                <input type="hidden" name="item_id" value="{{ $item->id }}">
                <button type="submit" class="btn btn--netral">+ Tambah</button>
            </form>
        @else
            <button type="button" class="btn btn--netral" disabled>{{ $item->tersedia ? 'Tutup' : 'Habis' }}</button>
        @endif
    </div>
</article>
