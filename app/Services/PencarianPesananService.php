<?php

namespace App\Services;

use App\Models\Pesanan;
use App\Support\Format;
use Illuminate\Support\Collection;

/**
 * Bantuan untuk pelanggan yang lupa kode pesanan (FR-07, versi terbatas).
 * Nomor kontak dan nama harus cocok, dan hanya pesanan 24 jam terakhir yang dicari.
 * Hasilnya hanya kode dan status, tanpa rincian item, waktu ambil, atau data lain.
 */
class PencarianPesananService
{
    public const RENTANG_JAM = 24;

    public const MAKS_HASIL = 5;

    /**
     * @return Collection<int, Pesanan>
     */
    public function cariLewatKontakDanNama(string $kontak, string $nama): Collection
    {
        $nomor = Format::nomor($kontak);
        $namaDicari = $this->samakanNama($nama);

        if ($nomor === '' || $namaDicari === '') {
            return collect();
        }

        return Pesanan::query()
            ->where('kontak', $nomor)
            ->where('created_at', '>=', now()->subHours(self::RENTANG_JAM))
            ->orderByDesc('id')
            ->get(['id', 'kode_pesanan', 'nama_pelanggan', 'status', 'created_at'])
            ->filter(fn (Pesanan $pesanan): bool => hash_equals($this->samakanNama($pesanan->nama_pelanggan), $namaDicari))
            ->take(self::MAKS_HASIL)
            ->values();
    }

    /** "  budi   SANTOSO " menjadi "budi santoso". */
    private function samakanNama(string $nama): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $nama)));
    }
}
