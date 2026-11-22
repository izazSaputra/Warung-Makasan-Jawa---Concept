# Rencana 2 Minggu: Website Pemesanan Warung Masakan Jawa

Catatan: ini usulan. Nama di tiap bagian boleh ditukar sesuai kemampuan masing-masing. Asumsi: stack Laravel, tim 3 orang (Izaz, Sultan, Leyla), target website berjalan di akhir minggu ke-2.

## 1. Cakupan final: 10 halaman

Pelanggan (5):
1. Beranda
2. Menu (ada panel keranjang di samping)
3. Cari Makan Sesuai Budget
4. Checkout (daftar item keranjang bisa diubah di ringkasan; tidak ada halaman Keranjang terpisah)
5. Status Pesanan (setelah kirim, langsung diarahkan ke sini dengan kode pesanan; tidak ada halaman Konfirmasi terpisah)

Pengelola (5):
6. Masuk
7. Kelola Pesanan
8. Kelola Menu
9. Pengaturan Warung (info + buka/tutup pemesanan)
10. Ringkasan (jumlah pesanan selesai + item terlaris per rentang tanggal)

Dibuang dulu: halaman Riwayat dan Pesan Lagi (opsional di SKPL, SKPL-F-07), halaman Keranjang, halaman Konfirmasi. Keadaan khusus (pemesanan tutup, keranjang kosong, item habis, gagal simpan) dibuat sebagai banner atau pesan biasa, bukan halaman.

## 2. Abaikan dari desain Stitch (tidak ada di SKPL)

- QRIS di mana pun. Pembayaran hanya "bayar saat ambil".
- Alamat, telepon, dan nama "Malioboro" yang dikarang. Ambil dari data Pengaturan Warung.
- Janji waktu: "estimasi verifikasi 3-5 menit", "siap santap".
- Peta, shift dapur, notifikasi suara, status "Dine-in/Terjadwal".
- Ringkasan: omzet, persentase naik, rata-rata, Unduh CSV, Cetak. Cukup jumlah selesai dan item terlaris.
- Panel anotasi Stitch ("Dokumentasi Status Sistem", "Pilih Skenario", "Simulasi", chip "Tes Rp3.000").
- Foto menu yang memuat potongan screenshot UI. Pakai blok krem + ikon piring dulu.
- Tombol "Terima & Mulai Masak". Alurnya: Terima -> Mulai Siapkan -> Siap Diambil -> Selesai.

## 3. Pembagian tugas

**Izaz: inti pesanan (paling berisiko, ambil yang paling logis)**
- Struktur database + migration + seeder data menu contoh (pakai data contoh di PROMPT_STITCH.md).
- Alur kirim pesanan: validasi di server, hitung total dari harga katalog, snapshot nama+harga, kunci anti-ganda, kode pesanan acak.
- Status pesanan: aturan transisi sah + catatan waktu tiap perubahan.
- Halaman Status Pesanan (cek dengan kode + kontak).
- Pola kode: Controller tipis -> Service -> Aturan terpisah (seperti tugas perpustakaan).

**Sultan: sisi pengelola**
- Login pengelola (satu akun, kata sandi di-hash, halaman admin dilindungi).
- Kelola Pesanan (daftar per status, Terima, Tolak dengan alasan wajib, ubah status).
- Kelola Menu (tambah/ubah, aktif/nonaktif, saklar Tersedia/Habis).
- Pengaturan Warung (info + buka/tutup pemesanan).
- Ringkasan (hitung selesai + item terlaris per rentang tanggal).
- Opsi hemat waktu: pakai panel siap pakai seperti Filament, atau Blade CRUD sederhana. Pilih salah satu di hari 1 dan jangan ganti.

**Leyla: sisi pelanggan + tampilan**
- Layout dasar dan CSS dari DESIGN.md (warna, font Plus Jakarta Sans, kartu, tombol, badge status). Ini dikerjakan paling awal karena dipakai semua orang.
- Beranda, Menu (+ panel keranjang), Checkout (form + ringkasan).
- Keranjang disimpan di sesi/browser sampai dikirim.
- Cari Makan Sesuai Budget: tampilan + logika kombinasi. Aturan disimpan di tabel terpisah (SKPL-NF-19). Izaz meninjau logikanya.
- Responsif mobile (minggu 2): top nav jadi menu ringkas, grid jadi satu kolom, panel samping pindah ke bawah, lebar mulai 360px.

## 4. Jadwal

**Hari 1-2 (semua)**
- Repo dan cabang kerja, Laravel jalan di semua laptop.
- Izaz: migration + seeder. Leyla: layout + CSS dasar. Sultan: login pengelola.
- Sepakati satu daftar data contoh (jangan beda-beda lagi).

**Hari 3-7 (alur inti)**
- Menu -> keranjang -> checkout -> kirim -> status (Leyla UI, Izaz logika).
- Kelola Pesanan dan Kelola Menu (Sultan).
- Akhir minggu 1: demo kecil. Pelanggan kirim pesanan, pengelola menerima dan mengubah status sampai Selesai.

**Hari 8-11 (fitur sisanya)**
- Budget (Leyla, ditinjau Izaz). Pengaturan dan Ringkasan (Sultan).
- Izaz: validasi item habis/harga berubah, pemesanan ditutup, pesan error berbahasa Indonesia.

**Hari 12-14 (rapikan)**
- Responsif mobile dan uji di ponsel.
- Uji nilai batas budget (Tabel 14 SKPL): kosong/0/negatif, H-1, H, H+1, item termurah habis.
- Uji alur status loncat tahap ditolak, tombol kirim ditekan dua kali, akses admin tanpa login.
- Perbaiki bug, siapkan demo.

## 5. Jika waktu mepet (urutan yang dipotong)

1. Ringkasan jadi hitungan sederhana tanpa tampilan batang.
2. Pengaturan hanya saklar buka/tutup (info warung diisi lewat seeder).
3. Budget hanya jenis "Makanan lengkap" dulu, lalu tambah "Lauk saja" dan "Termasuk minuman".
4. Foto menu tidak dipakai.

Yang tidak boleh dipotong: hitung total di server, snapshot harga, status hanya boleh maju sesuai alur, alasan tolak wajib, login pengelola.

## 6. Aturan kerja tim

- Satu fitur selesai berarti: bisa dijalankan, ada pesan error yang jelas, dan sudah dicoba orang lain di tim.
- Jangan mengubah tabel database tanpa memberi tahu Izaz. Aturan bisnis (total, status, budget) hanya boleh di Service/Aturan, bukan di tampilan.
- Hal yang masih menunggu mitra: nama resmi usaha, daftar menu dan harga asli, apakah harga lauk termasuk nasi, cara bayar, nomor telepon wajib atau tidak. Selama belum dijawab, pakai data contoh dan tandai jelas.
