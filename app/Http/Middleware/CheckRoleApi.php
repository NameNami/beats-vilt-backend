<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleApi
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$Role): Response
    {
        if(! $request->user())
        {
            redirect()->route('login');
        }

        if (!in_array($request->user()->role, $Role))
        {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
