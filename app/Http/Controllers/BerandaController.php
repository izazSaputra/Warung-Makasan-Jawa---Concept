<?php

namespace App\Http\Controllers;

use App\Models\ItemMenu;
use App\Support\Warung;

class BerandaController extends Controller
{
    public function index()
    {
        $pratinjau = ItemMenu::query()
            ->dapatDipesan()
            ->whereIn('jenis', ['paket', 'lauk', 'sayur'])
            ->orderBy('id')
            ->take(4)
            ->get();

        return view('beranda', [
            'warung' => Warung::pengaturan(),
            'pratinjau' => $pratinjau,
        ]);
    }
}
