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
            abort(403, __('You do not have permission to access :module.', [
                'module' => $this->permissionLabel($names),
            ]));
        }

        return $next($request);
    }

    /**
     * @param  array<int, string>  $names
     */
    private function permissionLabel(array $names): string
    {
        $labels = collect($names)
            ->map(function (string $name) {
                $module = str($name)->before('.')->replace(['_', '-'], ' ')->title()->toString();

                return $module === 'Admin' ? 'Dashboard' : $module;
            })
            ->unique()
            ->values();

        if ($labels->isEmpty()) {
            return 'this module';
        }

        if ($labels->count() === 1) {
            return $labels->first();
        }

        return $labels->slice(0, -1)->implode(', ').' or '.$labels->last();
    }
}
