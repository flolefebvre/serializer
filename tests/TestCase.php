<?php

namespace Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    protected function getPackageProviders($app)
    {
        return [
            'Flolefebvre\\Serializer\\ServiceProvider',
        ];
    }
}
