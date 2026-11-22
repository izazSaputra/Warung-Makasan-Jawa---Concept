<?php

namespace App\Services;

use App\Enums\StatusPesanan;
use App\Exceptions\PesananGagal;
use App\Models\Pesanan;
use App\Support\Format;
use App\Support\Warung;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Alur kirim pesanan (UC-05) beserta validasi harga dan ketersediaan (UC-14).
 * Controller tidak boleh menghitung total atau menyimpan pesanan sendiri.
 */
class PemesananService
{
    public function __construct(private KeranjangService $keranjang)
    {
    }

    /**
     * @param  array{nama:string, kontak:string, waktu_pilihan:string, jam_ambil?:?string, catatan?:?string}  $data
     *
     * @throws PesananGagal
     */
    public function kirim(array $data, string $kunci): Pesanan
    {
        // Tombol kirim ditekan dua kali: kembalikan pesanan yang sama.
        if ($ada = Pesanan::query()->where('kunci_idempoten', $kunci)->first()) {
            return $ada;
        }

        if (! Warung::pengaturan()->pemesanan_dibuka) {
            throw new PesananGagal('Pemesanan sedang ditutup. Pesanan belum dibuat.');
        }

        $baris = $this->keranjang->baris();
        if ($baris->isEmpty()) {
            throw new PesananGagal('Keranjang masih kosong. Pilih menu terlebih dahulu.');
        }

        $this->validasiItem($baris);
        $waktuAmbil = $this->hitungWaktuAmbil($data);

        try {
            $pesanan = DB::transaction(function () use ($data, $kunci, $baris, $waktuAmbil) {
                // Total dihitung dari harga katalog di database, bukan dari peramban.
                $total = (int) $baris->sum(fn ($b) => $b['item']->harga * $b['jumlah']);

                $pesanan = Pesanan::create([
                    'kode_pesanan' => $this->buatKode(),
                    'kunci_idempoten' => $kunci,
                    'nama_pelanggan' => trim($data['nama']),
                    'kontak' => Format::nomor($data['kontak']),
                    'catatan' => ($data['catatan'] ?? null) ?: null,
                    'waktu_ambil_diminta' => $waktuAmbil,
                    'total_harga' => $total,
                    'status' => StatusPesanan::MenungguKonfirmasi,
                ]);

                foreach ($baris as $b) {
                    $pesanan->items()->create([
                        'item_menu_id' => $b['item']->id,
                        'nama_snapshot' => $b['item']->nama,
                        'harga_snapshot' => $b['item']->harga,
                        'jumlah' => $b['jumlah'],
                        'catatan' => $b['catatan'],
                    ]);
                }

                $pesanan->riwayatStatus()->create([
                    'status' => StatusPesanan::MenungguKonfirmasi,
                    'waktu' => now(),
                ]);

                return $pesanan;
            });
        } catch (UniqueConstraintViolationException $e) {
            // Dua permintaan dengan kunci yang sama masuk bersamaan.
            $ada = Pesanan::query()->where('kunci_idempoten', $kunci)->first();
            if ($ada) {
                return $ada;
            }
            report($e);
            throw new PesananGagal('Pesanan belum tersimpan. Isi keranjang Anda aman, silakan coba lagi.');
        } catch (Throwable $e) {
            report($e);
            // Jangan mengaku berhasil dan jangan kosongkan keranjang (SKPL-NF-04).
            throw new PesananGagal('Pesanan belum tersimpan. Isi keranjang Anda aman, silakan coba lagi.');
        }

        $this->keranjang->kosongkan();

        return $pesanan;
    }

    /**
     * Item habis atau berubah harga: pesanan tidak dibuat dan pelanggan diminta meninjau.
     *
     * @param  Collection<int, array<string, mixed>>  $baris
     */
    private function validasiItem(Collection $baris): void
    {
        $habis = [];
        $berubah = [];

        foreach ($baris as $b) {
            $item = $b['item'];

            if (! $item->dapatDipesan()) {
                $habis[] = $item->nama;
            } elseif ($b['harga_berubah']) {
                $berubah[] = sprintf(
                    '%s (dari %s menjadi %s)',
                    $item->nama,
                    Format::rupiah($b['harga_dilihat']),
                    Format::rupiah($item->harga)
                );
            }
        }

        $pesan = [];
        if ($habis) {
            $pesan[] = implode(', ', $habis).' sedang habis. Hapus dari keranjang, lalu kirim ulang.';
        }
        if ($berubah) {
            // Anggap sudah dilihat: pelanggan meninjau total baru lalu menekan kirim lagi.
            $this->keranjang->perbaruiHargaDilihat();
            $pesan[] = 'Harga berubah: '.implode(', ', $berubah).'. Periksa total terbaru, lalu kirim ulang.';
        }

        if ($pesan) {
            throw new PesananGagal(implode(' ', $pesan));
        }
    }

    private function hitungWaktuAmbil(array $data): Carbon
    {
        $pilihan = $data['waktu_pilihan'] ?? 'sekarang';

        if ($pilihan === 'jam') {
            $jam = $data['jam_ambil'] ?? null;
            if (! $jam) {
                throw new PesananGagal('Isi jam ambil yang diinginkan.');
            }

            $waktu = Carbon::createFromFormat('H:i', $jam)->setDate(now()->year, now()->month, now()->day);
            if ($waktu->lt(now()->subMinute())) {
                throw new PesananGagal('Jam ambil sudah lewat. Pilih jam yang masih akan datang.');
            }

            return $waktu;
        }

        return match ($pilihan) {
            '30' => now()->addMinutes(30),
            '60' => now()->addHour(),
            default => now(),
        };
    }

    /** Kode acak, sulit ditebak, contoh WMJ-7K3Q9F (tanpa 0/O dan 1/I agar mudah dibaca). */
    private function buatKode(): string
    {
        $abjad = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $kode = 'WMJ-';
            for ($i = 0; $i < 6; $i++) {
                $kode .= $abjad[random_int(0, strlen($abjad) - 1)];
            }
        } while (Pesanan::query()->where('kode_pesanan', $kode)->exists());

        return $kode;
    }
}
