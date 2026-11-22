<?php

use App\Http\Controllers\Admin\MasukController;
use App\Http\Controllers\Admin\MenuAdminController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PesananAdminController;
use App\Http\Controllers\Admin\RingkasanController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PesananController;
use Illuminate\Support\Facades\Route;

// Pelanggan
Route::get('/', [BerandaController::class, 'index'])->name('beranda');
Route::get('/menu', [MenuController::class, 'index'])->name('menu');

// Cari Makan Sesuai Budget
Route::get('/budget', [BudgetController::class, 'index'])->name('budget');
Route::post('/budget/keranjang', [BudgetController::class, 'keranjang'])->name('budget.keranjang');

// Checkout dan pengiriman pesanan
Route::get('/checkout', [CheckoutController::class, 'tampil'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'kirim'])->name('checkout.kirim');

// Status pesanan (kode pesanan + nomor kontak, tanpa akun)
Route::get('/cek-pesanan', [PesananController::class, 'formCek'])->name('cek-pesanan');
Route::post('/cek-pesanan', [PesananController::class, 'cek'])->middleware('throttle:10,1')->name('cek-pesanan.proses');
Route::get('/pesanan/{kode}', [PesananController::class, 'tampil'])->name('pesanan.status');

// Keranjang (sesi)
Route::prefix('keranjang')->name('keranjang.')->group(function () {
    Route::post('/tambah', [KeranjangController::class, 'tambah'])->name('tambah');
    Route::patch('/{item}', [KeranjangController::class, 'ubah'])->whereNumber('item')->name('ubah');
    Route::patch('/{item}/catatan', [KeranjangController::class, 'catatan'])->whereNumber('item')->name('catatan');
    Route::delete('/{item}', [KeranjangController::class, 'hapus'])->whereNumber('item')->name('hapus');
});

// Pengelola. Rute bernama "login" dipakai middleware auth untuk mengarahkan tamu ke halaman masuk.
Route::prefix('admin')->group(function () {
    Route::get('/masuk', [MasukController::class, 'form'])->name('login');
    Route::post('/masuk', [MasukController::class, 'proses'])->middleware('throttle:5,1')->name('admin.masuk.proses');

    Route::middleware('auth')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.pesanan'))->name('admin');
        Route::post('/keluar', [MasukController::class, 'keluar'])->name('admin.keluar');

        Route::get('/pesanan', [PesananAdminController::class, 'index'])->name('admin.pesanan');
        Route::post('/pesanan/{pesanan}/status', [PesananAdminController::class, 'ubahStatus'])
            ->whereNumber('pesanan')->name('admin.pesanan.status');

        Route::get('/menu', [MenuAdminController::class, 'index'])->name('admin.menu');
        Route::get('/menu/baru', [MenuAdminController::class, 'buat'])->name('admin.menu.buat');
        Route::post('/menu', [MenuAdminController::class, 'simpan'])->name('admin.menu.simpan');
        Route::get('/menu/{item}/ubah', [MenuAdminController::class, 'ubah'])->whereNumber('item')->name('admin.menu.ubah');
        Route::put('/menu/{item}', [MenuAdminController::class, 'perbarui'])->whereNumber('item')->name('admin.menu.perbarui');
        Route::patch('/menu/{item}/tersedia', [MenuAdminController::class, 'ubahTersedia'])->whereNumber('item')->name('admin.menu.tersedia');
        Route::patch('/menu/{item}/aktif', [MenuAdminController::class, 'ubahAktif'])->whereNumber('item')->name('admin.menu.aktif');

        Route::get('/pengaturan', [PengaturanController::class, 'tampil'])->name('admin.pengaturan');
        Route::put('/pengaturan', [PengaturanController::class, 'perbarui'])->name('admin.pengaturan.perbarui');
        Route::patch('/pengaturan/pemesanan', [PengaturanController::class, 'ubahPemesanan'])->name('admin.pengaturan.pemesanan');

        Route::get('/ringkasan', [RingkasanController::class, 'tampil'])->name('admin.ringkasan');
    });
});
