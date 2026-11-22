<aside class="panel" aria-label="Keranjang">
    <h2 class="panel__judul">
        Keranjang
        <span class="lencana lencana--aman">{{ $baris->sum('jumlah') }} item</span>
    </h2>

    @forelse ($baris as $b)
        <div class="baris">
            <div class="baris__info">
                <strong>{{ $b['item']->nama }}</strong>
                <span class="muted">{{ \App\Support\Format::rupiah($b['item']->harga) }} x {{ $b['jumlah'] }}</span>
                @unless ($b['item']->tersedia)
                    <span class="lencana lencana--habis">Habis, hapus dari keranjang</span>
                @endunless
            </div>
            <span class="baris__subtotal">{{ \App\Support\Format::rupiah($b['subtotal']) }}</span>
            <div class="baris__aksi">
                <form method="POST" action="{{ route('keranjang.ubah', $b['item']->id) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="jumlah" value="{{ $b['jumlah'] - 1 }}">
                    <button type="submit" class="btn-ikon" aria-label="Kurangi jumlah {{ $b['item']->nama }}">&minus;</button>
                </form>
                <span aria-label="Jumlah">{{ $b['jumlah'] }}</span>
                <form method="POST" action="{{ route('keranjang.ubah', $b['item']->id) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="jumlah" value="{{ $b['jumlah'] + 1 }}">
                    <button type="submit" class="btn-ikon" aria-label="Tambah jumlah {{ $b['item']->nama }}">+</button>
                </form>
                <form method="POST" action="{{ route('keranjang.hapus', $b['item']->id) }}" style="margin-left:auto">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn--garis">Hapus</button>
                </form>
            </div>
        </div>
    @empty
        <p class="muted">Keranjang masih kosong. Pilih menu lalu tekan Tambah.</p>
    @endforelse

    <div class="total">
        <span>Total perkiraan</span>
        <span class="total__nilai">{{ \App\Support\Format::rupiah($total) }}</span>
    </div>
    <p class="muted" style="font-size:14px">Total final dihitung ulang saat pesanan dikirim. Bayar saat mengambil pesanan di warung.</p>

    @if ($baris->isNotEmpty() && $dibuka)
        <a href="{{ route('checkout') }}" class="btn btn--utama btn--besar">Lanjut ke Checkout</a>
    @elseif (! $dibuka)
        <button type="button" class="btn btn--utama btn--besar" disabled>Pemesanan ditutup</button>
    @endif
</aside>
