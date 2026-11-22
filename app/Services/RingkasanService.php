<?php

namespace App\Services;

use App\Enums\StatusPesanan;
use App\Models\ItemPesanan;
use App\Models\RiwayatStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Ringkasan sederhana (FR-08): jumlah pesanan selesai dan item terlaris dari pesanan website.
 * Pesanan dihitung pada tanggal ia ditandai Selesai (dari riwayat_status), bukan tanggal dibuat.
 */
class RingkasanService
{
    /**
     * @return array{selesai:int, item:Collection<int, object>}
     */
    public function hitung(CarbonInterface $dari, CarbonInterface $sampai, int $batasItem = 10): array
    {
        $idSelesai = RiwayatStatus::query()
            ->where('status', StatusPesanan::Selesai->value)
            ->whereBetween('waktu', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
            ->pluck('pesanan_id')
            ->unique();

        $item = ItemPesanan::query()
            ->selectRaw('nama_snapshot as nama, SUM(jumlah) as jumlah')
            ->whereIn('pesanan_id', $idSelesai)
            ->groupBy('nama_snapshot')
            ->orderByDesc('jumlah')
            ->orderBy('nama_snapshot')
            ->limit($batasItem)
            ->get()
            ->map(fn ($r) => (object) ['nama' => $r->nama, 'jumlah' => (int) $r->jumlah]);

        return ['selesai' => $idSelesai->count(), 'item' => $item];
    }
}
