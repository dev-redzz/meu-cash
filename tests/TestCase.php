<?php

namespace Tests;

use App\Support\Tenant;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app->make(Tenant::class)->set(null);

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
