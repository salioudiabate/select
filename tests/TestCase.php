<?php

declare(strict_types=1);

namespace Salioudiabate\Select\Tests;

use Illuminate\Support\Facades\View;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Salioudiabate\Select\SelectServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LivewireServiceProvider::class, SelectServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function getEnvironmentSetUp($app): void
    {
        View::addLocation(__DIR__.'/Fixtures/views');
    }
}
