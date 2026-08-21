<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Salioudiabate\Select\SelectServiceProvider;

it('registers the config publish tag', function () {
    expect(ServiceProvider::pathsToPublish(SelectServiceProvider::class, 'select-config'))->not->toBeEmpty();
});

it('registers the views publish tag', function () {
    expect(ServiceProvider::pathsToPublish(SelectServiceProvider::class, 'select-views'))->not->toBeEmpty();
});

it('registers the assets publish tag', function () {
    expect(ServiceProvider::pathsToPublish(SelectServiceProvider::class, 'select-assets'))->not->toBeEmpty();
});
