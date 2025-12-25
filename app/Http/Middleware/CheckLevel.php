<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckLevel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect('/');
        }

        if (Auth::user()->level == 1) {
            return $next($request);
        }

        // User is authenticated but not admin (level 0)
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Forbidden. Admin access required.'], 403);
        }
        return redirect('/')->with('error', 'Anda tidak memiliki akses untuk halaman ini');
    }
}

