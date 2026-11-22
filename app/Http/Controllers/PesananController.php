<?php

namespace App\Http\Controllers;

use App\Enums\StatusPesanan;
use App\Models\Pesanan;
use App\Support\Format;
use App\Support\Warung;
use Illuminate\Http\Request;

class PesananController extends Controller
{
    public function formCek()
    {
        return view('cek-pesanan', ['warung' => Warung::pengaturan()]);
    }

    /** Cek status memakai kode pesanan + nomor kontak (tanpa akun). */
    public function cek(Request $request)
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20'],
            'kontak' => ['required', 'string', 'max:30'],
        ], [
            'kode.required' => 'Kode pesanan wajib diisi.',
            'kontak.required' => 'Nomor kontak wajib diisi.',
        ]);

        $pesanan = Pesanan::query()
            ->where('kode_pesanan', strtoupper(trim($data['kode'])))
            ->first();

        // Pesan umum: tidak membuka data apa pun bila tidak cocok.
        if (! $pesanan || ! hash_equals($pesanan->kontak, Format::nomor($data['kontak']))) {
            return back()->withInput($request->only('kode'))->with('galat', 'Kode atau nomor tidak cocok.');
        }

        $this->beriAkses($pesanan->kode_pesanan);

        return redirect()->route('pesanan.status', $pesanan->kode_pesanan);
    }

    public function tampil(string $kode)
    {
        $kode = strtoupper($kode);

        if (! in_array($kode, session('akses_pesanan', []), true)) {
            return redirect()
                ->route('cek-pesanan')
                ->with('galat', 'Masukkan kode pesanan dan nomor kontak untuk melihat status.');
        }

        $pesanan = Pesanan::query()
            ->with(['items', 'riwayatStatus'])
            ->where('kode_pesanan', $kode)
            ->firstOrFail();

        $urut = [
            StatusPesanan::MenungguKonfirmasi,
            StatusPesanan::Diterima,
            StatusPesanan::Disiapkan,
            StatusPesanan::SiapDiambil,
            StatusPesanan::Selesai,
        ];
        $alur = $pesanan->status === StatusPesanan::Ditolak
            ? [StatusPesanan::MenungguKonfirmasi, StatusPesanan::Ditolak]
            : $urut;

        $posisi = array_search($pesanan->status, $alur, true);
        $waktu = $pesanan->riwayatStatus->mapWithKeys(fn ($r) => [$r->status->value => $r->waktu]);

        $tahap = collect($alur)->map(fn (StatusPesanan $s, int $i) => [
            'status' => $s,
            'kondisi' => $i < $posisi ? 'selesai' : ($i === $posisi ? 'sekarang' : 'belum'),
            'waktu' => $waktu->get($s->value),
        ]);

        return view('pesanan-status', [
            'warung' => Warung::pengaturan(),
            'pesanan' => $pesanan,
            'tahap' => $tahap,
        ]);
    }

    private function beriAkses(string $kode): void
    {
        $akses = session('akses_pesanan', []);
        $akses[] = $kode;
        session(['akses_pesanan' => array_values(array_unique($akses))]);
    }
}
