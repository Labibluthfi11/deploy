<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Ambil semua notifikasi user yang login
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Tandai notifikasi sebagai sudah dibaca
     */
    public function markAsRead($id)
    {
        // ✅ VALIDASI ID
        if (!is_numeric($id) || $id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID tidak valid'
            ], 400);
        }

        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan'
            ], 404);
        }

        // ✅ VALIDASI OWNERSHIP
        if ($notification->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sebagai dibaca'
        ]);
    }

    /**
     * ✅ FIXED: Buat notifikasi baru (KHUSUS ADMIN!)
     * 
     * Endpoint ini dipanggil dari Admin Panel (Web Laravel)
     * untuk kirim notifikasi ke user Flutter
     */
    public function store(Request $request)
    {
        // ✅ CEK APAKAH USER ADALAH ADMIN
        $user = $request->user();
        
        // ✅ VALIDASI: Hanya admin yang bisa kirim notifikasi
        if (!$user || !$this->isAdmin($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Hanya admin yang bisa mengirim notifikasi.'
            ], 403);
        }

        // ✅ VALIDASI INPUT
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string',
            'target_page' => 'nullable|string',
            'target_id' => 'nullable|integer',
        ]);

        // ✅ CREATE NOTIFICATION
        $notif = Notification::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type ?? 'info',
            'target_page' => $request->target_page ?? null,
            'target_id' => $request->target_id ?? null,
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil dikirim',
            'data' => $notif
        ], 201);
    }

    /**
     * ✅ HELPER: Cek apakah user adalah admin
     * 
     * Sesuaikan dengan struktur database lu!
     * Ada beberapa cara cek admin:
     */
    private function isAdmin($user)
    {
        // ✅ PILIH SALAH SATU SESUAI STRUKTUR DATABASE LU:

        // CARA 1: Pakai field 'role' di tabel users
        if (isset($user->role)) {
            return $user->role === 'admin';
        }

        // CARA 2: Pakai field 'is_admin' (boolean)
        if (isset($user->is_admin)) {
            return $user->is_admin === true;
        }

        // CARA 3: Cek dari email (TEMPORARY - ganti nanti!)
        // return in_array($user->email, ['admin@example.com', 'superadmin@example.com']);

        // CARA 4: Pakai Spatie Permission (kalau lu pake package ini)
        // return $user->hasRole('admin');

        // ✅ DEFAULT: Return false kalau gak ada field role
        // (Ini bikin semua user TIDAK BISA kirim notifikasi - AMAN!)
        return false;
    }
}