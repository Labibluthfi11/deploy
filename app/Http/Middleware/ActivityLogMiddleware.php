<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Abaikan kalau yang buka bukan admin, atau API, atau halaman log itu sendiri (biar ga infinite loop log)
        if (!auth()->check() || !method_exists(auth()->user(), 'isAnyAdmin') || !auth()->user()->isAnyAdmin() || $request->is('admin/activity-logs') || $request->ajax()) {
            return $response;
        }

        $method = $request->method();
        $path = $request->path();

        // Jangan log bagian otentikasi atau setup web
        if (str_contains($path, 'login') || str_contains($path, 'logout') || str_contains($path, 'setup')) {
            return $response;
        }
        
        // Jangan timpa log aksi Approve/Reject dan Edit Gaji yang udah spesifik & detail di Controllernya
        if ($method === 'POST' && (str_contains($path, 'approve') || str_contains($path, 'reject') || str_contains($path, 'edit-checkin'))) {
            return $response;
        }

        $actionName = "Navigasi Menu";
        $subject = "Cek Muka Halaman";
        $details = "Buka link: /" . $path;

        if ($method === 'POST') {
            $actionName = "Submit Data / Tombol";
            $subject = "Form Action";
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            $actionName = "Update / Simpan Data";
            $subject = "Form Action";
        } elseif ($method === 'DELETE') {
            $actionName = "Aksi Hapus Data";
            $subject = "Form Action";
        }

        // Kalau dia klik fitur Export Excel/PDF
        if (str_contains(strtolower($path), 'export')) {
            $actionName = "Export File Terlarang";
            $subject = "Unduh Data (Excel/PDF)";
            $details = "Admin ini mendownload file export dari: /" . $path;
        }

        \App\Models\ActivityLog::log($actionName, $subject, $details);

        return $response;
    }
}

