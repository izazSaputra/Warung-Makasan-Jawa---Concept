<?php

namespace Tests\Feature;

use App\Enums\StatusPesanan;
use App\Models\ItemMenu;
use App\Models\PengaturanWarung;
use App\Models\Pesanan;
use Database\Seeders\WarungSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PemesananTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WarungSeeder::class);
    }

    /** @param array<string,int> $daftar nama item => jumlah */
    private function keranjang(array $daftar): array
    {
        $data = [];
        foreach ($daftar as $nama => $jumlah) {
            $item = ItemMenu::where('nama', $nama)->firstOrFail();
            $data[$item->id] = ['jumlah' => $jumlah, 'catatan' => null, 'harga' => $item->harga];
        }

        return $data;
    }

    private function form(array $tambahan = []): array
    {
        return array_merge([
            'nama' => 'Budi Santoso',
            'kontak' => '0812-9876-5432',
            'waktu_pilihan' => 'sekarang',
            'catatan' => '',
        ], $tambahan);
    }

    private function buatPesanan(): Pesanan
    {
        return Pesanan::create([
            'kode_pesanan' => 'WMJ-ABC234',
            'kunci_idempoten' => 'kunci-pesanan-uji',
            'nama_pelanggan' => 'Budi',
            'kontak' => '081298765432',
            'waktu_ambil_diminta' => now(),
            'total_harga' => 5000,
            'status' => StatusPesanan::MenungguKonfirmasi,
        ]);
    }

    public function test_pesanan_tersimpan_dengan_total_dari_server_dan_snapshot(): void
    {
        $respon = $this->withSession([
            'keranjang' => $this->keranjang(['Nasi Putih' => 2, 'Telur Balado' => 1]),
            'kunci_checkout' => 'kunci-uji-1',
        ])->post(route('checkout.kirim'), $this->form());

        $pesanan = Pesanan::firstOrFail();
        $respon->assertRedirect(route('pesanan.status', $pesanan->kode_pesanan));
        $respon->assertSessionMissing('keranjang');

        $this->assertSame(13000, $pesanan->total_harga); // 2 x 4.000 + 5.000
        $this->assertSame(StatusPesanan::MenungguKonfirmasi, $pesanan->status);
        $this->assertSame('081298765432', $pesanan->kontak);
        $this->assertMatchesRegularExpression('/^WMJ-[A-Z2-9]{6}$/', $pesanan->kode_pesanan);
        $this->assertCount(2, $pesanan->items);
        $this->assertSame('Nasi Putih', $pesanan->items->first()->nama_snapshot);
        $this->assertSame(4000, $pesanan->items->first()->harga_snapshot);
        $this->assertCount(1, $pesanan->riwayatStatus);
    }

    public function test_kunci_sama_tidak_menggandakan_pesanan(): void
    {
        $sesi = [
            'keranjang' => $this->keranjang(['Nasi Putih' => 1]),
            'kunci_checkout' => 'kunci-uji-2',
        ];

        $this->withSession($sesi)->post(route('checkout.kirim'), $this->form());
        $this->withSession($sesi)->post(route('checkout.kirim'), $this->form());

        $this->assertSame(1, Pesanan::count());
    }

    public function test_pemesanan_ditutup_menolak_pesanan(): void
    {
        PengaturanWarung::first()->update(['pemesanan_dibuka' => false]);

        $respon = $this->withSession([
            'keranjang' => $this->keranjang(['Nasi Putih' => 1]),
            'kunci_checkout' => 'kunci-uji-3',
        ])->post(route('checkout.kirim'), $this->form());

        $respon->assertSessionHas('galat');
        $this->assertSame(0, Pesanan::count());
    }

    public function test_item_habis_menolak_pesanan_dan_keranjang_tetap_ada(): void
    {
        $respon = $this->withSession([
            'keranjang' => $this->keranjang(['Rawon Daging Sapi' => 1]), // item contoh yang habis
            'kunci_checkout' => 'kunci-uji-4',
        ])->post(route('checkout.kirim'), $this->form());

        $respon->assertSessionHas('galat');
        $respon->assertSessionHas('keranjang');
        $this->assertSame(0, Pesanan::count());
    }

    public function test_harga_berubah_menolak_dan_memperbarui_harga_dilihat(): void
    {
        $sesi = $this->keranjang(['Nasi Putih' => 1]);
        $item = ItemMenu::where('nama', 'Nasi Putih')->firstOrFail();
        $item->update(['harga' => 4500]); // harga berubah setelah pelanggan menambahkan ke keranjang

        $respon = $this->withSession([
            'keranjang' => $sesi,
            'kunci_checkout' => 'kunci-uji-5',
        ])->post(route('checkout.kirim'), $this->form());

        $respon->assertSessionHas('galat');
        $respon->assertSessionHas('keranjang', fn ($k) => $k[$item->id]['harga'] === 4500);
        $this->assertSame(0, Pesanan::count());
    }

    public function test_cek_pesanan_kontak_salah_dan_benar(): void
    {
        $this->buatPesanan();

        $this->post(route('cek-pesanan.proses'), ['kode' => 'WMJ-ABC234', 'kontak' => '0811111111'])
            ->assertSessionHas('galat', 'Kode atau nomor tidak cocok.');

        $this->post(route('cek-pesanan.proses'), ['kode' => 'wmj-abc234', 'kontak' => '+62 812-9876-5432'])
            ->assertRedirect(route('pesanan.status', 'WMJ-ABC234'));
    }

    public function test_status_pesanan_butuh_akses_sesi(): void
    {
        $this->buatPesanan();

        $this->get(route('pesanan.status', 'WMJ-ABC234'))
            ->assertRedirect(route('cek-pesanan'));

        $this->withSession(['akses_pesanan' => ['WMJ-ABC234']])
            ->get(route('pesanan.status', 'WMJ-ABC234'))
            ->assertOk()
            ->assertSee('WMJ-ABC234');
    }
}
