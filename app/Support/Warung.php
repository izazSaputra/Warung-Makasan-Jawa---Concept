<?php

namespace App\Support;

use App\Models\PengaturanWarung;

class Warung
{
    /**
     * Pengaturan warung saat ini. Jika seeder belum dijalankan,
     * kembalikan nilai bawaan agar halaman tidak error.
     */
    public static function pengaturan(): PengaturanWarung
    {
        return PengaturanWarung::query()->first()
            ?? new PengaturanWarung([
                'nama_resmi' => 'Warung Masakan Jawa',
                'pemesanan_dibuka' => true,
            ]);
    }
}
