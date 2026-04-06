<?php

namespace LaravelUi5\OData\Tests;

use LaravelUi5\OData\ODataServiceProvider;
use Illuminate\Foundation\Testing\WithoutMiddleware;

/**
 * Base test case for all OData tests.
 *
 * Boots the ODataServiceProvider and provides the airline migrations
 * as the default database fixture.
 */
abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithoutMiddleware;

    protected $migrations = __DIR__.'/../tests-fixtures/migrations/airline';

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom($this->migrations);
    }

    protected function getPackageProviders($app): array
    {
        return [
            ODataServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }
}
