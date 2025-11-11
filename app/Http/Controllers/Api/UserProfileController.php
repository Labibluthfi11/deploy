<?php
// FILE: app/Http/Controllers/Api/UserProfileController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    /**
     * Mengembalikan data profil pengguna yang sedang login.
     */
    public function show()
    {
        try {
            // Mengambil objek user yang sedang terautentikasi
            $user = Auth::user();

            // Memastikan user tidak null
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            // Kembalikan hanya atribut-atribut yang spesifik dan dibutuhkan
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'id_karyawan' => $user->id_karyawan,
                'departemen' => $user->departemen,
                // Pastikan profile_photo_url sudah di-generate dengan benar
                'profile_photo_url' => $user->profile_photo_url,
            ]);

        } catch (\Exception $e) {
            // Menangkap error tak terduga
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memperbarui data profil pengguna (nama, id_karyawan, departemen, dan foto).
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'id_karyawan' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'departemen' => 'required|string|max:255',
            'profile_photo' => 'nullable|image|max:10240', // Maksimal 10MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Tangani pengunggahan foto profil
        if ($request->hasFile('profile_photo')) {
            // Hapus foto lama jika ada dan file-nya benar-benar ada
            if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Simpan foto baru di folder 'public/profile-photos'
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            // Simpan path internal ke database (misal: 'profile-photos/abc.jpg')
            $user->profile_photo_path = $path;

            // -------- Bagian Penting yang Diperbaiki --------
            // 1. Buat URL publik dari path yang sudah disimpan
            $url = Storage::disk('public')->url($path);
            // 2. Simpan URL publik ini ke atribut 'profile_photo_url' di model User
            $user->profile_photo_url = $url;
            // -----------------------------------------------
        }

        // Perbarui data pengguna yang lain
        $user->name = $request->input('name');
        $user->id_karyawan = $request->input('id_karyawan');
        $user->departemen = $request->input('departemen');
        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            // Mengembalikan objek user yang sudah diperbarui dengan URL yang benar
            'user' => $user,
        ]);
    }
}
