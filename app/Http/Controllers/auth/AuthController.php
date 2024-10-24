<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('pages.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Mencoba autentikasi pengguna
        $user = User::where('email', $request->input('email'))->first();

        // Menggunakan fungsi attemptLogin untuk memeriksa email dan password
        if ($this->attemptLogin($user, $request->input('password'))) {
            return $this->handleUserStatus($request, $user);
        }

        // Menggunakan flash message untuk notifikasi kesalahan
        return $this->handleFailedLogin($request, 'Email atau password salah.');
    }

    private function attemptLogin(?User $user, string $password): bool
    {
        // Memeriksa apakah pengguna ada dan password cocok
        return $user && Hash::check($password, $user->password);
    }

    private function handleFailedLogin(Request $request, string $message)
    {
        // Menggunakan flash message untuk notifikasi
        return redirect()->back()->withInput()->with('notif_loginn', $message);
    }

    private function handleUserStatus(Request $request, User $user)
    {
        // Memisahkan logika penanganan pengguna berdasarkan status
        switch ($user->status) {
            case 'pending':
                return $this->handleFailedLogin($request, 'Akun Anda membutuhkan persetujuan administrator.');
            case 'rejected':
                return $this->handleFailedLogin($request, 'Akun Anda tidak disetujui, hubungi administrator.');
            case 'approved':
                Auth::login($user);
                $this->storeUserSession($user);
                return redirect()->intended('/dashboard');
            default:
                return $this->handleFailedLogin($request, 'Status akun tidak valid.');
        }
    }

    private function storeUserSession(User $user)
    {
        // Menyimpan informasi sensitif dengan enkripsi
        session([
            'user_id' => encrypt($user->id),      // Menyimpan id user
            'user_role' => encrypt($user->role),  // Menyimpan role user
            'user_status' => encrypt($user->status) // Menyimpan status user
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->flush(); // Menghapus semua data session saat logout
        return redirect('/')->with('success', 'Anda telah berhasil logout.');
    }
}
