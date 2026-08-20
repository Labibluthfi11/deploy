<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KoreksiAbsensi;
use Illuminate\Http\Request;

class KoreksiAbsensiController extends Controller
{
    // Admin list request
    public function index()
    {
        $requests = KoreksiAbsensi::with('user')->orderBy('created_at', 'desc')->get();
        return response()->json($requests);
    }

    // Karyawan ajukan koreksi
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_absen' => 'required|date',
            'data_koreksi' => 'required|array',
            'alasan' => 'required|string',
        ]);

        $correction = KoreksiAbsensi::create([
            'user_id' => auth()->id(),
            'tanggal_absen' => $validated['tanggal_absen'],
            'data_koreksi' => $validated['data_koreksi'],
            'alasan' => $validated['alasan'],
        ]);

        return response()->json(['message' => 'Berhasil mengajukan koreksi', 'data' => $correction], 201);
    }
}
