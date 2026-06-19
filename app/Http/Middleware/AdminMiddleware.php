<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'Access denied. Admin privileges required.');
        }

        $user = auth()->user();

        if ($request->routeIs('admin.profile.*')) {
            return $next($request);
        }

        if (! $user->isStaff() && $user->getAllPermissions()->isEmpty()) {
            abort(403, 'You do not have permission to access '.$this->routeLabel($request).'.');
        }

        return $next($request);
    }

    private function routeLabel(Request $request): string
    {
        $routeName = (string) $request->route()?->getName();
        $module = str($routeName)
            ->after('admin.')
            ->before('.')
            ->replace(['_', '-'], ' ')
            ->title()
            ->toString();

        return $module !== '' ? ($module === 'Dashboard' ? 'Dashboard' : $module) : 'this module';
    }
}
