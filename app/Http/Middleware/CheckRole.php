<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Grant access if the user has any of the listed roles (OR), separated by "|".
     */
    public function handle(Request $request, Closure $next, string ...$rolePipeList): Response
    {
        if (! $request->user()) {
            abort(403);
        }

        $names = [];
        foreach ($rolePipeList as $chunk) {
            foreach (array_map('trim', explode('|', $chunk)) as $name) {
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }

        $allowed = collect($names)->contains(fn (string $name) => $request->user()->hasRole($name));

        if (! $allowed) {
            abort(403, __('This action is unauthorized.'));
        }

        return $next($request);
    }
}
