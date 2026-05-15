<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Grant access if the user has any of the listed permissions (OR), separated by "|".
     *
     * @param  string  ...$permissionPipeList  First argument may contain "a|b|c"
     */
    public function handle(Request $request, Closure $next, string ...$permissionPipeList): Response
    {
        if (! $request->user()) {
            abort(403);
        }

        $names = [];
        foreach ($permissionPipeList as $chunk) {
            foreach (array_map('trim', explode('|', $chunk)) as $name) {
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }

        $allowed = collect($names)->contains(fn (string $name) => $request->user()->can($name));

        if (! $allowed) {
            abort(403, __('This action is unauthorized.'));
        }

        return $next($request);
    }
}
