<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengaturanWarung;
use Illuminate\Http\Request;

class PengaturanController extends Controller
{
    public function tampil()
    {
        return view('admin.pengaturan', ['pengaturan' => $this->baris()]);
    }

    public function perbarui(Request $request)
    {
        $data = $request->validate([
            'nama_resmi' => ['required', 'string', 'max:120'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'kontak' => ['nullable', 'string', 'max:40'],
            'jam_buka' => ['nullable', 'string', 'max:60'],
            'info_pengambilan' => ['nullable', 'string', 'max:1000'],
        ], [
            'nama_resmi.required' => 'Nama warung wajib diisi.',
            'nama_resmi.max' => 'Nama warung terlalu panjang (maksimal 120 karakter).',
        ]);

        $this->baris()->update($data);

        return back()->with('pesan', 'Info warung disimpan.');
    }

    /** Saklar buka/tutup pemesanan. Pesanan yang sudah masuk tidak berubah. */
    public function ubahPemesanan()
    {
        $p = $this->baris();
        $p->update(['pemesanan_dibuka' => ! $p->pemesanan_dibuka]);

        return back()->with('pesan', $p->pemesanan_dibuka
            ? 'Pemesanan dibuka. Pelanggan bisa mengirim pesanan.'
            : 'Pemesanan ditutup. Pelanggan tidak bisa mengirim pesanan baru.');
    }

    private function baris(): PengaturanWarung
    {
        return PengaturanWarung::query()->firstOrCreate([], ['nama_resmi' => 'Warung Masakan Jawa', 'pemesanan_dibuka' => true]);
    }
}
