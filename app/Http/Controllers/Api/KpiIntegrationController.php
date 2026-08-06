<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiIntegrationController extends Controller
{
    public function getAttendanceSummary(Request $request)
    {
        // Validasi parameter bulan dan tahun (wajib), employee_id (opsional)
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
            'employee_id' => 'nullable|exists:users,id'
        ]);

        // Menggunakan kolom 'created_at' sebagai acuan tanggal absensi
        $query = Absensi::whereMonth('created_at', $request->bulan)
                        ->whereYear('created_at', $request->tahun);

        // Filter jika spesifik untuk 1 karyawan
        if ($request->filled('employee_id')) {
            $query->where('user_id', $request->employee_id);
        }

        /* 
         * Query Eloquent dengan selectRaw
         * Catatan: total_telat dihitung ketika status = "Hadir" dan late_minutes > 0
         */
        $summary = $query->select(
                'user_id',
                DB::raw('SUM(CASE WHEN status = "Hadir" THEN 1 ELSE 0 END) as total_hadir'),
                DB::raw('SUM(CASE WHEN status = "Hadir" AND late_minutes > 0 THEN 1 ELSE 0 END) as total_telat'),
                DB::raw('SUM(CASE WHEN status = "Izin" THEN 1 ELSE 0 END) as total_izin'),
                DB::raw('SUM(CASE WHEN status = "Sakit" THEN 1 ELSE 0 END) as total_sakit'),
                DB::raw('SUM(CASE WHEN status = "Alfa" THEN 1 ELSE 0 END) as total_alfa')
            )
            ->groupBy('user_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $summary
        ]);
    }

    /**
     * Menyediakan daftar karyawan aktif beserta kode profilnya untuk kebutuhan sinkronisasi KPI
     */
    public function getActiveEmployees(Request $request)
    {
        // Mengambil semua user dengan informasi profil mereka langsung dari tabel users
        $employees = \App\Models\User::select('id', 'name', 'email', 'id_karyawan as code')->get();

        return response()->json([
            'status' => 'success',
            'data' => $employees
        ]);
    }
}
