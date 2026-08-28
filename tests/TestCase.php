<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PHPUnit\Framework\AssertionFailedError;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        $this->guardAgainstDestructiveDatabase();

        parent::setUp();
    }

    /**
     * Block tests from running migrate:fresh against a non-test database.
     */
    private function guardAgainstDestructiveDatabase(): void
    {
        $database = $_ENV['DB_DATABASE']
            ?? $_SERVER['DB_DATABASE']
            ?? getenv('DB_DATABASE')
            ?: null;

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
