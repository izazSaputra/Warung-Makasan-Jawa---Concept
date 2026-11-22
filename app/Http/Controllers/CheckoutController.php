<?php

namespace App\Http\Controllers;

use App\Exceptions\PesananGagal;
use App\Services\KeranjangService;
use App\Services\PemesananService;
use App\Support\Warung;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function tampil(KeranjangService $keranjang)
    {
        // Kunci ini ikut terkirim bersama formulir untuk mencegah pesanan ganda.
        $kunci = session('kunci_checkout');
        if (! $kunci) {
            $kunci = (string) Str::uuid();
            session(['kunci_checkout' => $kunci]);
        }

        return view('checkout', [
            'warung' => Warung::pengaturan(),
            'baris' => $keranjang->baris(),
            'total' => $keranjang->total(),
        ]);
    }

    public function kirim(Request $request, PemesananService $pemesanan)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kontak' => ['required', 'string', 'regex:/^\+?[0-9][0-9\s\-]{6,18}$/'],
            'waktu_pilihan' => ['required', 'in:sekarang,30,60,jam'],
            'jam_ambil' => ['required_if:waktu_pilihan,jam', 'nullable', 'date_format:H:i'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'nama.required' => 'Nama pemesan wajib diisi.',
            'nama.max' => 'Nama pemesan terlalu panjang (maksimal 100 karakter).',
            'kontak.required' => 'Nomor kontak wajib diisi.',
            'kontak.regex' => 'Nomor kontak tidak valid. Contoh: 0812-3456-7890.',
            'waktu_pilihan.required' => 'Pilih waktu ambil yang diminta.',
            'waktu_pilihan.in' => 'Pilihan waktu ambil tidak dikenal.',
            'jam_ambil.required_if' => 'Isi jam ambil jika memilih "Pilih jam".',
            'jam_ambil.date_format' => 'Format jam ambil tidak valid.',
            'catatan.max' => 'Catatan terlalu panjang (maksimal 500 karakter).',
        ]);

        $kunci = session('kunci_checkout') ?: (string) Str::uuid();

        try {
            $pesanan = $pemesanan->kirim($data, $kunci);
        } catch (PesananGagal $e) {
            return back()->withInput()->with('galat', $e->getMessage());
        }

        session()->forget('kunci_checkout');

        // Beri akses sesi ke halaman status pesanan yang baru dibuat.
        $akses = session('akses_pesanan', []);
        $akses[] = $pesanan->kode_pesanan;
        session(['akses_pesanan' => array_values(array_unique($akses))]);

        return redirect()
            ->route('pesanan.status', $pesanan->kode_pesanan)
            ->with('pesan', 'Pesanan terkirim. Simpan kode pesanan ini untuk mengecek status.');
    }
}
