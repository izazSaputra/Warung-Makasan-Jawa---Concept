<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RingkasanService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RingkasanController extends Controller
{
    public function tampil(Request $request, RingkasanService $layanan)
    {
        $data = $request->validate([
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dari'],
        ], [
            'dari.date_format' => 'Tanggal awal tidak valid.',
            'sampai.date_format' => 'Tanggal akhir tidak valid.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        $sampai = isset($data['sampai']) ? Carbon::createFromFormat('Y-m-d', $data['sampai']) : now();
        $dari = isset($data['dari']) ? Carbon::createFromFormat('Y-m-d', $data['dari']) : $sampai->copy()->subDays(29);

        return view('admin.ringkasan', [
            'dari' => $dari,
            'sampai' => $sampai,
            'hasil' => $layanan->hitung($dari, $sampai),
        ]);
    }
}
