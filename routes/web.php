<?php

use App\Http\Controllers\BerandaController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PesananController;
use Illuminate\Support\Facades\Route;

// Pelanggan
Route::get('/', [BerandaController::class, 'index'])->name('beranda');
Route::get('/menu', [MenuController::class, 'index'])->name('menu');

// Budget dikerjakan di hari berikutnya (sementara "segera hadir").
Route::view('/budget', 'segera', ['judul' => 'Cari Makan Sesuai Budget'])->name('budget');

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
