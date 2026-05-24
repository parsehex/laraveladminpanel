<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (AuthorizationException $e, Request $request) {
            if ($request->routeIs('admin.*')) {
                abort(403, __('You do not have permission to access :module.', [
                    'module' => $this->routeModuleLabel($request),
                ]));
            }
        });

        $this->reportable(function (Throwable $e) {
            //
        });
    }

    private function routeModuleLabel(Request $request): string
    {
        $routeName = (string) $request->route()?->getName();
        $module = str($routeName)
            ->after('admin.')
            ->before('.')
            ->replace(['_', '-'], ' ')
            ->title()
            ->toString();

        return match ($module) {
            '' => 'this module',
            'Dashboard' => 'Dashboard',
            default => $module,
        };
    }
}
