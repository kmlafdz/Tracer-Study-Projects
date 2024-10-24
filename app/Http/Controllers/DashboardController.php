<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ProfilAlumni;
use App\Models\ProfilAdmin;
use App\Models\ResponKuesioner;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        // Pastikan session ada
        if (!$this->cekSessionValid($request)) {
            return redirect()->route('login')->with('error', 'Session tidak valid. Silakan login kembali.');
        }

        // Dekripsi data pengguna dari session
        $dataPengguna = $this->dekripsiDataPengguna();

        // Redirect jika status pengguna belum disetujui
        if ($dataPengguna['status'] !== 'approved') {
            return redirect()->route('login')->with('error', 'Anda belum di-approve oleh admin.');
        }

        // Ambil profil pengguna dan jumlah pengguna disetujui
        $profil = $this->ambilProfil($dataPengguna['id'], $dataPengguna['role']);
        $jumlahPenggunaDisetujui = $dataPengguna['role'] === 'admin' ? User::where('status', 'approved')->count() : null;

        // Ambil data alumni dan responden
        $alumniDanResponden = $this->ambilAlumniDanResponden();

        // Hitung persentase responden per tahun kelulusan
        $persentasePerTahun = $this->hitungPersentasePerTahun($alumniDanResponden);
        $totalAlumni = $alumniDanResponden->sum('total_alumni');
        $totalResponden = $alumniDanResponden->sum('total_responden');

        // Hitung persentase responden keseluruhan
        $persentaseRespondenKeseluruhan = $this->hitungPersentaseKeseluruhan($totalAlumni, $totalResponden);

        return view('pages.dashboard.index', [
            'profil' => $profil,
            'jumlahPenggunaDisetujui' => $jumlahPenggunaDisetujui,
            'peranPengguna' => $dataPengguna['role'],
            'persentasePerTahun' => $persentasePerTahun,
            'persentaseRespondenKeseluruhan' => $persentaseRespondenKeseluruhan,
            'totalResponden' => $totalResponden,
        ]);
    }

    private function ambilProfil($idPengguna, $peranPengguna)
    {
        $kolom = $peranPengguna === 'admin'
            ? ['nama', 'email', 'no_telepon', 'jabatan']
            : ['nama', 'tahun_lulus', 'linkedin', 'instagram', 'email', 'no_telepon'];

        return ($peranPengguna === 'admin' ? ProfilAdmin::class : ProfilAlumni::class)
            ::select($kolom)->where('user_id', $idPengguna)->firstOrFail();
    }

    private function ambilAlumniDanResponden()
    {
        return ProfilAlumni::selectRaw('profil_alumni.tahun_lulus, COUNT(profil_alumni.id) as total_alumni, COUNT(DISTINCT respon_kuesioner.user_id) as total_responden')
            ->leftJoin('respon_kuesioner', 'profil_alumni.user_id', '=', 'respon_kuesioner.user_id')
            ->groupBy('profil_alumni.tahun_lulus')
            ->get();
    }

    private function hitungPersentasePerTahun($alumniDanResponden)
    {
        return $alumniDanResponden->map(function ($data) {
            return [
                'tahun_lulus' => $data->tahun_lulus,
                'total_alumni' => $data->total_alumni,
                'total_responden' => $data->total_responden,
                'persentase' => $data->total_alumni ? ($data->total_responden / $data->total_alumni) * 100 : 0,
            ];
        });
    }

    private function hitungPersentaseKeseluruhan($totalAlumni, $totalResponden)
    {
        return $totalAlumni ? ($totalResponden / $totalAlumni) * 100 : 0;
    }

    private function cekSessionValid(Request $request)
    {
        return $request->session()->has(['user_id', 'user_role', 'user_status']);
    }

    private function dekripsiDataPengguna()
    {
        return [
            'id' => decrypt(session('user_id')),
            'role' => decrypt(session('user_role')),
            'status' => decrypt(session('user_status')),
        ];
    }
}
