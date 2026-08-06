<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IzinKeluar;
use App\Models\Absensi;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\ActivityLog;


class IzinKeluarApiController extends Controller
{
    public function startIzin(Request $request)
    {
        $request->validate([
            'tipe_izin' => 'required|in:mendesak,tugas_kantor',
            'tipe_durasi' => 'nullable|in:setengah_hari,satu_hari_full',
            'alasan_keluar' => 'required|string',
            'foto_surat' => 'required|image|max:2048',
        ]);

        $user = Auth::user();
        $today = Carbon::today();

        // Pastikan user sudah absen masuk hari ini
        $absensi = Absensi::where('user_id', $user->id)
            ->whereDate('check_in_at', $today)
            ->whereIn('status_approval', ['approved', 'pending'])
            ->first();

        if (!$absensi) {
            return response()->json(['message' => 'Anda harus Absen Masuk terlebih dahulu sebelum mengajukan Izin Keluar.'], 400);
        }

        if ($absensi->check_out_at) {
            return response()->json(['message' => 'Anda sudah Absen Pulang hari ini.'], 400);
        }

        // Pastikan tidak ada izin berjalan
        $activeIzin = IzinKeluar::where('user_id', $user->id)
            ->where('status_izin', 'berjalan')
            ->first();

        if ($activeIzin) {
            return response()->json(['message' => 'Anda masih memiliki sesi Izin Keluar yang belum diselesaikan.'], 400);
        }

        // Cek pelanggaran hari ini (jika sudah melanggar hari ini, tidak boleh izin lagi)
        $hasViolation = IzinKeluar::where('user_id', $user->id)
            ->whereDate('waktu_keluar', $today)
            ->where('is_pelanggaran', true)
            ->exists();

        if ($hasViolation) {
            return response()->json(['message' => 'Anda tidak bisa mengajukan Izin Keluar lagi hari ini karena telah melakukan pelanggaran sebelumnya.'], 403);
        }

        // Limit 1x per minggu (Senin - Minggu)
        $weeklyCount = IzinKeluar::where('user_id', $user->id)
            ->whereBetween('waktu_keluar', [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()])
            ->count();

        if ($weeklyCount >= 1) {
            return response()->json(['message' => 'Anda sudah mencapai batas maksimal 1x Izin Keluar minggu ini.'], 403);
        }

        // Limit 3x per bulan
        $monthlyCount = IzinKeluar::where('user_id', $user->id)
            ->whereBetween('waktu_keluar', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
            ->count();

        if ($monthlyCount >= 3) {
            return response()->json(['message' => 'Anda sudah mencapai batas maksimal 3x Izin Keluar bulan ini.'], 403);
        }

        $fotoSuratPath = $request->file('foto_surat')->store('izin_keluar', 'public');

        $izin = IzinKeluar::create([
            'user_id' => $user->id,
            'tipe_izin' => $request->tipe_izin,
            'tipe_durasi' => $request->tipe_izin === 'tugas_kantor' ? $request->tipe_durasi : null,
            'alasan_keluar' => $request->alasan_keluar,
            'foto_surat' => $fotoSuratPath,
            'waktu_keluar' => now(),
            'status_izin' => 'berjalan',
            'is_pelanggaran' => false,
        ]);

        ActivityLog::log('Mulai Izin Keluar', "User: {$user->name}", "Tipe: {$request->tipe_izin}");

        return response()->json([
            'message' => 'Berhasil memulai Izin Keluar',
            'data' => $izin
        ], 200);
    }

    public function endIzin(Request $request)
    {
        $request->validate([
            'dokumen_kembali' => 'required|image|max:2048',
            'keterangan_kembali' => 'nullable|string'
        ]);

        $user = Auth::user();
        $today = Carbon::today();

        $activeIzin = IzinKeluar::where('user_id', $user->id)
            ->where('status_izin', 'berjalan')
            ->first();

        if (!$activeIzin) {
            return response()->json(['message' => 'Tidak ada sesi Izin Keluar yang aktif.'], 404);
        }

        $waktuKembali = now();
        $dokumenPath = $request->file('dokumen_kembali')->store('izin_keluar', 'public');
        
        $diffMinutes = Carbon::parse($activeIzin->waktu_keluar)->diffInMinutes($waktuKembali);
        
        $isPelanggaran = false;

        // Cek pelanggaran mendesak > 2 jam
        if ($activeIzin->tipe_izin === 'mendesak') {
            if ($diffMinutes > 120) {
                $isPelanggaran = true;
            }
        }

        $activeIzin->update([
            'waktu_kembali' => $waktuKembali,
            'dokumen_kembali' => $dokumenPath,
            'keterangan_kembali' => $request->keterangan_kembali,
            'status_izin' => 'selesai',
            'is_pelanggaran' => $isPelanggaran
        ]);

        // 🔥 Potong gaji TIDAK lagi otomatis - keputusan final ada di tangan admin
        // lewat approve/reject di halaman approval. is_pelanggaran cuma jadi flag/petunjuk.

        ActivityLog::log('Selesai Izin Keluar', "User: {$user->name}", $isPelanggaran ? "Selesai dengan PELANGGARAN (menunggu approval admin)" : "Selesai dengan aman, menunggu approval admin");

        return response()->json([
            'message' => $isPelanggaran ? 'Izin Keluar diselesaikan, namun lewat batas maksimal 2 jam. Ini tercatat sebagai Pelanggaran.' : 'Izin Keluar diselesaikan dengan aman.',
            'data' => $activeIzin,
            'is_pelanggaran' => $isPelanggaran
        ], 200);
    }
}
