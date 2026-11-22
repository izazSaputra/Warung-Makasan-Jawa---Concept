# HANDOFF: Website Pemesanan Warung Masakan Jawa

Dokumen ini meringkas percakapan sebelumnya untuk dilanjutkan di Claude Code. Baca `CLAUDE.md` juga, karena berisi aturan proyek yang tetap berlaku.

## Sumber kebenaran

- `PRD.md`: kebutuhan dan skenario uji (bagian 14).
- `DESIGN.md`: warna, font, komponen, dan teks antarmuka.
- `PEMBAGIAN_TUGAS.md`: pembagian tim dan jadwal 14 hari.
- `CLAUDE.md`: aturan kerja dan struktur kode.

Kalau ada yang bertentangan, ikuti dokumen di atas dan tanyakan dulu.

## Status

| Bagian | Status | Keterangan |
|---|---|---|
| Hari 1 sampai 4 | Tes lulus | Migrasi, seeder, layout, Menu, keranjang, Checkout, Status Pesanan. `app/Exceptions/PesananGagal.php` sempat hilang dari repo dan dipulihkan dari zip hari 3. |
| Hari 5: sisi pengelola | Digabung, tes lulus | Disalin dari `warung-hari5-pengelola.zip`. Satu tes di `AdminTest` diperbaiki (lihat Catatan). |
| Hari 6: Cari Makan Sesuai Budget | Digabung, tes lulus | Disalin dari `warung-hari6-budget.zip`. `routes/web.php` hari 6 sudah memuat route hari 5, jadi dipakai apa adanya. `/budget` memakai `BudgetController`. |
| Tes | Lulus | 23 November 2026, SQLite in-memory: 63 tes, 522 assertion, semua lulus. |
| Lupa kode pesanan | Ditambah, tes lulus | Di `/cek-pesanan`: nama + nomor kontak yang cocok menampilkan kode dan status pesanan 24 jam terakhir saja, tanpa rincian dan tanpa akses ke halaman status. Logika di `PencarianPesananService`. Versi terbatas dari FR-07, disetujui 23 November 2026. Tim masih perlu mengetahuinya. |
| Database | Berhasil | `migrate:fresh --seed` ke MySQL: 12 menu, 3 aturan kombinasi, 1 pengaturan, 1 akun pengelola. |
| Uji manual | Belum | Belum dicoba di browser, termasuk layar 360px. |

Catatan perbaikan saat penggabungan (23 November 2026):
- `DatabaseSeeder` masih bawaan Laravel, jadi `--seed` tidak menjalankan `WarungSeeder` dan malah membuat akun `test@example.com`. Sekarang hanya memanggil `WarungSeeder`.
- `AdminTest::test_item_nonaktif_hilang_dari_menu_pelanggan` gagal karena pesan flash pengelola (berisi nama menu) ikut tampil di sesi yang sama. Tes sekarang mengosongkan sesi sebelum membuka Menu, seperti pelanggan dengan sesinya sendiri. Kode aplikasi tidak diubah.
- Tombol merah dikurangi menjadi satu per layar sesuai `DESIGN.md`: tombol di kartu hasil Budget, tombol keadaan kosong di Budget dan Kelola Menu, serta tombol buka pemesanan di Pengaturan sekarang memakai `btn--garis`.
- `DESIGN.md` masih menyebut halaman Masuk memakai "nama pengguna", padahal keputusan tim memakai email. Perlu diperbarui oleh tim.

## File hari 5 dan 6 (perlu ada di repo)

Hari 5 (sisi pengelola):
- `app/Services/PengelolaanPesananService.php`, `app/Services/RingkasanService.php`
- `app/Http/Controllers/Admin/`: `MasukController`, `PesananAdminController`, `MenuAdminController`, `PengaturanController`, `RingkasanController`
- `resources/views/admin/`: `layout.blade.php`, `masuk.blade.php`, `_badge.blade.php`, `pesanan.blade.php`, `pengaturan.blade.php`, `ringkasan.blade.php`, `menu/index.blade.php`, `menu/form.blade.php`
- `public/css/admin.css`, `routes/web.php` (diganti), `tests/Feature/AdminTest.php`

Hari 6 (Budget):
- `app/Services/BudgetService.php`, `app/Http/Controllers/BudgetController.php`
- `resources/views/budget.blade.php`, `public/css/budget.css`
- `routes/web.php` (diganti: route `/budget` dan `/budget/keranjang`, menggantikan halaman "segera hadir")
- `tests/Feature/BudgetTest.php`

Catatan: `routes/web.php` dari hari 5 dan 6 saling menggantikan. Gabungkan keduanya, dan pastikan `budget` tidak lagi memakai `Route::view('/budget', 'segera')`. Jika tidak yakin, buat ulang dari `routes/web.php` hari 5 lalu ganti blok Budget dengan dua route dari hari 6.

## Keputusan yang sudah diambil

- Pelanggan tidak punya akun. Cek pesanan memakai kode dan nomor kontak.
- Pengelola satu peran. Login memakai email (tabel `users`), bukan nama pengguna.
- Halaman terpisah dihapus: Keranjang, Konfirmasi, Riwayat, Pesan Lagi. Keadaan khusus dibuat sebagai banner atau pesan.
- Pembayaran hanya saat ambil. Tidak ada QRIS atau e-wallet.
- Status sah: Menunggu konfirmasi, Diterima, Disiapkan, Siap diambil, Selesai, Ditolak (wajib beralasan). Tidak ada "Dibatalkan".
- Alur pengelola: Terima, lalu Mulai Siapkan, lalu Tandai Siap Diambil, lalu Tandai Selesai.
- Menu nonaktif disembunyikan, bukan dihapus, supaya riwayat pesanan tetap utuh.
- Ringkasan hanya menghitung pesanan website yang berstatus Selesai, dihitung dari tanggal di `riwayat_status`.
- Budget: preferensi rasa hanya menyaring lauk dan paket. Hasil diurutkan dari yang paling mendekati budget. Satu paket atau lauk maksimal muncul 2 kali di 6 pilihan.
- Banner "belum dikonfirmasi mitra" tampil di Budget selama `aturan_kombinasi.dikonfirmasi_mitra = false`.
- Kode Budget memakai `Route::get('/budget')`. Nilai `budget` yang kosong dibaca dari keberadaan parameter, bukan nilainya.

## Hal yang menunggu mitra

Nama resmi, alamat, kontak, menu dan harga asli, apakah harga lauk sudah termasuk nasi, aturan kombinasi Budget, cara bayar selain saat ambil, apakah nomor telepon wajib, dan label rasa yang boleh tampil.

## Langkah berikutnya (urutan)

1. Jalankan `composer install`, `php artisan migrate:fresh --seed`, lalu `php artisan test`. Perbaiki galat dari hari 5 dan 6 sampai hijau.
2. Uji manual dari awal sampai akhir: Menu, lalu Budget, lalu Masukkan ke Keranjang, lalu Checkout, lalu Kirim, lalu terima di admin, lalu Selesai, lalu cek angka di Ringkasan.
3. Uji nilai batas dari PRD bagian 14 dan `PEMBAGIAN_TUGAS.md` (hari 12-14): item termurah habis, dua tab admin yang mengubah status yang sama, tombol kirim ditekan dua kali, akses admin tanpa login, dan layar 360px.
4. Ganti kata sandi akun contoh sebelum demo.
5. Perbarui "Status pengerjaan" di `CLAUDE.md`.

## Hal yang tidak boleh dilakukan

- Tidak mengarang menu, harga, alamat, atau kontak.
- Tidak menghitung total atau mengubah status di controller atau view.
- Tidak menambah pembayaran online, halaman yang tidak ada di SKPL, atau status baru.
- Tidak menyatakan sesuatu sudah berjalan sebelum `php artisan test` dan uji manual dilakukan.
