<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $storagePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'keystone-client-tests'.DIRECTORY_SEPARATOR.'storage';

        if (! is_dir($storagePath) && ! mkdir($storagePath, 0777, true) && ! is_dir($storagePath)) {
            throw new \RuntimeException("Unable to create test storage directory [{$storagePath}].");
        }

        $this->app->useStoragePath($storagePath);
    }
}
