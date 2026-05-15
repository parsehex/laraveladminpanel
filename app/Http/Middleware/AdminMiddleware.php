<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->isStaff()) {
            return redirect()->route('login')->with('error', 'Access denied. Admin privileges required.');
        }

        return $next($request);
    }
}
