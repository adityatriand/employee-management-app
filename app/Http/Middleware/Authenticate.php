<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            // Check if there's a workspace in the route
            $workspaceSlug = $request->route('workspace');
            if ($workspaceSlug) {
                return route('workspace.login', ['workspace' => $workspaceSlug]);
            }
            // Otherwise redirect to landing page
            return route('welcome');
        }
    }
}
