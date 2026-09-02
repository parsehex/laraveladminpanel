<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PHPUnit\Framework\AssertionFailedError;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        $this->refreshApplication();

        $this->guardAgainstDestructiveDatabase();

        parent::setUp();
    }

    /**
     * Block RefreshDatabase from running migrate:fresh against a non-test database.
     *
     * Checks the resolved Laravel config (not only PHPUnit env vars) so a stale
     * `bootstrap/cache/config.php` cannot silently point tests at the dev database.
     */
    private function guardAgainstDestructiveDatabase(): void
    {
        if ($this->app->configurationIsCached()) {
            throw new AssertionFailedError(
                'Refusing to run tests while configuration is cached. '.
                'Run `php artisan config:clear` first — cached config may still reference your dev database.'
            );
        }

        $connection = (string) config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($database === ':memory:') {
            return;
        }

        if (! is_string($database) || ! str_ends_with($database, '_testing')) {
            throw new AssertionFailedError(
                'Refusing to run tests against database ['.($database ?? 'unknown').']. '.
                'Tests must use a database whose name ends with "_testing" (e.g. laravel_admin_testing). '.
                'Run: composer test:db:setup'
            );
        }
    }
}
