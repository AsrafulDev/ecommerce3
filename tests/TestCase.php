<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Safety net: RefreshDatabase erases whatever database it points at.
        // Hard-stop the run rather than ever touch a live database again.
        $database = config('database.connections.'.config('database.default').'.database');

        if ($database !== ':memory:' && ! str_ends_with((string) $database, '_test')) {
            $this->fail("Refusing to run tests against non-test database [{$database}].");
        }
    }
}
