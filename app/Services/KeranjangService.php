<?php

namespace App\Services;

use App\Models\ItemMenu;
use App\Support\Warung;
use Illuminate\Support\Collection;

/**
 * Keranjang disimpan di sesi:
 * [id_item => ['jumlah' => int, 'catatan' => ?string, 'harga' => int]].
 * 'harga' adalah harga yang terakhir dilihat pelanggan, dipakai untuk mendeteksi
 * perubahan harga sebelum pesanan dikirim (UC-14).
 * Total di sini hanya perkiraan tampilan. Total final dihitung ulang server.
 */
class KeranjangService
{
    public const MAKS_JUMLAH = 20;

    private function data(): array
    {
        return session('keranjang', []);
    }

    private function simpan(array $data): void
    {
        session(['keranjang' => $data]);
    }

    /** Tambah item. Mengembalikan false jika item tidak boleh dipesan. */
    public function tambah(int $itemId, int $jumlah = 1): bool
    {
        if (! Warung::pengaturan()->pemesanan_dibuka) {
            return false;
        }

        $item = ItemMenu::query()->dapatDipesan()->find($itemId);
        if (! $item) {
            return false;
        }

        $data = $this->data();
        $sudah = $data[$itemId]['jumlah'] ?? 0;

        $data[$itemId] = [
            'jumlah' => min(self::MAKS_JUMLAH, $sudah + max(1, $jumlah)),
            'catatan' => $data[$itemId]['catatan'] ?? null,
            'harga' => $item->harga,
        ];
        $this->simpan($data);

        return true;
    }

    /** Ubah jumlah. Jumlah 0 atau kurang berarti item dihapus. */
    public function ubahJumlah(int $itemId, int $jumlah): void
    {
        $data = $this->data();
        if (! isset($data[$itemId])) {
            return;
        }

        if ($jumlah <= 0) {
            unset($data[$itemId]);
        } else {
            $data[$itemId]['jumlah'] = min(self::MAKS_JUMLAH, $jumlah);
        }
        $this->simpan($data);
    }

    public function ubahCatatan(int $itemId, ?string $catatan): void
    {
        $data = $this->data();
        if (! isset($data[$itemId])) {
            return;
        }

        $catatan = $catatan !== null ? trim($catatan) : null;
        $data[$itemId]['catatan'] = $catatan === '' ? null : $catatan;
        $this->simpan($data);
    }

    public function hapus(int $itemId): void
    {
        $data = $this->data();
        unset($data[$itemId]);
        $this->simpan($data);
    }

    public function kosongkan(): void
    {
        session()->forget('keranjang');
    }

    public function jumlahItem(): int
    {
        return (int) array_sum(array_column($this->data(), 'jumlah'));
    }

    /** Anggap pelanggan sudah melihat harga terbaru semua item di keranjang. */
    public function perbaruiHargaDilihat(): void
    {
        $data = $this->data();
        $items = ItemMenu::query()->whereIn('id', array_keys($data))->get()->keyBy('id');

        foreach ($data as $id => $baris) {
            if ($items->has((int) $id)) {
                $data[$id]['harga'] = $items->get((int) $id)->harga;
            }
        }
        $this->simpan($data);
    }

    /**
     * @return Collection<int, array{item:ItemMenu, jumlah:int, catatan:?string, harga_dilihat:int, harga_berubah:bool, subtotal:int}>
     */
    public function baris(): Collection
    {
        $data = $this->data();
        if ($data === []) {
            return collect();
        }

        $items = ItemMenu::query()
            ->where('aktif', true)
            ->whereIn('id', array_keys($data))
            ->get()
            ->keyBy('id');

        return collect($data)
            ->map(function (array $baris, $id) use ($items) {
                $item = $items->get((int) $id);
                if (! $item) {
                    return null;
                }

                $dilihat = (int) ($baris['harga'] ?? $item->harga);

                return [
                    'item' => $item,
                    'jumlah' => $baris['jumlah'],
                    'catatan' => $baris['catatan'] ?? null,
                    'harga_dilihat' => $dilihat,
                    'harga_berubah' => $dilihat !== $item->harga,
                    'subtotal' => $item->harga * $baris['jumlah'],
                ];
            })
            ->filter()
            ->values();
    }

    public function total(): int
    {
        return (int) $this->baris()->sum('subtotal');
    }
}
