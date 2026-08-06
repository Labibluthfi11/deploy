<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IzinKeluar;
use Illuminate\Http\Request;

class IzinKeluarAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = IzinKeluar::with('user')->orderBy('waktu_keluar', 'desc');

        if ($request->has('date') && $request->date != '') {
            $query->whereDate('waktu_keluar', $request->date);
        } else {
            // Default ke hari ini jika tidak dicari secara khusus
            $query->whereDate('waktu_keluar', today());
        }

        $izins = $query->paginate(20);
        $totalIzinBerjalan = IzinKeluar::whereDate('waktu_keluar', today())->where('status_izin', 'berjalan')->count();
        $totalIzinSelesai = IzinKeluar::whereDate('waktu_keluar', today())->where('status_izin', 'selesai')->count();
        $totalMelanggar = IzinKeluar::whereDate('waktu_keluar', today())->where('is_pelanggaran', true)->count();

        return view('admin.absensi.izin-keluar.index', compact('izins', 'totalIzinBerjalan', 'totalIzinSelesai', 'totalMelanggar'));
    }
}
