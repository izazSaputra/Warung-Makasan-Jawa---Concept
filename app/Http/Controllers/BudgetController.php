<?php

namespace App\Http\Controllers;

use App\Enums\JenisHasil;
use App\Exceptions\PesananGagal;
use App\Services\BudgetService;
use App\Services\KeranjangService;
use App\Support\Format;
use App\Support\Warung;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request, BudgetService $layanan)
    {
        $jenis = JenisHasil::tryFrom((string) $request->query('jenis', '')) ?? JenisHasil::MakananLengkap;
        $label = in_array($request->query('label'), ['Pedas', 'Tidak pedas'], true) ? $request->query('label') : null;

        $dikirim = $request->query->has('budget');
        $mentah = $request->query('budget');
        $hasil = null;
        $galat = null;

        if ($dikirim) {
            [$nominal, $galat] = $this->bacaNominal($mentah);
            if ($galat === null) {
                $hasil = $layanan->cari($nominal, $jenis, $label);
            }
        }

        return view('budget', [
            'warung' => Warung::pengaturan(),
            'jenis' => $jenis,
            'label' => $label,
            'daftarJenis' => JenisHasil::cases(),
            'nilaiInput' => is_scalar($mentah) ? (string) $mentah : '',
            'galat' => $galat,
            'hasil' => $hasil,
            'cepat' => [10000, 15000, 20000, 25000, 30000],
        ]);
    }

    public function keranjang(Request $request, BudgetService $layanan, KeranjangService $keranjang)
    {
        $data = $request->validate([
            'budget' => ['required', 'integer', 'min:1', 'max:'.BudgetService::MAKS_BUDGET],
            'item_ids' => ['required', 'array', 'min:1', 'max:10'],
            'item_ids.*' => ['integer'],
        ]);

        try {
            $siap = $layanan->siapkanUntukKeranjang($data['item_ids'], (int) $data['budget']);
        } catch (PesananGagal $e) {
            return back()->with('galat', $e->getMessage());
        }

        foreach ($siap['items'] as $item) {
            $keranjang->tambah($item->id, 1);
        }

        return redirect()->route('checkout')->with(
            'pesan',
            'Kombinasi '.Format::rupiah($siap['total']).' dimasukkan ke keranjang. Ubah jumlah atau hapus item di bawah bila perlu.'
        );
    }

    /**
     * 
     *
     * @return array{0:?int, 1:?string}
     */
    private function bacaNominal(mixed $mentah): array
    {
        if ($mentah === null) {
            return [null, 'Nominal budget wajib diisi.'];
        }
        if (! is_scalar($mentah)) {
            return [null, 'Masukkan nominal dalam angka, contoh 15000.'];
        }

        $s = strtolower(trim((string) $mentah));
        $s = preg_replace('/^rp\.?\s*/', '', $s);
        $s = trim($s);

        if ($s === '') {
            return [null, 'Nominal budget wajib diisi.'];
        }
        if (preg_match('/^-\s*\d/', $s)) {
            return [null, 'Budget harus lebih dari Rp0.'];
        }
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $s)) {
            $s = str_replace('.', '', $s);
        }
        if (! preg_match('/^\d+$/', $s)) {
            return [null, 'Tulis angka bulat saja, contoh 15000 atau 15.000.'];
        }
        if (strlen(ltrim($s, '0')) > 9 || (int) $s > BudgetService::MAKS_BUDGET) {
            return [null, 'Budget terlalu besar. Maksimal '.Format::rupiah(BudgetService::MAKS_BUDGET).'.'];
        }

        $nilai = (int) $s;
        if ($nilai <= 0) {
            return [null, 'Budget harus lebih dari Rp0.'];
        }

        return [$nilai, null];
    }
}
