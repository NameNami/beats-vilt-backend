<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleWeb
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
            abort(401, 'Unauthenticated');
        }

        if (!in_array($request->user()->role, $Role))
        {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
