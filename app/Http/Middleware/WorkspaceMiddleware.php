<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Workspace;

class WorkspaceMiddleware
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
        $workspaceSlug = $request->route('workspace');

        $isApiRequest = $request->expectsJson() || $request->is('api/*');

        if (!$workspaceSlug) {
            if ($isApiRequest) {
                return response()->json(['message' => 'Workspace not found'], 404);
            }
            abort(404, 'Workspace not found');
        }

        // Get workspace
        $workspace = Workspace::where('slug', $workspaceSlug)->first();

        if (!$workspace) {
            if ($isApiRequest) {
                return response()->json(['message' => 'Workspace not found'], 404);
            }
            abort(404, 'Workspace not found');
        }

        // Check if user is authenticated
        if (!Auth::check()) {
            if ($isApiRequest) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('workspace.login', ['workspace' => $workspaceSlug]);
        }

        // Check if user belongs to this workspace
        if (Auth::user()->workspace_id !== $workspace->id) {
            if ($isApiRequest) {
                return response()->json(['message' => 'You do not have access to this workspace'], 403);
            }
            abort(403, 'Anda tidak memiliki akses ke workspace ini');
        }

        // Set workspace in request for easy access
        $request->merge(['workspace' => $workspace]);
        
        // Only share with views for web requests
        if (!$isApiRequest) {
            view()->share('workspace', $workspace);
        }

        return $next($request);
    }
}

