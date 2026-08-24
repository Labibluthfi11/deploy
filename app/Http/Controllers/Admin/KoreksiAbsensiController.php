<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KoreksiAbsensi;
use App\Models\Absensi;
use Illuminate\Http\Request;

class KoreksiAbsensiController extends Controller
{
    // Admin list request
    public function index(Request $request)
    {
        // Cek apakah request meminta view web
        if ($request->expectsJson() || $request->wantsJson()) {
            $requests = KoreksiAbsensi::with('user')->orderBy('created_at', 'desc')->get();
            return response()->json($requests);
        }

        // Return view web dengan filter status pending di query database
        $requests = KoreksiAbsensi::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.koreksi-absensi.index', compact('requests'));
    }

    // Karyawan ajukan koreksi
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_absen' => 'required|date',
            'data_koreksi' => 'required|array',
            'alasan' => 'required|string',
        ]);

        // Cek duplikat: sudah ada koreksi pending/approved untuk tanggal ini?
        $existing = KoreksiAbsensi::where('user_id', auth()->id())
            ->where('tanggal_absen', $validated['tanggal_absen'])
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            $pesanStatus = $existing->status === 'pending'
                ? 'Anda sudah mengajukan koreksi untuk tanggal ini, harap tunggu diproses admin.'
                : 'Koreksi untuk tanggal ini sudah disetujui sebelumnya.';
            return response()->json(['message' => $pesanStatus], 409);
        }

        $correction = KoreksiAbsensi::create([
            'user_id' => auth()->id(),
            'tanggal_absen' => $validated['tanggal_absen'],
            'data_koreksi' => $validated['data_koreksi'],
            'alasan' => $validated['alasan'],
        ]);

        return response()->json(['message' => 'Berhasil mengajukan koreksi', 'data' => $correction], 201);
    }

    // Upload bukti foto
    public function uploadBukti(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $path = $request->file('file')->store('koreksi-bukti', 'public');

        return response()->json([
            'message' => 'Foto berhasil diupload',
            'path' => $path,
            'url' => asset('storage/' . $path)
        ], 200);
    }

    // Logic Approve (Lupa Absen Full Day)
    public function approve(Request $request, $id)
    {
        $koreksi = KoreksiAbsensi::findOrFail($id);
        
        if ($koreksi->status !== 'pending') {
            if ($request->wantsJson()) return response()->json(['message' => 'Request sudah diproses'], 400);
            return back()->with('error', 'Request sudah diproses');
        }

        // 1. Cek: Apakah benar-benar tidak ada data absen di hari itu?
        $absenExist = Absensi::where('user_id', $koreksi->user_id)
            ->whereDate('check_in_at', $koreksi->tanggal_absen)
            ->exists();

        if ($absenExist) {
            if ($request->wantsJson()) return response()->json(['message' => 'Data absen untuk tanggal ini sudah ada, gunakan fitur izin/telat.'], 400);
            return back()->with('error', 'Data absen untuk tanggal ini sudah ada, gunakan fitur izin/telat.');
        }

        // 2. Cek: Apakah ada bukti foto/dokumen di data_koreksi?
        if (empty($koreksi->data_koreksi['file_bukti'])) {
             if ($request->wantsJson()) return response()->json(['message' => 'Bukti foto wajib dilampirkan.'], 400);
             return back()->with('error', 'Bukti foto wajib dilampirkan.');
        }

        // 3. Buat data absensi baru (Full Day)
        $newAbsen = Absensi::create(array_merge([
            'user_id' => $koreksi->user_id,
            'status' => 'hadir', 
            'tipe' => 'normal',
            'check_in_at' => $koreksi->tanggal_absen . ' 08:00:00',
            'check_out_at' => $koreksi->tanggal_absen . ' 17:00:00',
            'status_approval' => 'approved',
            'file_bukti' => $koreksi->data_koreksi['file_bukti'],
            'keterangan_admin' => 'Koreksi Lupa Absen: ' . $koreksi->alasan,
        ]));

        // 4. Kalkulasi gaji otomatis
        $salaryData = Absensi::calculateSalary(
            0,
            'hadir',
            'normal',
            Absensi::isWeekend($koreksi->tanggal_absen),
            $newAbsen->check_in_at,
            $newAbsen->check_out_at,
            $koreksi->user->employment_type ?? 'organik'
        );

        $newAbsen->update($salaryData);

        // 5. Tandai koreksi selesai
        $koreksi->update(['status' => 'approved', 'admin_note' => $request->admin_note]);

        if ($request->wantsJson()) return response()->json(['message' => 'Absensi berhasil ditambahkan!']);
        return back()->with('success', 'Absensi berhasil ditambahkan!');
    }

    // Logic Reject
    public function reject(Request $request, $id)
    {
        $request->validate(['admin_note' => 'required|string|min:5']);

        $koreksi = KoreksiAbsensi::findOrFail($id);

        if ($koreksi->status !== 'pending') {
            if ($request->wantsJson()) return response()->json(['message' => 'Request sudah diproses'], 400);
            return back()->with('error', 'Request sudah diproses');
        }

        $koreksi->update([
            'status' => 'rejected',
            'admin_note' => $request->admin_note,
        ]);

        if ($request->wantsJson()) return response()->json(['message' => 'Koreksi absensi berhasil ditolak.']);
        return back()->with('success', 'Koreksi absensi berhasil ditolak.');
    }
}
