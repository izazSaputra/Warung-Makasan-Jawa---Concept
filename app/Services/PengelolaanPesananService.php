<?php

namespace App\Services;

use App\Enums\StatusPesanan;
use App\Exceptions\PesananGagal;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;

/**
 * Perubahan status pesanan oleh pengelola (UC-08, UC-09).
 * Aturan transisi ada di StatusPesanan::transisiSah(); controller tidak boleh mengubah status sendiri.
 */
class PengelolaanPesananService
{
    /**
     * @param  StatusPesanan|null  $dariStatus  Status yang dilihat pengelola saat menekan tombol.
     *                                          Bila sudah berubah (misal dua orang membuka halaman), aksi ditolak.
     *
     * @throws PesananGagal
     */
    public function ubahStatus(int $pesananId, StatusPesanan $baru, ?string $alasan = null, ?StatusPesanan $dariStatus = null): Pesanan
    {
        $alasan = $alasan === null ? null : trim($alasan);

        if ($baru === StatusPesanan::Ditolak && ($alasan === null || mb_strlen($alasan) < 3)) {
            throw new PesananGagal('Alasan penolakan wajib diisi agar pelanggan tahu sebabnya.');
        }

        return DB::transaction(function () use ($pesananId, $baru, $alasan, $dariStatus) {
            $pesanan = Pesanan::query()->lockForUpdate()->findOrFail($pesananId);

            if ($dariStatus !== null && $pesanan->status !== $dariStatus) {
                throw new PesananGagal(sprintf(
                    'Status pesanan %s sudah berubah menjadi "%s". Muat ulang halaman lalu coba lagi.',
                    $pesanan->kode_pesanan,
                    $pesanan->status->label()
                ));
            }

            if (! $pesanan->bolehUbahStatus($baru)) {
                throw new PesananGagal(sprintf(
                    'Pesanan %s berstatus "%s" dan tidak bisa diubah ke "%s".',
                    $pesanan->kode_pesanan,
                    $pesanan->status->label(),
                    $baru->label()
                ));
            }

            $pesanan->status = $baru;
            if ($baru === StatusPesanan::Ditolak) {
                $pesanan->alasan_penolakan = $alasan;
            }
            $pesanan->save();

            $pesanan->riwayatStatus()->create(['status' => $baru, 'waktu' => now()]);

            return $pesanan;
        });
    }
}
