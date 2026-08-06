<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        try {
            $logs = ActivityLog::with('user')
                ->orderBy('created_at', 'desc')
                ->paginate(50); // Minta 50 log terbaru per halaman

            return view('admin.activity_logs.index', compact('logs'));
        } catch (\Throwable $e) {
            dd('TERJADI FATAL ERROR DI SERVER LU BANG: ' . $e->getMessage() . ' - di Baris: ' . $e->getLine() . ' - File: ' . $e->getFile());
        }
    }
}
