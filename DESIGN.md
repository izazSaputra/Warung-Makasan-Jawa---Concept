---
name: Warung Masakan Jawa
description: Website pemesanan warung masakan Jawa. Hangat, ramah, desktop dan ponsel, harga selalu jelas.
colors:
  primary: "#A8321A"
  primary-dark: "#8A1C06"
  on-primary: "#FFFFFF"
  secondary: "#2F6B3F"
  on-secondary: "#FFFFFF"
  accent: "#E8A317"
  accent-soft: "#FFDEAE"
  on-accent: "#2B1D14"
  background: "#FFF8EE"
  surface: "#FFFFFF"
  surface-variant: "#F6EBDA"
  on-surface: "#2B1D14"
  on-surface-muted: "#6B5648"
  outline: "#D9C7AE"
  error: "#B3261E"
  ok-bg: "#D8EEDC"
  ok-text: "#1F5A2E"
  reject-bg: "#FBDAD6"
  reject-text: "#8C1D18"
  neutral-bg: "#ECE6DC"
  neutral-text: "#4A3F35"
  status-waiting-bg: "#FFF1CC"
  status-waiting-text: "#7A5300"
  status-accepted-bg: "#DCEBFA"
  status-accepted-text: "#0F4C81"
  status-preparing-bg: "#FDE3D3"
  status-preparing-text: "#8A3B12"
  status-ready-bg: "#D8EEDC"
  status-ready-text: "#1F5A2E"
  status-rejected-bg: "#FBDAD6"
  status-rejected-text: "#8C1D18"
  status-done-bg: "#ECE6DC"
  status-done-text: "#4A3F35"
typography:
  display:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: 800
    lineHeight: 36px
  headline:
    fontFamily: Plus Jakarta Sans
    fontSize: 22px
    fontWeight: 700
    lineHeight: 28px
  title:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: 700
    lineHeight: 24px
  body:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: 400
    lineHeight: 24px
  price:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: 800
    lineHeight: 24px
  label:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: 600
    lineHeight: 20px
rounded:
  sm: 6px
  md: 12px
  lg: 20px
  full: 9999px
spacing:
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  page-margin: 16px
  content-max: 1200px
  touch-target: 44px
  touch-target-large: 48px
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.title}"
    rounded: "{rounded.md}"
    height: 48px
    padding: 0 24px
  button-neutral:
    backgroundColor: "{colors.surface-variant}"
    textColor: "{colors.on-surface}"
    rounded: "{rounded.md}"
    height: 44px
    padding: 0 20px
  button-outline:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.primary}"
    rounded: "{rounded.md}"
    height: 44px
  menu-card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.on-surface}"
    rounded: "{rounded.lg}"
    padding: 16px
  summary-panel:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.on-surface}"
    rounded: "{rounded.lg}"
    padding: 20px
  chip:
    backgroundColor: "{colors.surface-variant}"
    textColor: "{colors.on-surface}"
    typography: "{typography.label}"
    rounded: "{rounded.full}"
    height: 36px
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.on-surface}"
    rounded: "{rounded.md}"
    height: 48px
---

# Design System: Website Pemesanan Warung Masakan Jawa (v2)

Versi ini menggantikan `DESIGN.md` pertama dan disesuaikan dengan tampilan yang benar-benar dibangun (file `public/css/warung.css`) serta cakupan akhir 10 halaman. Nama usaha masih berupa nama kerja ("Warung Masakan Jawa") dan belum dikonfirmasi mitra. Jangan membuat logo. Palet adalah usulan tim dan bisa diganti setelah mitra menentukan identitasnya. Semua menu, harga, alamat, dan kontak di desain adalah data contoh.

## Overview

Tampilan terasa seperti warung yang hangat dan ramah, bukan aplikasi restoran mewah. Layar bersih, tulisan besar, dan harga langsung terbaca. Nuansa Jawa hadir lewat warna tanah dan rempah (merah sambal, hijau daun pisang, kuning kunyit), bukan ornamen berat.

Prioritas: (1) harga dan total selalu jelas, (2) fitur Cari Makan Sesuai Budget menonjol, (3) status pesanan mudah dipahami tanpa menebak, (4) halaman pengelola cepat dipakai dengan sedikit langkah.

## Colors

- **Primary (#A8321A), merah sambal:** aksi utama (Lanjut ke Checkout, Kirim Pesanan, Cari), tautan, dan penekanan harga total. Hover memakai primary-dark (#8A1C06).
- **Secondary (#2F6B3F), hijau daun pisang:** tanda tersedia dan elemen pendukung.
- **Accent (#E8A317) dan accent-soft (#FFDEAE):** sorotan seperti kartu fitur Budget. Teks di atasnya selalu gelap.
- **Background (#FFF8EE):** latar halaman. **Surface (#FFFFFF):** kartu dan formulir. **Surface-variant (#F6EBDA):** chip, tombol netral, dan area yang dikelompokkan.
- **On-surface (#2B1D14)** untuk teks utama, **on-surface-muted (#6B5648)** untuk keterangan, **outline (#D9C7AE)** untuk garis tipis.
- **Error (#B3261E)** untuk pesan kesalahan.

Warna status pesanan memakai latar muda dengan teks gelap: Menunggu konfirmasi (kuning), Diterima (biru), Disiapkan (oranye), Siap diambil (hijau), Ditolak (merah), Selesai (abu hangat). Status **tidak boleh dibedakan hanya dengan warna**: selalu ada ikon dan tulisan. Kontras teks minimal 4,5:1.

## Typography

Satu keluarga sans-serif, Plus Jakarta Sans (dimuat dari Google Fonts) dengan cadangan font sistem. Teks isi 16px. Judul halaman 28px (24px di ponsel), judul bagian 22px, nama menu 18px, label dan chip 14px. Harga memakai bobot 800 dengan format rupiah titik pemisah ribuan tanpa spasi, misalnya **Rp12.000**.

## Layout

Konten dibatasi lebar 1200px dan berada di tengah. Pelanggan memakai navigasi atas (Beranda, Menu, Budget, Cek Pesanan) dengan tombol keranjang berisi jumlah item. Header menempel di atas saat menggulir. Tanpa ikon profil karena pelanggan tidak punya akun.

Breakpoint: 1100px (grid menu 3 menjadi 2 kolom), 900px (panel samping pindah ke bawah, semua grid menjadi satu kolom), 720px (navigasi bisa digeser mendatar, judul mengecil). Ponsel dijamin nyaman mulai lebar 360px. Margin halaman dan jarak dalam kartu 16px, jarak antar-bagian 24px.

Pada Menu dan Checkout, panel ringkasan/keranjang berada di kolom kanan (340px dan 400px) dan menempel saat menggulir di layar lebar.

## Elevation & Depth

Datar dan ringan. Kartu memakai batas tipis warna outline tanpa bayangan tebal atau efek kaca.

## Shapes

Sudut membulat ramah: kartu 20px, tombol dan input 12px, chip dan badge status berbentuk pil penuh.

## Components

- **Tombol:** tinggi minimal 44px, tombol aksi utama besar 48px. Satu tombol merah penuh per layar untuk aksi utama. Tombol "Tambah" di kartu menu bergaya netral (surface-variant), bukan merah. Tombol nonaktif dipudarkan dan diberi tulisan alasannya (Habis, Tutup).
- **Kartu menu:** foto opsional (tanpa foto: blok krem dengan ikon piring), nama, keterangan, lencana label (Pedas berlatar merah muda, Tidak pedas berlatar hijau muda, Habis berlatar abu), harga, dan tombol Tambah. Menu habis dipudarkan, tanpa harga dicoret.
- **Chip:** penyaring jenis dan rasa, tinggi 36px. Chip aktif memakai primary dengan teks putih.
- **Panel keranjang/ringkasan:** daftar item dengan pemilih jumlah (tombol minus dan plus 44px), tombol Hapus, total tebal berwarna primary, dan catatan bahwa total final dihitung ulang saat pesanan dikirim.
- **Formulir:** label selalu di atas kolom, tinggi input 48px, pesan kesalahan di bawah kolom, bantuan singkat berwarna muted. Pilihan waktu ambil berupa kartu radio berukuran 48px.
- **Badge status pesanan:** pil dengan ikon, tulisan, dan warna status. Daftar tahap vertikal memakai tanda centang (selesai), titik (sekarang), dan kosong (belum), selalu disertai tulisan.
- **Banner:** hijau untuk pemesanan dibuka, merah muda untuk ditutup, kuning untuk peringatan seperti "Pesanan belum pasti sampai dikonfirmasi warung".

## Screens (cakupan akhir: 10 halaman)

Pelanggan:
1. **Beranda:** banner buka/tutup, hero dengan tombol "Lihat Menu", kartu besar "Makan Enak Sesuai Budget", info warung, dan pratinjau 4 menu.
2. **Menu:** pencarian, chip jenis (Semua, Paket, Nasi, Lauk, Sayur, Tambahan, Minuman), chip rasa, grid menu, dan panel keranjang di kanan.
3. **Cari Makan Sesuai Budget:** kolom nominal rupiah, tombol cepat, pilihan jenis hasil (Makanan lengkap, Lauk saja, Termasuk minuman), preferensi opsional, lalu daftar kombinasi dengan rincian harga dan total. Keadaan kosong: "Budget belum cukup. Pilihan termurah: ...".
4. **Checkout:** form pemesan dan waktu ambil di kiri, ringkasan yang bisa diubah di kanan (keranjang digabung ke sini, tidak ada halaman Keranjang terpisah), tombol Kirim Pesanan.
5. **Status Pesanan:** kode pesanan, badge status, daftar tahap, rincian item, dan alasan penolakan bila ada. Halaman Cek Pesanan (kode + nomor kontak) menjadi pintu masuknya.

Pengelola (satu peran; sidebar kiri di desktop, ringkas di ponsel):
6. **Masuk:** nama pengguna dan kata sandi.
7. **Kelola Pesanan:** daftar per status, aksi Terima, Tolak (alasan wajib), Mulai Siapkan, Tandai Siap Diambil, dan Tandai Selesai.
8. **Kelola Menu:** daftar item, formulir tambah/ubah, saklar Tersedia/Habis berisi tulisan, dan saklar Aktif.
9. **Pengaturan Warung:** info warung dan saklar buka/tutup pemesanan.
10. **Ringkasan:** jumlah pesanan selesai dan item terlaris per rentang tanggal, dengan catatan bahwa data hanya pesanan website.

Ditunda: halaman Riwayat dan Pesan Lagi (opsional di SKPL).

## Bahasa dan Nada Teks

Semua teks antarmuka dalam bahasa Indonesia, singkat, ramah, dan menyebut langkah berikutnya. Contoh: "Pesanan terkirim. Belum pasti sampai dikonfirmasi warung." Jangan menulis kesan pesanan sudah pasti sebelum diterima pengelola. Waktu ambil selalu disebut "diminta" sampai pesanan diterima.

## Do's and Don'ts

- Lakukan: tampilkan harga dan total di setiap tahap, pakai ikon dan teks untuk status, pertahankan tombol besar dan jarak lega.
- Lakukan: pakai warna primary hanya untuk aksi utama agar tidak ada dua tombol merah yang bersaing dalam satu layar.
- Jangan: mengarang menu, harga, nomor telepon, alamat, foto, pajak, biaya kemasan, atau klaim seperti "halal" dan "terlaris".
- Jangan: menampilkan pembayaran online (QRIS, kartu, e-wallet). Pembayaran hanya saat mengambil pesanan.
- Jangan: menampilkan nomor kontak pelanggan di layar publik.
- Jangan: membuat status "Dibatalkan". Status yang sah hanya Menunggu konfirmasi, Diterima, Disiapkan, Siap diambil, Selesai, dan Ditolak (wajib beralasan).
- Jangan: gradien mencolok, animasi berlebihan, atau teks isi di bawah 16px.
- Jangan: membedakan status hanya dengan warna.
