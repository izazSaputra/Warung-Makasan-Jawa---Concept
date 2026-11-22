<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JenisItem;
use App\Http\Controllers\Controller;
use App\Models\ItemMenu;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuAdminController extends Controller
{
    public function index()
    {
        $urutan = array_map(fn (JenisItem $j) => $j->value, JenisItem::cases());

        $items = ItemMenu::query()->get()
            ->sortBy([
                fn ($a, $b) => array_search($a->jenis->value, $urutan) <=> array_search($b->jenis->value, $urutan),
                fn ($a, $b) => strcmp($a->nama, $b->nama),
            ])
            ->values();

        return view('admin.menu.index', ['items' => $items]);
    }

    public function buat()
    {
        return view('admin.menu.form', [
            'item' => new ItemMenu(['aktif' => true, 'tersedia' => true]),
            'daftarJenis' => JenisItem::cases(),
        ]);
    }

    public function simpan(Request $request)
    {
        $item = ItemMenu::create($this->data($request));

        return redirect()->route('admin.menu')->with('pesan', "Menu \"{$item->nama}\" ditambahkan.");
    }

    public function ubah(ItemMenu $item)
    {
        return view('admin.menu.form', ['item' => $item, 'daftarJenis' => JenisItem::cases()]);
    }

    public function perbarui(Request $request, ItemMenu $item)
    {
        $item->update($this->data($request, $item));

        return redirect()->route('admin.menu')
            ->with('pesan', "Menu \"{$item->nama}\" diperbarui. Perubahan harga berlaku untuk pesanan baru.");
    }

    /** Saklar Tersedia/Habis: satu aksi, satu langkah. */
    public function ubahTersedia(ItemMenu $item)
    {
        $item->update(['tersedia' => ! $item->tersedia]);

        return back()->with('pesan', $item->tersedia
            ? "\"{$item->nama}\" ditandai tersedia."
            : "\"{$item->nama}\" ditandai habis. Tidak bisa dipesan lagi.");
    }

    /** Saklar Aktif: item nonaktif disembunyikan dari pelanggan (tidak dihapus agar riwayat tetap utuh). */
    public function ubahAktif(ItemMenu $item)
    {
        $item->update(['aktif' => ! $item->aktif]);

        return back()->with('pesan', $item->aktif
            ? "\"{$item->nama}\" diaktifkan dan tampil di menu."
            : "\"{$item->nama}\" dinonaktifkan dan disembunyikan dari menu.");
    }

    /** @return array<string, mixed> */
    private function data(Request $request, ?ItemMenu $item = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120', Rule::unique('item_menu', 'nama')->ignore($item?->id)],
            'jenis' => ['required', Rule::enum(JenisItem::class)],
            'harga' => ['required', 'integer', 'min:1', 'max:10000000'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'in:Pedas,Tidak pedas'],
        ], [
            'nama.required' => 'Nama menu wajib diisi.',
            'nama.unique' => 'Sudah ada menu dengan nama ini.',
            'nama.max' => 'Nama menu terlalu panjang (maksimal 120 karakter).',
            'jenis.required' => 'Pilih jenis menu.',
            'harga.required' => 'Harga wajib diisi.',
            'harga.integer' => 'Harga harus berupa angka bulat tanpa titik, contoh 12000.',
            'harga.min' => 'Harga harus lebih dari 0.',
            'harga.max' => 'Harga terlalu besar.',
            'keterangan.max' => 'Keterangan terlalu panjang (maksimal 255 karakter).',
            'label.in' => 'Label rasa tidak dikenal.',
        ]);

        return [
            'nama' => trim($data['nama']),
            'jenis' => $data['jenis'],
            'harga' => (int) $data['harga'],
            'keterangan' => ($data['keterangan'] ?? null) ?: null,
            'label' => ($data['label'] ?? null) ? [$data['label']] : [],
            'aktif' => $request->boolean('aktif'),
            'tersedia' => $request->boolean('tersedia'),
        ];
    }
}
