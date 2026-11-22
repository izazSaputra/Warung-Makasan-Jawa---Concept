<?php

namespace App\Services;

use App\Enums\JenisHasil;
use App\Exceptions\PesananGagal;
use App\Models\AturanKombinasi;
use App\Models\ItemMenu;
use App\Support\Format;
use App\Support\Warung;
use Illuminate\Support\Collection;

/**
 * Cari Makan Sesuai Budget (FR-03).
 *
 * Semua menu, harga, dan susunan kombinasi berasal dari database: item aktif+tersedia
 * dan tabel aturan_kombinasi (SKPL-NF-19). Layanan ini tidak mengarang apa pun.
 *
 * Format aturan: pola_komponen = daftar alternatif, tiap alternatif = daftar jenis item,
 * satu item per jenis. Contoh [["paket"],["nasi","lauk","sayur"]] artinya
 * "satu paket" ATAU "satu nasi + satu lauk + satu sayur".
 */
class BudgetService
{
    public const MAKS_BUDGET = 1_000_000;

    /** Jumlah pilihan yang ditampilkan. */
    public const BATAS_HASIL = 6;

    /** Satu paket/lauk tidak muncul lebih dari ini di daftar tampil, supaya hasil bervariasi. */
    public const MAKS_PER_UTAMA = 2;

    /** Pengaman: hentikan penelusuran bila kombinasi terlalu banyak. */
    private const BATAS_TELUSUR = 20000;

    /** Label rasa hanya menyaring jenis ini (nasi, minuman, dan sayur tidak ikut disaring). */
    private const JENIS_BERLABEL = ['paket', 'lauk'];

    /**
     * @return array{
     *   budget:int, jenis:JenisHasil, label:?string,
     *   kombinasi:array<int, array{items:array<int, ItemMenu>, total:int, sisa:int}>,
     *   jumlah_ditemukan:int,
     *   termurah:?array{items:array<int, ItemMenu>, total:int},
     *   alasan_kosong:?string,
     *   belum_dikonfirmasi:bool
     * }
     */
    public function cari(int $budget, JenisHasil $jenis, ?string $label = null): array
    {
        $aturan = AturanKombinasi::query()->where('jenis_hasil', $jenis->value)->first();
        $pola = $this->pola($aturan?->pola_komponen);

        $items = ItemMenu::query()->dapatDipesan()
            ->orderBy('harga')->orderBy('nama')
            ->get()
            ->groupBy(fn (ItemMenu $i) => $i->jenis->value);

        $semua = $this->susun($pola, $items, $budget, $label);

        $kombinasi = array_map(
            fn (array $k) => $k + ['sisa' => $budget - $k['total']],
            $this->pilihUntukTampil($semua)
        );

        $termurah = null;
        $alasan = null;

        if ($semua === []) {
            $termurah = $this->termurah($pola, $items, $label);

            if ($termurah === null && $label !== null) {
                // Tidak ada kombinasi sama sekali karena preferensi rasa. Cek tanpa preferensi.
                $termurah = $this->termurah($pola, $items, null);
                $alasan = $termurah === null ? 'tidak_ada_menu' : 'preferensi';
                if ($alasan === 'preferensi' && $termurah['total'] > $budget) {
                    $alasan = 'budget_kurang';
                }
            } elseif ($termurah === null) {
                $alasan = 'tidak_ada_menu';
            } else {
                $alasan = 'budget_kurang';
            }
        }

        return [
            'budget' => $budget,
            'jenis' => $jenis,
            'label' => $label,
            'kombinasi' => $kombinasi,
            'jumlah_ditemukan' => count($semua),
            'termurah' => $termurah,
            'alasan_kosong' => $alasan,
            'belum_dikonfirmasi' => $aturan === null || ! $aturan->dikonfirmasi_mitra,
        ];
    }

    /**
     * Periksa kombinasi sebelum dimasukkan ke keranjang: harga dan ketersediaan dibaca ulang dari database,
     * dan total sekarang tidak boleh melewati budget.
     *
     * @param  array<int, int|string>  $ids
     * @return array{items:Collection<int, ItemMenu>, total:int}
     *
     * @throws PesananGagal
     */
    public function siapkanUntukKeranjang(array $ids, int $budget): array
    {
        if (! Warung::pengaturan()->pemesanan_dibuka) {
            throw new PesananGagal('Pemesanan sedang ditutup. Kombinasi belum bisa dimasukkan ke keranjang.');
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $items = ItemMenu::query()->dapatDipesan()->whereIn('id', $ids)->get();

        if ($ids === [] || $items->count() !== count($ids)) {
            throw new PesananGagal('Ada menu dalam kombinasi ini yang sudah habis atau tidak tersedia. Silakan cari lagi.');
        }

        $total = (int) $items->sum('harga');
        if ($total > $budget) {
            throw new PesananGagal(sprintf(
                'Harga menu berubah. Total sekarang %s, melebihi budget %s. Silakan cari lagi.',
                Format::rupiah($total),
                Format::rupiah($budget)
            ));
        }

        return ['items' => $items, 'total' => $total];
    }

    /** @return array<int, array<int, string>> */
    private function pola(mixed $mentah): array
    {
        if (! is_array($mentah)) {
            return [];
        }

        $hasil = [];
        foreach ($mentah as $komponen) {
            if (is_array($komponen) && $komponen !== [] && count(array_filter($komponen, 'is_string')) === count($komponen)) {
                $hasil[] = array_values($komponen);
            }
        }

        return $hasil;
    }

    /**
     * Daftar kandidat per posisi komponen (urut harga naik), atau null bila ada jenis yang kosong.
     *
     * @param  array<int, string>  $komponen
     * @return array<int, Collection<int, ItemMenu>>|null
     */
    private function kandidat(array $komponen, Collection $items, ?string $label): ?array
    {
        $daftar = [];
        foreach ($komponen as $jenis) {
            $kandidat = $items->get($jenis, collect());

            if ($label !== null && in_array($jenis, self::JENIS_BERLABEL, true)) {
                $kandidat = $kandidat->filter(fn (ItemMenu $i) => in_array($label, $i->label ?? [], true));
            }

            if ($kandidat->isEmpty()) {
                return null;
            }
            $daftar[] = $kandidat->values();
        }

        return $daftar;
    }

    /**
     * @param  array<int, array<int, string>>  $pola
     * @return array<int, array{items:array<int, ItemMenu>, total:int}>
     */
    private function susun(array $pola, Collection $items, int $budget, ?string $label): array
    {
        $hasil = [];
        $terlihat = [];

        foreach ($pola as $komponen) {
            $daftar = $this->kandidat($komponen, $items, $label);
            if ($daftar === null) {
                continue;
            }

            // Harga terendah yang masih harus dipenuhi mulai dari posisi i, untuk memangkas penelusuran.
            $minSisa = array_fill(0, count($daftar) + 1, 0);
            for ($i = count($daftar) - 1; $i >= 0; $i--) {
                $minSisa[$i] = $minSisa[$i + 1] + $daftar[$i]->first()->harga;
            }

            $this->telusuri($komponen, $daftar, $minSisa, 0, [], 0, $budget, $hasil, $terlihat);

            if (count($hasil) >= self::BATAS_TELUSUR) {
                break;
            }
        }

        return $hasil;
    }

    /**
     * @param  array<int, string>  $komponen
     * @param  array<int, Collection<int, ItemMenu>>  $daftar
     * @param  array<int, int>  $minSisa
     * @param  array<int, ItemMenu>  $dipilih
     */
    private function telusuri(array $komponen, array $daftar, array $minSisa, int $pos, array $dipilih, int $jumlah, int $budget, array &$hasil, array &$terlihat): void
    {
        if ($pos === count($daftar)) {
            $ids = array_map(fn (ItemMenu $i) => $i->id, $dipilih);
            sort($ids);
            $kunci = implode('-', $ids);

            if (! isset($terlihat[$kunci])) {
                $terlihat[$kunci] = true;
                $hasil[] = ['items' => $dipilih, 'total' => $jumlah];
            }

            return;
        }

        foreach ($daftar[$pos] as $item) {
            // Kandidat urut harga naik: begitu melewati budget, sisanya pasti melewati juga.
            if ($jumlah + $item->harga + $minSisa[$pos + 1] > $budget) {
                break;
            }
            if (count($hasil) >= self::BATAS_TELUSUR) {
                return;
            }

            // Satu item tidak boleh dipakai dua kali, dan jenis yang sama berurutan dibatasi naik id (hindari susunan kembar).
            foreach ($dipilih as $sudah) {
                if ($sudah->id === $item->id) {
                    continue 2;
                }
            }
            if ($pos > 0 && $komponen[$pos] === $komponen[$pos - 1] && $item->id < $dipilih[$pos - 1]->id) {
                continue;
            }

            $this->telusuri($komponen, $daftar, $minSisa, $pos + 1, [...$dipilih, $item], $jumlah + $item->harga, $budget, $hasil, $terlihat);
        }
    }

    /**
     * Kombinasi tersedia paling murah, tanpa melihat budget.
     *
     * @param  array<int, array<int, string>>  $pola
     * @return array{items:array<int, ItemMenu>, total:int}|null
     */
    private function termurah(array $pola, Collection $items, ?string $label): ?array
    {
        $terbaik = null;

        foreach ($pola as $komponen) {
            $daftar = $this->kandidat($komponen, $items, $label);
            if ($daftar === null) {
                continue;
            }

            $dipilih = [];
            $layak = true;
            foreach (array_count_values($komponen) as $jenis => $butuh) {
                $kandidat = $items->get($jenis, collect());
                if ($label !== null && in_array($jenis, self::JENIS_BERLABEL, true)) {
                    $kandidat = $kandidat->filter(fn (ItemMenu $i) => in_array($label, $i->label ?? [], true));
                }
                if ($kandidat->count() < $butuh) {
                    $layak = false;
                    break;
                }
                foreach ($kandidat->values()->take($butuh) as $item) {
                    $dipilih[] = $item;
                }
            }

            if (! $layak) {
                continue;
            }

            $total = (int) array_sum(array_map(fn (ItemMenu $i) => $i->harga, $dipilih));
            if ($terbaik === null || $total < $terbaik['total']) {
                $terbaik = ['items' => $this->urutkanSesuaiPola($dipilih, $komponen), 'total' => $total];
            }
        }

        return $terbaik;
    }

    /**
     * @param  array<int, ItemMenu>  $items
     * @param  array<int, string>  $komponen
     * @return array<int, ItemMenu>
     */
    private function urutkanSesuaiPola(array $items, array $komponen): array
    {
        $urutan = array_flip(array_unique($komponen));
        usort($items, fn (ItemMenu $a, ItemMenu $b) => $urutan[$a->jenis->value] <=> $urutan[$b->jenis->value]);

        return $items;
    }

    /**
     * Urut: paling mendekati budget (total terbesar) dulu. Lalu variasi: satu paket/lauk maksimal
     * MAKS_PER_UTAMA kali; sisa slot diisi dari yang tertunda.
     *
     * @param  array<int, array{items:array<int, ItemMenu>, total:int}>  $semua
     * @return array<int, array{items:array<int, ItemMenu>, total:int}>
     */
    private function pilihUntukTampil(array $semua): array
    {
        usort($semua, function (array $a, array $b) {
            return [$b['total'], count($a['items']), $this->namaGabung($a)]
                <=> [$a['total'], count($b['items']), $this->namaGabung($b)];
        });

        $dipilih = [];
        $tertunda = [];
        $pakai = [];

        foreach ($semua as $k) {
            if (count($dipilih) >= self::BATAS_HASIL) {
                break;
            }

            $utama = array_values(array_filter(
                $k['items'],
                fn (ItemMenu $i) => in_array($i->jenis->value, self::JENIS_BERLABEL, true)
            ));

            $muat = true;
            foreach ($utama as $i) {
                if (($pakai[$i->id] ?? 0) >= self::MAKS_PER_UTAMA) {
                    $muat = false;
                }
            }

            if ($muat) {
                $dipilih[] = $k;
                foreach ($utama as $i) {
                    $pakai[$i->id] = ($pakai[$i->id] ?? 0) + 1;
                }
            } else {
                $tertunda[] = $k;
            }
        }

        foreach ($tertunda as $k) {
            if (count($dipilih) >= self::BATAS_HASIL) {
                break;
            }
            $dipilih[] = $k;
        }

        return $dipilih;
    }

    private function namaGabung(array $k): string
    {
        return implode('|', array_map(fn (ItemMenu $i) => $i->nama, $k['items']));
    }
}
