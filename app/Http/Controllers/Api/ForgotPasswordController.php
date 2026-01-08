<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    
    public function sendOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            $email = $request->email;

            // Generate OTP 6 digit
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Hapus OTP lama (kalo ada)
            DB::table('password_resets')->where('email', $email)->delete();

            // Simpan OTP baru
            DB::table('password_resets')->insert([
                'email' => $email,
                'otp' => $otp,
                'created_at' => Carbon::now(),
            ]);

            // Kirim email (pake Mail facade Laravel)
            try {
                Mail::raw(
                    "Kode OTP reset password Anda adalah: $otp\n\nKode ini berlaku selama 10 menit.",
                    function ($message) use ($email) {
                        $message->to($email)
                                ->subject('Kode OTP Reset Password');
                    }
                );
            } catch (\Exception $e) {
                // Kalo email gagal kirim (misal belum setup SMTP), tetap return success
                // Supaya bisa testing. Di production, ini harus throw error.
                \Log::error('Failed to send OTP email: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Kode OTP telah dikirim ke email Anda.',
                // 🔥 BUAT TESTING DOANG - HAPUS DI PRODUCTION!
                'otp_debug' => $otp,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Email tidak ditemukan.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan pada server.',
                'error_debug' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Step 2: Verifikasi OTP
     */
    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
            ]);

            $email = $request->email;
            $otp = $request->otp;

            // Cari OTP di database
            $record = DB::table('password_resets')
                ->where('email', $email)
                ->where('otp', $otp)
                ->first();

            if (!$record) {
                return response()->json([
                    'message' => 'Kode OTP salah atau sudah tidak valid.',
                ], 422);
            }

            // Cek expired (10 menit)
            $createdAt = Carbon::parse($record->created_at);
            if ($createdAt->diffInMinutes(Carbon::now()) > 10) {
                DB::table('password_resets')->where('email', $email)->delete();
                return response()->json([
                    'message' => 'Kode OTP sudah kadaluarsa. Silakan minta kode baru.',
                ], 422);
            }

            return response()->json([
                'message' => 'Kode OTP valid. Silakan reset password Anda.',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan pada server.',
                'error_debug' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Step 3: Reset Password
     */
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $email = $request->email;
            $otp = $request->otp;

            // Verifikasi OTP lagi (double check)
            $record = DB::table('password_resets')
                ->where('email', $email)
                ->where('otp', $otp)
                ->first();

            if (!$record) {
                return response()->json([
                    'message' => 'Kode OTP salah atau sudah tidak valid.',
                ], 422);
            }

            // Cek expired
            $createdAt = Carbon::parse($record->created_at);
            if ($createdAt->diffInMinutes(Carbon::now()) > 10) {
                DB::table('password_resets')->where('email', $email)->delete();
                return response()->json([
                    'message' => 'Kode OTP sudah kadaluarsa.',
                ], 422);
            }

            // Update password user
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json([
                    'message' => 'User tidak ditemukan.',
                ], 404);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            // Hapus OTP setelah berhasil
            DB::table('password_resets')->where('email', $email)->delete();

            return response()->json([
                'message' => 'Password berhasil direset. Silakan login dengan password baru.',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan pada server.',
                'error_debug' => $e->getMessage(),
            ], 500);
        }
    }
}
