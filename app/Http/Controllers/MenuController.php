<?php

namespace App\Http\Controllers;

use App\Enums\JenisItem;
use App\Models\ItemMenu;
use App\Services\KeranjangService;
use App\Support\Warung;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request, KeranjangService $keranjang)
    {
        $q = trim((string) $request->query('q', ''));
        $jenis = JenisItem::tryFrom((string) $request->query('jenis', ''));
        $label = in_array($request->query('label'), ['Pedas', 'Tidak pedas'], true)
            ? $request->query('label')
            : null;

        // Item nonaktif disembunyikan. Item habis tetap tampil dengan tanda "Habis".
        $items = ItemMenu::query()
            ->where('aktif', true)
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub->where('nama', 'like', "%{$q}%")->orWhere('keterangan', 'like', "%{$q}%")
            ))
            ->when($jenis, fn ($query) => $query->where('jenis', $jenis->value))
            ->when($label, fn ($query) => $query->whereJsonContains('label', $label))
            ->orderByDesc('tersedia')
            ->orderBy('id')
            ->get();

        return view('menu', [
            'warung' => Warung::pengaturan(),
            'items' => $items,
            'q' => $q,
            'jenis' => $jenis,
            'label' => $label,
            'daftarJenis' => JenisItem::cases(),
            'baris' => $keranjang->baris(),
            'total' => $keranjang->total(),
        ]);
    }
}
