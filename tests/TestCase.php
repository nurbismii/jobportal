<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        // Tests must never connect to the configured HRIS server.
        config(['database.connections.mysql_hris' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        \Illuminate\Support\Facades\DB::purge('mysql_hris');
    }

    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        // Switching accounts in a test simulates a fresh login session.
        $this->app['session']->forget('password_hash_' . ($guard ?? config('auth.defaults.guard')));
        return parent::actingAs($user, $guard);
    }
}
