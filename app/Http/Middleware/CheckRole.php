<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role  The required role (e.g., 'admin', 'lecturer')
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // 1. Ensure the user is actually logged in
        if (! $request->user()) {
            return redirect('/login');
        }

        // 2. Check if their role matches the requirement
        if ($request->user()->role !== $role) {
            // If they fail the check, kick them out with a 403 Forbidden error
            abort(403, 'Unauthorized action. You do not have permission to access this page.');
        }

        // 3. If everything is good, proceed to the controller
        return $next($request);
    }
}
