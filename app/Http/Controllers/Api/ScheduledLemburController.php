<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\ScheduledLembur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class ScheduledLemburController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'tanggal_lembur' => 'required|date|after:today',
            'keterangan'     => 'required|max:500',
            'foto_bukti'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto_bukti')) {
            $fotoPath = $request->file('foto_bukti')->store('absensi_foto', 'public');
        }

        $record = ScheduledLembur::create([
            'user_id'        => Auth::id(),
            'tanggal_lembur' => $request->tanggal_lembur,
            'keterangan'     => $request->keterangan,
            'foto_bukti'     => $fotoPath,
            'status'         => 'pending',
        ]);

        $formattedDate = Carbon::parse($record->tanggal_lembur)->translatedFormat('d F Y');

        Notification::create([
            'user_id'     => Auth::id(),
            'title'       => 'Pengajuan Lembur Terjadwal',
            'message'     => "Lembur kamu pada $formattedDate berhasil dijadwalkan.",
            'type'        => 'scheduled_lembur_submitted',
            'target_page' => '/jadwal_lembur',
            'target_id'   => $record->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lembur terjadwal berhasil diajukan',
            'data'    => $record
        ]);
    }

    public function index()
    {
        $list = ScheduledLembur::where('user_id', Auth::id())
            ->orderBy('tanggal_lembur', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $list
        ]);
    }

    // Menjawab permintaan: "ada approval berkala" (Untuk Admin/HR)
    public function updateStatus(Request $request, $id)
    {
        // ✅ FIX SECURITY: Hanya Admin/HR yang boleh update status
        if (!Auth::user() || !Auth::user()->isAnyAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Hanya admin atau HR yang dapat menyetujui lembur.'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,approved,rejected'
        ]);

        $record = ScheduledLembur::findOrFail($id);
        $record->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status lembur terjadwal berhasil diupdate',
            'data'    => $record
        ]);
    }
}
