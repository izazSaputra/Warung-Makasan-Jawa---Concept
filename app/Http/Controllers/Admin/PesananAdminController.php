<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPesanan;
use App\Exceptions\PesananGagal;
use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Services\PengelolaanPesananService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PesananAdminController extends Controller
{
    private const BATAS = 100;

    public function index(Request $request)
    {
        $aktif = StatusPesanan::tryFrom((string) $request->query('status', '')) ?? StatusPesanan::MenungguKonfirmasi;

        $hitung = Pesanan::query()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $query = Pesanan::query()->with('items')->where('status', $aktif->value);

        // Yang masih berjalan: waktu ambil terdekat dulu. Yang sudah final: terbaru dulu.
        if ($aktif->final()) {
            $query->orderByDesc('id');
        } else {
            $query->orderBy('waktu_ambil_diminta')->orderBy('id');
        }

        return view('admin.pesanan', [
            'aktif' => $aktif,
            'tab' => StatusPesanan::cases(),
            'hitung' => $hitung,
            'pesanan' => $query->limit(self::BATAS)->get(),
            'batas' => self::BATAS,
            'totalStatus' => (int) ($hitung[$aktif->value] ?? 0),
        ]);
    }

    public function ubahStatus(Request $request, Pesanan $pesanan, PengelolaanPesananService $layanan)
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(StatusPesanan::class)],
            'dari' => ['nullable', Rule::enum(StatusPesanan::class)],
            'alasan' => ['nullable', 'string', 'max:255'],
        ], [
            'status.required' => 'Status tujuan wajib dipilih.',
            'alasan.max' => 'Alasan terlalu panjang (maksimal 255 karakter).',
        ]);

        try {
            $baru = $layanan->ubahStatus(
                $pesanan->id,
                StatusPesanan::from($data['status']),
                $data['alasan'] ?? null,
                isset($data['dari']) ? StatusPesanan::from($data['dari']) : null,
            );
        } catch (PesananGagal $e) {
            return back()->with('galat', $e->getMessage());
        }

        // Kembali ke tab asal supaya pengelola bisa lanjut memproses pesanan berikutnya.
        return redirect()
            ->route('admin.pesanan', ['status' => $data['dari'] ?? $baru->status->value])
            ->with('pesan', sprintf('Pesanan %s sekarang berstatus "%s".', $baru->kode_pesanan, $baru->status->label()));
    }
}
