<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // \Illuminate\Support\Facades\Log::info('AdminAuthMiddleware handle called.'); // Remove debug log

        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return redirect()->route('login')
                ->with('error', '管理者権限が必要です。');
        }

        return $next($request);
    }
}
