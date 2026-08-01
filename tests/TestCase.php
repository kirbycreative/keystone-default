<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null)
    {
        parent::actingAs($user, $guard);

        if ($user->mfa_confirmed_at) {
            $this->withSession(['mfa_passed' => true]);
        }

        return $this;
    }

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
