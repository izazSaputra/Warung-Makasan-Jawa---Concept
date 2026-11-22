<?php

namespace Tests\Feature;

use App\Enums\JenisHasil;
use App\Models\AturanKombinasi;
use App\Models\ItemMenu;
use App\Models\PengaturanWarung;
use App\Services\BudgetService;
use App\Services\KeranjangService;
use Database\Seeders\WarungSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Angka di tes ini mengikuti data contoh WarungSeeder. Jika data contoh diganti,
 * sesuaikan angkanya. Termurah "makanan lengkap" = Nasi 4.000 + Telur Bacem 4.500 + Sayur Bening 4.000 = 12.500.
 */
class BudgetTest extends TestCase
{
    use RefreshDatabase;

    private BudgetService $layanan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WarungSeeder::class);
        $this->layanan = app(BudgetService::class);
    }

    private function item(string $nama): ItemMenu
    {
        return ItemMenu::where('nama', $nama)->firstOrFail();
    }

    private function totals(array $hasil): array
    {
        return array_map(fn ($k) => $k['total'], $hasil['kombinasi']);
    }

    // ---- Aturan inti: total benar dan tidak melewati budget ----

    public function test_total_sama_dengan_jumlah_item_dan_tidak_melewati_budget(): void
    {
        foreach ([JenisHasil::MakananLengkap, JenisHasil::LaukSaja, JenisHasil::TermasukMinuman] as $jenis) {
            foreach ([5000, 12500, 15000, 20000, 30000, 100000] as $budget) {
                $hasil = $this->layanan->cari($budget, $jenis);

                foreach ($hasil['kombinasi'] as $k) {
                    $jumlah = array_sum(array_map(fn ($i) => $i->harga, $k['items']));
                    $this->assertSame($jumlah, $k['total'], "{$jenis->value} {$budget}");
                    $this->assertLessThanOrEqual($budget, $k['total']);
                    $this->assertSame($budget - $k['total'], $k['sisa']);
                }
            }
        }
    }

    public function test_makanan_lengkap_selalu_berisi_nasi_lauk_sayur_atau_paket(): void
    {
        $hasil = $this->layanan->cari(30000, JenisHasil::MakananLengkap);
        $this->assertNotEmpty($hasil['kombinasi']);

        foreach ($hasil['kombinasi'] as $k) {
            $jenis = array_map(fn ($i) => $i->jenis->value, $k['items']);
            sort($jenis);
            $this->assertContains($jenis, [['paket'], ['lauk', 'nasi', 'sayur']]);
        }
    }

    public function test_urutan_dari_yang_paling_mendekati_budget(): void
    {
        $totals = $this->totals($this->layanan->cari(30000, JenisHasil::MakananLengkap));
        $urut = $totals;
        rsort($urut);

        $this->assertSame($urut, $totals);
        $this->assertCount(BudgetService::BATAS_HASIL, $totals);
    }

    public function test_hasil_bervariasi_satu_lauk_tidak_menguasai_daftar(): void
    {
        $hasil = $this->layanan->cari(30000, JenisHasil::MakananLengkap);
        $hitung = [];
        foreach ($hasil['kombinasi'] as $k) {
            foreach ($k['items'] as $i) {
                if (in_array($i->jenis->value, ['paket', 'lauk'], true)) {
                    $hitung[$i->id] = ($hitung[$i->id] ?? 0) + 1;
                }
            }
        }

        $this->assertLessThanOrEqual(BudgetService::MAKS_PER_UTAMA, max($hitung));
    }

    // ---- Nilai batas (Tabel 14 SKPL) ----

    public function test_batas_tepat_di_harga_kombinasi_termurah(): void
    {
        $kurang = $this->layanan->cari(12499, JenisHasil::MakananLengkap);
        $this->assertSame([], $kurang['kombinasi']);
        $this->assertSame('budget_kurang', $kurang['alasan_kosong']);
        $this->assertSame(12500, $kurang['termurah']['total']);

        $pas = $this->layanan->cari(12500, JenisHasil::MakananLengkap);
        $this->assertSame([12500], $this->totals($pas));
        $this->assertSame(0, $pas['kombinasi'][0]['sisa']);

        $lebih = $this->layanan->cari(12501, JenisHasil::MakananLengkap);
        $this->assertSame([12500], $this->totals($lebih));
        $this->assertSame(1, $lebih['kombinasi'][0]['sisa']);
    }

    public function test_batas_paket_tepat_h_min_1_h_h_plus_1(): void
    {
        $paket = fn (int $b) => collect($this->layanan->cari($b, JenisHasil::MakananLengkap)['kombinasi'])
            ->contains(fn ($k) => $k['items'][0]->jenis->value === 'paket');

        $this->assertFalse($paket(17999));
        $this->assertTrue($paket(18000));
        $this->assertTrue($paket(18001));
    }

    public function test_item_termurah_habis_menaikkan_batas(): void
    {
        $this->item('Telur Bacem')->update(['tersedia' => false]);

        $hasil = $this->layanan->cari(12500, JenisHasil::MakananLengkap);
        $this->assertSame([], $hasil['kombinasi']);
        $this->assertSame(13000, $hasil['termurah']['total']); // nasi + balado + bening

        $this->assertSame([13000], $this->totals($this->layanan->cari(13000, JenisHasil::MakananLengkap)));
    }

    public function test_budget_terlalu_kecil_menjelaskan_pilihan_termurah_lengkap_dengan_item(): void
    {
        $hasil = $this->layanan->cari(1000, JenisHasil::MakananLengkap);

        $this->assertSame('budget_kurang', $hasil['alasan_kosong']);
        $this->assertCount(3, $hasil['termurah']['items']);
        $this->assertSame(12500, array_sum(array_map(fn ($i) => $i->harga, $hasil['termurah']['items'])));
    }

    // ---- Jenis hasil ----

    public function test_lauk_saja_tidak_memuat_nasi_dan_tidak_memuat_item_habis(): void
    {
        $hasil = $this->layanan->cari(100000, JenisHasil::LaukSaja);

        foreach ($hasil['kombinasi'] as $k) {
            $this->assertCount(1, $k['items']);
            $this->assertSame('lauk', $k['items'][0]->jenis->value);
            $this->assertNotSame('Rawon Daging Sapi', $k['items'][0]->nama); // habis
        }

        $this->assertSame([5000, 4500], $this->totals($this->layanan->cari(5000, JenisHasil::LaukSaja)));
    }

    public function test_termasuk_minuman_selalu_ada_minuman(): void
    {
        $hasil = $this->layanan->cari(30000, JenisHasil::TermasukMinuman);
        $this->assertNotEmpty($hasil['kombinasi']);
        foreach ($hasil['kombinasi'] as $k) {
            $this->assertTrue(collect($k['items'])->contains(fn ($i) => $i->jenis->value === 'minuman'));
        }

        $this->assertSame([], $this->layanan->cari(15999, JenisHasil::TermasukMinuman)['kombinasi']);
        $this->assertSame([16000], $this->totals($this->layanan->cari(16000, JenisHasil::TermasukMinuman)));
    }

    // ---- Preferensi rasa ----

    public function test_preferensi_pedas_hanya_menyaring_lauk(): void
    {
        $hasil = $this->layanan->cari(20000, JenisHasil::LaukSaja, 'Pedas');
        $this->assertSame(['Mangut Lele Asap', 'Telur Balado'], array_map(fn ($k) => $k['items'][0]->nama, $hasil['kombinasi']));

        $tidak = $this->layanan->cari(20000, JenisHasil::LaukSaja, 'Tidak pedas');
        $nama = array_map(fn ($k) => $k['items'][0]->nama, $tidak['kombinasi']);
        $this->assertEqualsCanonicalizing(['Ayam Goreng Lengkuas', 'Telur Bacem'], $nama);
    }

    public function test_preferensi_menaikkan_batas_budget_dan_dilaporkan_apa_adanya(): void
    {
        $hasil = $this->layanan->cari(12500, JenisHasil::MakananLengkap, 'Pedas');

        $this->assertSame('budget_kurang', $hasil['alasan_kosong']);
        $this->assertSame(13000, $hasil['termurah']['total']);
    }

    public function test_preferensi_tanpa_hasil_padahal_budget_cukup(): void
    {
        $this->item('Telur Balado')->update(['aktif' => false]);
        $this->item('Mangut Lele Asap')->update(['aktif' => false]);

        $hasil = $this->layanan->cari(30000, JenisHasil::MakananLengkap, 'Pedas');

        $this->assertSame([], $hasil['kombinasi']);
        $this->assertSame('preferensi', $hasil['alasan_kosong']);
    }

    public function test_tidak_ada_menu_sama_sekali_untuk_jenis_itu(): void
    {
        ItemMenu::whereIn('jenis', ['paket', 'nasi'])->update(['aktif' => false]);

        $hasil = $this->layanan->cari(50000, JenisHasil::MakananLengkap);

        $this->assertSame('tidak_ada_menu', $hasil['alasan_kosong']);
        $this->assertNull($hasil['termurah']);
    }

    public function test_status_belum_dikonfirmasi_mitra_dilaporkan(): void
    {
        $this->assertTrue($this->layanan->cari(15000, JenisHasil::MakananLengkap)['belum_dikonfirmasi']);

        AturanKombinasi::query()->update(['dikonfirmasi_mitra' => true]);
        $this->assertFalse($this->layanan->cari(15000, JenisHasil::MakananLengkap)['belum_dikonfirmasi']);
    }

    // ---- Halaman: validasi nominal ----

    public function test_halaman_tanpa_input_tidak_menampilkan_hasil(): void
    {
        $this->get(route('budget'))->assertOk()->assertDontSee('Pilihan 1');
    }

    public function test_nominal_tidak_valid_ditolak_dengan_pesan_jelas(): void
    {
        foreach ([
            '' => 'wajib diisi',
            '0' => 'lebih dari Rp0',
            '-5000' => 'lebih dari Rp0',
            'abc' => 'angka bulat',
            '15,5' => 'angka bulat',
            '15.5' => 'angka bulat',
            '99999999999' => 'terlalu besar',
            '1000001' => 'terlalu besar',
        ] as $input => $pesan) {
            $respon = $this->get(route('budget', ['budget' => $input]));
            $respon->assertOk()->assertSee($pesan)->assertDontSee('Pilihan 1');
            $this->assertNull($respon->viewData('hasil'), "input: {$input}");
        }
    }

    public function test_nominal_dengan_titik_ribuan_dan_rp_diterima(): void
    {
        foreach (['15000', '15.000', 'Rp15.000', 'rp 15.000', ' 15000 '] as $input) {
            $respon = $this->get(route('budget', ['budget' => $input]));
            $respon->assertOk();
            $this->assertSame(15000, $respon->viewData('hasil')['budget'], "input: {$input}");
        }
    }

    public function test_halaman_menampilkan_rincian_dan_total(): void
    {
        $this->get(route('budget', ['budget' => 12500, 'jenis' => 'makanan_lengkap']))
            ->assertOk()
            ->assertSee('Pilihan 1')
            ->assertSee('Nasi Putih Rp4.000 + Telur Bacem Rp4.500 + Sayur Bening Rp4.000 = Rp12.500', false)
            ->assertSee('Masukkan ke Keranjang');
    }

    public function test_halaman_kosong_menjelaskan_pilihan_termurah(): void
    {
        $this->get(route('budget', ['budget' => 5000, 'jenis' => 'makanan_lengkap']))
            ->assertOk()
            ->assertSee('Budget belum cukup')
            ->assertSee('Rp12.500')
            ->assertDontSee('Masukkan ke Keranjang');
    }

    public function test_saat_pemesanan_ditutup_tombol_keranjang_dinonaktifkan(): void
    {
        PengaturanWarung::query()->update(['pemesanan_dibuka' => false]);

        $this->get(route('budget', ['budget' => 15000]))
            ->assertOk()
            ->assertSee('Pemesanan ditutup')
            ->assertDontSee('Masukkan ke Keranjang');
    }

    // ---- Masuk keranjang: total keranjang harus sama dengan rincian ----

    private function idKombinasi(): array
    {
        return [$this->item('Nasi Putih')->id, $this->item('Telur Bacem')->id, $this->item('Sayur Bening')->id];
    }

    public function test_masuk_keranjang_total_sama_dengan_rincian(): void
    {
        $this->post(route('budget.keranjang'), ['budget' => 12500, 'item_ids' => $this->idKombinasi()])
            ->assertRedirect(route('checkout'))
            ->assertSessionHas('pesan');

        $keranjang = app(KeranjangService::class);
        $this->assertSame(3, $keranjang->jumlahItem());
        $this->assertSame(12500, $keranjang->total());
    }

    public function test_masuk_keranjang_ditolak_bila_harga_berubah_melewati_budget(): void
    {
        $this->item('Telur Bacem')->update(['harga' => 6000]); // total jadi 14.000

        $this->post(route('budget.keranjang'), ['budget' => 12500, 'item_ids' => $this->idKombinasi()])
            ->assertSessionHas('galat');

        $this->assertSame(0, app(KeranjangService::class)->jumlahItem());
    }

    public function test_masuk_keranjang_ditolak_bila_ada_item_habis_dan_tidak_ada_yang_masuk_sebagian(): void
    {
        $this->item('Sayur Bening')->update(['tersedia' => false]);

        $this->post(route('budget.keranjang'), ['budget' => 12500, 'item_ids' => $this->idKombinasi()])
            ->assertSessionHas('galat');

        $this->assertSame(0, app(KeranjangService::class)->jumlahItem());
    }

    public function test_masuk_keranjang_ditolak_saat_pemesanan_ditutup(): void
    {
        PengaturanWarung::query()->update(['pemesanan_dibuka' => false]);

        $this->post(route('budget.keranjang'), ['budget' => 12500, 'item_ids' => $this->idKombinasi()])
            ->assertSessionHas('galat');

        $this->assertSame(0, app(KeranjangService::class)->jumlahItem());
    }

    public function test_masuk_keranjang_validasi_input(): void
    {
        $this->post(route('budget.keranjang'), ['budget' => 0, 'item_ids' => $this->idKombinasi()])->assertSessionHasErrors('budget');
        $this->post(route('budget.keranjang'), ['budget' => 12500])->assertSessionHasErrors('item_ids');
        $this->post(route('budget.keranjang'), ['budget' => 12500, 'item_ids' => ['abc']])->assertSessionHasErrors('item_ids.0');
    }
}
