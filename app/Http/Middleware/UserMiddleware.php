<?php

namespace App\Http\Middleware;

use App\Support\PanelRedirector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'Access denied.');
        }

        if (auth()->user()->isStaff()) {
            return redirect()->route(PanelRedirector::routeNameFor(auth()->user()));
        }

        return $next($request);
    }
}
