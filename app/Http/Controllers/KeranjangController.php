<?php

namespace App\Http\Controllers;

use App\Services\KeranjangService;
use Illuminate\Http\Request;

class KeranjangController extends Controller
{
    public function tambah(Request $request, KeranjangService $keranjang)
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer'],
            'jumlah' => ['nullable', 'integer', 'min:1', 'max:'.KeranjangService::MAKS_JUMLAH],
        ]);

        $berhasil = $keranjang->tambah((int) $data['item_id'], (int) ($data['jumlah'] ?? 1));

        return back()->with(
            $berhasil ? 'pesan' : 'galat',
            $berhasil
                ? 'Item ditambahkan ke keranjang.'
                : 'Item tidak bisa ditambahkan. Mungkin sudah habis atau pemesanan sedang ditutup.'
        );
    }

    public function ubah(Request $request, int $item, KeranjangService $keranjang)
    {
        $data = $request->validate([
            'jumlah' => ['required', 'integer', 'min:0', 'max:'.KeranjangService::MAKS_JUMLAH],
        ]);

        $keranjang->ubahJumlah($item, (int) $data['jumlah']);

        return back();
    }

    public function catatan(Request $request, int $item, KeranjangService $keranjang)
    {
        $data = $request->validate([
            'catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'catatan.max' => 'Catatan item terlalu panjang (maksimal 255 karakter).',
        ]);

        $keranjang->ubahCatatan($item, $data['catatan'] ?? null);

        return back()->with('pesan', 'Catatan disimpan.');
    }

    public function hapus(int $item, KeranjangService $keranjang)
    {
        $keranjang->hapus($item);

        return back()->with('pesan', 'Item dihapus dari keranjang.');
    }
}
