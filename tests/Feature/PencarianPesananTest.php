<?php

namespace Tests\Feature;

use App\Enums\StatusPesanan;
use App\Models\Pesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PencarianPesananTest extends TestCase
{
    use RefreshDatabase;

    private function buatPesanan(string $kode, string $nama, string $kontak = '081298765432'): Pesanan
    {
        return Pesanan::create([
            'kode_pesanan' => $kode,
            'kunci_idempoten' => 'kunci-'.$kode,
            'nama_pelanggan' => $nama,
            'kontak' => $kontak,
            'waktu_ambil_diminta' => now(),
            'total_harga' => 5000,
            'status' => StatusPesanan::Diterima,
        ]);
    }

    public function test_nama_dan_kontak_cocok_menampilkan_kode_dan_status(): void
    {
        $this->buatPesanan('WMJ-ABC234', 'Budi Santoso');

        $this->post(route('cek-pesanan.lupa-kode'), [
            'lupa_nama' => '  budi   SANTOSO ',
            'lupa_kontak' => '+62 812-9876-5432',
        ])->assertRedirect(route('cek-pesanan').'#lupa-kode');

        $this->get(route('cek-pesanan'))
            ->assertOk()
            ->assertSee('WMJ-ABC234')
            ->assertSee(StatusPesanan::Diterima->label())
            ->assertDontSee('081298765432');
    }

    public function test_nama_salah_tidak_membuka_kode(): void
    {
        $this->buatPesanan('WMJ-ABC234', 'Budi Santoso');

        $this->post(route('cek-pesanan.lupa-kode'), [
            'lupa_nama' => 'Siti',
            'lupa_kontak' => '081298765432',
        ])->assertSessionHas('galat_lupa')->assertSessionMissing('hasil_lupa');

        $this->get(route('cek-pesanan'))->assertDontSee('WMJ-ABC234');
    }

    public function test_pesanan_orang_lain_dengan_nomor_sama_tidak_ikut_tampil(): void
    {
        $this->buatPesanan('WMJ-ABC234', 'Budi Santoso');
        $this->buatPesanan('WMJ-XYZ789', 'Siti Aminah');

        $this->post(route('cek-pesanan.lupa-kode'), [
            'lupa_nama' => 'Budi Santoso',
            'lupa_kontak' => '081298765432',
        ]);

        $this->get(route('cek-pesanan'))->assertSee('WMJ-ABC234')->assertDontSee('WMJ-XYZ789');
    }

    public function test_pesanan_lebih_dari_24_jam_tidak_dicari(): void
    {
        $this->travel(-25)->hours();
        $this->buatPesanan('WMJ-LAMA22', 'Budi Santoso');
        $this->travelBack();

        $this->post(route('cek-pesanan.lupa-kode'), [
            'lupa_nama' => 'Budi Santoso',
            'lupa_kontak' => '081298765432',
        ])->assertSessionHas('galat_lupa')->assertSessionMissing('hasil_lupa');
    }

    public function test_hasil_tidak_memberi_akses_ke_rincian_pesanan(): void
    {
        $this->buatPesanan('WMJ-ABC234', 'Budi Santoso');

        $this->post(route('cek-pesanan.lupa-kode'), [
            'lupa_nama' => 'Budi Santoso',
            'lupa_kontak' => '081298765432',
        ]);

        $this->get(route('pesanan.status', 'WMJ-ABC234'))->assertRedirect(route('cek-pesanan'));
    }

    public function test_nama_dan_kontak_wajib_diisi(): void
    {
        $this->post(route('cek-pesanan.lupa-kode'), [])
            ->assertSessionHasErrors(['lupa_nama', 'lupa_kontak']);
    }
}
