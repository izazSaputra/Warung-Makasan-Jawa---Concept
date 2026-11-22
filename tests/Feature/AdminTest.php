<?php

namespace Tests\Feature;

use App\Enums\StatusPesanan;
use App\Models\ItemMenu;
use App\Models\PengaturanWarung;
use App\Models\Pesanan;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\WarungSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WarungSeeder::class);
        $this->admin = User::where('email', 'admin@warung.test')->firstOrFail();
    }

    private function pesanan(StatusPesanan $status = StatusPesanan::MenungguKonfirmasi, string $kode = 'WMJ-ABC234'): Pesanan
    {
        $p = Pesanan::create([
            'kode_pesanan' => $kode,
            'kunci_idempoten' => 'kunci-'.$kode,
            'nama_pelanggan' => 'Budi',
            'kontak' => '081298765432',
            'waktu_ambil_diminta' => now()->addHour(),
            'total_harga' => 13000,
            'status' => $status,
        ]);
        $p->items()->create(['nama_snapshot' => 'Nasi Putih', 'harga_snapshot' => 4000, 'jumlah' => 2]);
        $p->items()->create(['nama_snapshot' => 'Telur Balado', 'harga_snapshot' => 5000, 'jumlah' => 1]);
        $p->riwayatStatus()->create(['status' => $status, 'waktu' => now()]);

        return $p;
    }

    private function ubah(Pesanan $p, StatusPesanan $ke, array $tambahan = [])
    {
        return $this->actingAs($this->admin)->post(route('admin.pesanan.status', $p), array_merge([
            'status' => $ke->value,
            'dari' => $p->status->value,
        ], $tambahan));
    }

    // ---- Akses ----

    public function test_tamu_diarahkan_ke_halaman_masuk_untuk_semua_halaman_admin(): void
    {
        $p = $this->pesanan();
        $item = ItemMenu::first();

        foreach ([
            fn () => $this->get(route('admin.pesanan')),
            fn () => $this->get(route('admin.menu')),
            fn () => $this->get(route('admin.pengaturan')),
            fn () => $this->get(route('admin.ringkasan')),
            fn () => $this->post(route('admin.pesanan.status', $p), ['status' => 'diterima']),
            fn () => $this->patch(route('admin.menu.tersedia', $item)),
            fn () => $this->patch(route('admin.pengaturan.pemesanan')),
        ] as $aksi) {
            $aksi()->assertRedirect(route('login'));
        }

        $this->assertSame(StatusPesanan::MenungguKonfirmasi, $p->fresh()->status);
    }

    public function test_login_benar_dan_salah(): void
    {
        $this->post(route('admin.masuk.proses'), ['email' => 'admin@warung.test', 'password' => 'salah'])
            ->assertSessionHas('galat', 'Email atau kata sandi salah.');
        $this->assertGuest();

        $this->post(route('admin.masuk.proses'), ['email' => 'admin@warung.test', 'password' => 'password'])
            ->assertRedirect(route('admin.pesanan'));
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_keluar_mengakhiri_sesi(): void
    {
        $this->actingAs($this->admin)->post(route('admin.keluar'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // ---- Kelola Pesanan ----

    public function test_alur_penuh_sampai_selesai_mencatat_riwayat(): void
    {
        $p = $this->pesanan();

        foreach ([StatusPesanan::Diterima, StatusPesanan::Disiapkan, StatusPesanan::SiapDiambil, StatusPesanan::Selesai] as $ke) {
            $this->ubah($p->fresh(), $ke)->assertSessionHas('pesan');
        }

        $p->refresh();
        $this->assertSame(StatusPesanan::Selesai, $p->status);
        $this->assertCount(5, $p->riwayatStatus); // menunggu + 4 perubahan
    }

    public function test_tolak_wajib_beralasan_dan_alasan_tersimpan(): void
    {
        $p = $this->pesanan();

        $this->ubah($p, StatusPesanan::Ditolak, ['alasan' => ''])->assertSessionHas('galat');
        $this->assertSame(StatusPesanan::MenungguKonfirmasi, $p->fresh()->status);

        $this->ubah($p, StatusPesanan::Ditolak, ['alasan' => 'Ayam goreng habis'])->assertSessionHas('pesan');
        $p->refresh();
        $this->assertSame(StatusPesanan::Ditolak, $p->status);
        $this->assertSame('Ayam goreng habis', $p->alasan_penolakan);
    }

    public function test_status_loncat_tahap_ditolak(): void
    {
        $p = $this->pesanan();

        $this->ubah($p, StatusPesanan::Selesai)->assertSessionHas('galat');
        $this->ubah($p, StatusPesanan::Disiapkan)->assertSessionHas('galat');

        $p->refresh();
        $this->assertSame(StatusPesanan::MenungguKonfirmasi, $p->status);
        $this->assertCount(1, $p->riwayatStatus);
    }

    public function test_status_final_tidak_bisa_diubah(): void
    {
        $selesai = $this->pesanan(StatusPesanan::Selesai, 'WMJ-AAA222');
        $ditolak = $this->pesanan(StatusPesanan::Ditolak, 'WMJ-BBB333');

        $this->ubah($selesai, StatusPesanan::SiapDiambil)->assertSessionHas('galat');
        $this->ubah($ditolak, StatusPesanan::Diterima)->assertSessionHas('galat');

        $this->assertSame(StatusPesanan::Selesai, $selesai->fresh()->status);
        $this->assertSame(StatusPesanan::Ditolak, $ditolak->fresh()->status);
    }

    public function test_aksi_basi_ditolak_bila_status_sudah_berubah(): void
    {
        $p = $this->pesanan();
        $this->ubah($p, StatusPesanan::Diterima); // pengelola A

        // Pengelola B masih melihat "menunggu" dan menekan Tolak.
        $this->actingAs($this->admin)->post(route('admin.pesanan.status', $p), [
            'status' => 'ditolak', 'dari' => 'menunggu_konfirmasi', 'alasan' => 'Habis',
        ])->assertSessionHas('galat');

        $this->assertSame(StatusPesanan::Diterima, $p->fresh()->status);
    }

    public function test_status_tidak_dikenal_ditolak_validasi(): void
    {
        $p = $this->pesanan();

        $this->actingAs($this->admin)->post(route('admin.pesanan.status', $p), ['status' => 'dibatalkan'])
            ->assertSessionHasErrors('status');
        $this->assertSame(StatusPesanan::MenungguKonfirmasi, $p->fresh()->status);
    }

    public function test_daftar_pesanan_menampilkan_kontak_hanya_untuk_pengelola(): void
    {
        $this->pesanan();

        $this->actingAs($this->admin)->get(route('admin.pesanan'))
            ->assertOk()->assertSee('WMJ-ABC234')->assertSee('081298765432')->assertSee('Terima');
    }

    // ---- Kelola Menu ----

    public function test_saklar_habis_membuat_item_tidak_bisa_dipesan(): void
    {
        $item = ItemMenu::where('nama', 'Nasi Putih')->firstOrFail();
        $this->assertTrue($item->dapatDipesan());

        $this->actingAs($this->admin)->patch(route('admin.menu.tersedia', $item))->assertSessionHas('pesan');
        $this->assertFalse($item->fresh()->dapatDipesan());

        $this->patch(route('admin.menu.tersedia', $item));
        $this->assertTrue($item->fresh()->dapatDipesan());
    }

    public function test_item_nonaktif_hilang_dari_menu_pelanggan(): void
    {
        $item = ItemMenu::where('nama', 'Es Teh Melati')->firstOrFail();

        $this->actingAs($this->admin)->patch(route('admin.menu.aktif', $item));
        auth()->logout();
        $this->assertFalse($item->fresh()->aktif);

        // Pelanggan memakai sesi sendiri, jadi pesan flash milik pengelola tidak ikut terbawa.
        $this->flushSession();

        $this->get(route('menu'))->assertOk()->assertDontSee('Es Teh Melati');
    }

    public function test_tambah_dan_ubah_menu(): void
    {
        $this->actingAs($this->admin)->post(route('admin.menu.simpan'), [
            'nama' => 'Tempe Goreng', 'jenis' => 'lauk', 'harga' => '3000',
            'keterangan' => 'Tempe goreng tepung', 'label' => 'Tidak pedas', 'aktif' => '1', 'tersedia' => '1',
        ])->assertRedirect(route('admin.menu'));

        $item = ItemMenu::where('nama', 'Tempe Goreng')->firstOrFail();
        $this->assertSame(3000, $item->harga);
        $this->assertSame(['Tidak pedas'], $item->label);
        $this->assertTrue($item->aktif);

        $this->put(route('admin.menu.perbarui', $item), [
            'nama' => 'Tempe Goreng', 'jenis' => 'lauk', 'harga' => '3500', 'label' => '', 'aktif' => '1',
        ])->assertRedirect(route('admin.menu'));

        $item->refresh();
        $this->assertSame(3500, $item->harga);
        $this->assertSame([], $item->label);
        $this->assertFalse($item->tersedia); // kotak tidak dicentang
    }

    public function test_validasi_menu(): void
    {
        $this->actingAs($this->admin);

        foreach ([
            ['nama' => '', 'jenis' => 'lauk', 'harga' => '3000'],
            ['nama' => 'X', 'jenis' => 'tidakada', 'harga' => '3000'],
            ['nama' => 'X', 'jenis' => 'lauk', 'harga' => '-5'],
            ['nama' => 'X', 'jenis' => 'lauk', 'harga' => '0'],
            ['nama' => 'X', 'jenis' => 'lauk', 'harga' => '12.500'],
            ['nama' => 'Nasi Putih', 'jenis' => 'nasi', 'harga' => '4000'], // nama ganda
        ] as $data) {
            $this->post(route('admin.menu.simpan'), $data)->assertSessionHasErrors();
        }

        $this->assertSame(0, ItemMenu::where('nama', 'X')->count());
    }

    public function test_perubahan_harga_tidak_mengubah_snapshot_pesanan_lama(): void
    {
        $p = $this->pesanan();
        $item = ItemMenu::where('nama', 'Nasi Putih')->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.menu.perbarui', $item), [
            'nama' => 'Nasi Putih', 'jenis' => 'nasi', 'harga' => '9000', 'aktif' => '1', 'tersedia' => '1',
        ]);

        $this->assertSame(4000, $p->items()->where('nama_snapshot', 'Nasi Putih')->first()->harga_snapshot);
    }

    // ---- Pengaturan ----

    public function test_saklar_buka_tutup_pemesanan_mengubah_pengaturan(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.pengaturan.pemesanan'));
        $this->assertFalse(PengaturanWarung::first()->pemesanan_dibuka);

        $this->patch(route('admin.pengaturan.pemesanan'));
        $this->assertTrue(PengaturanWarung::first()->pemesanan_dibuka);
    }

    public function test_simpan_info_warung(): void
    {
        $this->actingAs($this->admin)->put(route('admin.pengaturan.perbarui'), [
            'nama_resmi' => 'Warung Contoh', 'alamat' => 'Jl. Contoh 1', 'kontak' => '', 'jam_buka' => '07.00 - 21.00', 'info_pengambilan' => 'Bayar saat ambil.',
        ])->assertSessionHas('pesan');

        $this->assertSame('Warung Contoh', PengaturanWarung::first()->nama_resmi);

        $this->put(route('admin.pengaturan.perbarui'), ['nama_resmi' => ''])->assertSessionHasErrors('nama_resmi');
    }

    // ---- Ringkasan ----

    public function test_ringkasan_cocok_dengan_pesanan_selesai(): void
    {
        $a = $this->pesanan(StatusPesanan::Selesai, 'WMJ-AAA222');
        $b = $this->pesanan(StatusPesanan::Selesai, 'WMJ-BBB333');
        $this->pesanan(StatusPesanan::Ditolak, 'WMJ-CCC444');
        $this->pesanan(StatusPesanan::Diterima, 'WMJ-DDD555');
        foreach ([$a, $b] as $p) {
            $p->riwayatStatus()->create(['status' => StatusPesanan::Selesai, 'waktu' => now()]);
        }

        $hari = now()->format('Y-m-d');
        $respon = $this->actingAs($this->admin)->get(route('admin.ringkasan', ['dari' => $hari, 'sampai' => $hari]));

        $respon->assertOk();
        $hasil = $respon->viewData('hasil');
        $this->assertSame(2, $hasil['selesai']);
        $this->assertSame('Nasi Putih', $hasil['item'][0]->nama);
        $this->assertSame(4, $hasil['item'][0]->jumlah); // 2 pesanan x 2 porsi
        $this->assertSame(2, $hasil['item'][1]->jumlah);
    }

    public function test_ringkasan_memakai_tanggal_wib(): void
    {
        // 01.00 WIB masih 18.00 UTC hari sebelumnya. Pesanan ini harus terhitung di tanggal WIB.
        $this->travelTo(Carbon::parse('2026-11-24 01:00', 'Asia/Jakarta'));
        $pesanan = $this->pesanan(StatusPesanan::Selesai, 'WMJ-WIB222');
        $pesanan->riwayatStatus()->create(['status' => StatusPesanan::Selesai, 'waktu' => now()]);

        $respon = $this->actingAs($this->admin)->get(route('admin.ringkasan', ['dari' => '2026-11-24', 'sampai' => '2026-11-24']));

        $this->assertSame(1, $respon->viewData('hasil')['selesai']);
    }

    public function test_ringkasan_rentang_kosong_dan_tidak_valid(): void
    {
        $this->actingAs($this->admin);

        $respon = $this->get(route('admin.ringkasan', ['dari' => '2020-01-01', 'sampai' => '2020-01-31']));
        $this->assertSame(0, $respon->viewData('hasil')['selesai']);

        $this->get(route('admin.ringkasan', ['dari' => '2026-11-10', 'sampai' => '2026-11-01']))
            ->assertSessionHasErrors('sampai');
    }
}
