<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Warung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasukController extends Controller
{
    public function form()
    {
        if (Auth::check()) {
            return redirect()->route('admin.pesanan');
        }

        return view('admin.masuk', ['warung' => Warung::pengaturan()]);
    }

    public function proses(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'email.required' => 'Email pengelola wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            // Pesan umum: tidak membocorkan apakah emailnya ada.
            return back()->withInput($request->only('email'))->with('galat', 'Email atau kata sandi salah.');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.pesanan'));
    }

    public function keluar(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('pesan', 'Anda sudah keluar.');
    }
}
