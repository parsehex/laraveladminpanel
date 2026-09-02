<?php

namespace Tests\Unit;

use PHPUnit\Framework\AssertionFailedError;
use ReflectionMethod;
use Tests\TestCase;

class TestDatabaseGuardTest extends TestCase
{
    public function test_guard_rejects_non_test_database_from_resolved_config(): void
    {
        config(['database.connections.pgsql.database' => 'laravel_admin']);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Refusing to run tests against database [laravel_admin]');

        (new ReflectionMethod(TestCase::class, 'guardAgainstDestructiveDatabase'))->invoke($this);
    }

    public function test_guard_allows_test_database_from_resolved_config(): void
    {
        config(['database.connections.pgsql.database' => 'laravel_admin_testing']);

        (new ReflectionMethod(TestCase::class, 'guardAgainstDestructiveDatabase'))->invoke($this);

        $this->assertTrue(true);
    }
}
