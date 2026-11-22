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
| Tes | Lulus | 23 November 2026, SQLite in-memory: 64 tes, 523 assertion, semua lulus. |
| Zona waktu | Diperbaiki | `config/app.php` diubah dari `UTC` ke `Asia/Jakarta`. Sebelumnya jam yang tampil mundur 7 jam dan Ringkasan memotong hari pada 07.00 WIB. Dijaga oleh `AdminTest::test_ringkasan_memakai_tanggal_wib`. |
| Lupa kode pesanan | Ditambah, tes lulus | Di `/cek-pesanan`: nama + nomor kontak yang cocok menampilkan kode dan status pesanan 24 jam terakhir saja, tanpa rincian dan tanpa akses ke halaman status. Logika di `PencarianPesananService`. Versi terbatas dari FR-07, disetujui 23 November 2026. Tim masih perlu mengetahuinya. |
| Database | Berhasil | `migrate:fresh --seed` ke MySQL: 12 menu, 3 aturan kombinasi, 1 pengaturan, 1 akun pengelola. |
| Uji manual | Sebagian | Alur penuh (pesan sampai Selesai di admin) sudah dicoba di browser dan berjalan. Layar 360px dan ponsel belum dicoba. |

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
- Banner "data contoh" tampil di Budget selama `aturan_kombinasi.dikonfirmasi_mitra = false`. Kolomnya tetap ada, hanya teks banner yang diubah.
- Penilaian atau konfirmasi oleh mitra (pemilik warung) dibatalkan oleh dosen (23 November 2026). Menu, harga, alamat, kontak, dan aturan kombinasi tetap data contoh dari `WarungSeeder`, dan ditandai sebagai data contoh di tampilan.
- Kode Budget memakai `Route::get('/budget')`. Nilai `budget` yang kosong dibaca dari keberadaan parameter, bukan nilainya.

## Data contoh (tidak lagi menunggu mitra)

Karena konfirmasi mitra dibatalkan, hal berikut tetap memakai data contoh: nama resmi, alamat, kontak, menu dan harga, apakah harga lauk sudah termasuk nasi, aturan kombinasi Budget, dan label rasa. Cara bayar tetap hanya saat ambil.

## Langkah berikutnya (hari 12 sampai 14)

Sudah selesai: tes otomatis untuk nilai batas Budget, status loncat tahap, dua tab admin, tombol kirim dua kali, dan akses admin tanpa login. Alur penuh sudah dicoba di browser.

1. Cek tampilan di lebar 360px dan di ponsel sungguhan, lalu perbaiki yang berantakan.
2. Perbaiki bug yang ditemukan saat uji.
3. Ganti kata sandi akun contoh `admin@warung.test` sebelum demo.
4. Siapkan demo atau deploy. Untuk server: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` yang benar, dan `APP_KEY` baru.

## Hal yang tidak boleh dilakukan

- Tidak mengarang menu, harga, alamat, atau kontak.
- Tidak menghitung total atau mengubah status di controller atau view.
- Tidak menambah pembayaran online, halaman yang tidak ada di SKPL, atau status baru.
- Tidak menyatakan sesuatu sudah berjalan sebelum `php artisan test` dan uji manual dilakukan.
