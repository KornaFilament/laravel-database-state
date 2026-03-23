<?php

namespace pxlrbt\LaravelDatabaseState\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use pxlrbt\LaravelDatabaseState\DatabaseStateServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            DatabaseStateServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
    }
}
