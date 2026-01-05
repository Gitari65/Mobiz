<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Safety check: abort tests if DB connection is not sqlite in-memory.
        $conn = config('database.default');
        $db = config("database.connections.{$conn}.database");

        if ($conn !== 'sqlite' && $db !== ':memory:') {
            $msg = "Aborting tests: DB connection is '{$conn}' (database: '{$db}').\n";
            $msg .= "Tests must run using SQLite (in-memory) to avoid modifying your local MySQL.\n";
            $msg .= "Run: composer test:safe  (or: php artisan test --env=testing)\n";
            fwrite(STDERR, $msg);
            throw new \RuntimeException($msg);
        }
    }
}
