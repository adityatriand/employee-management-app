<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                
                // If user has a workspace, redirect to workspace dashboard
                if ($user->workspace_id && $user->workspace) {
                    return redirect()->route('workspace.dashboard', ['workspace' => $user->workspace->slug]);
                }
                
                // If user doesn't have workspace yet, redirect to workspace setup
                if (!$user->workspace_id) {
                    return redirect()->route('workspace.setup');
                }
                
                // Fallback to default home
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
