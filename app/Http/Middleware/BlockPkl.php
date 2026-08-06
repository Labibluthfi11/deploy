<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockPkl
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->role === 'pkl') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }
            return redirect()->route('admin.absensi.index')
                ->with('error', '⛔ Akses ditolak. PKL tidak memiliki izin untuk halaman ini.');
        }
        return $next($request);
    }
}
