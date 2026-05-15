<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || auth()->user()->isStaff()) {
            return redirect()->route('login')->with('error', 'Access denied.');
        }

        return $next($request);
    }
}
