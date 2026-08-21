<?php

declare(strict_types=1);

namespace Salioudiabate\Select;

use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class SelectServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('select')
            ->hasConfigFile('select')
            ->hasViews('select');
    }

    public function packageBooted(): void
    {
        $this->registerRoutes();
        $this->publishAssets();
    }

    private function registerRoutes(): void
    {
        if (! config('select.assets.serve', true)) {
            return;
        }

        Route::middleware('web')->group(function (): void {
            // no-cache (not no-store): the browser still validates every
            // time via the file's Last-Modified/ETag, so an edit during
            // active development is never masked by a silently-stale
            // heuristic cache — a 304 round trip is negligible either way.
            $assetHeaders = ['Cache-Control' => 'no-cache, must-revalidate', 'Pragma' => 'no-cache'];

            Route::get('/select/select.js', fn () => response()
                ->file(__DIR__.'/../resources/js/select.js', $assetHeaders + ['Content-Type' => 'application/javascript; charset=utf-8']))
                ->name('select.assets.js');

            Route::get('/select/select.css', fn () => response()
                ->file(__DIR__.'/../resources/css/select.css', $assetHeaders + ['Content-Type' => 'text/css; charset=utf-8']))
                ->name('select.assets.css');
        });
    }

    /**
     * Backs the `php artisan vendor:publish --tag=select-assets` command
     * documented in the README, for teams that want to self-host the JS/CSS
     * through their own build pipeline instead of the package serving them
     * directly (see config('select.assets.serve')).
     */
    private function publishAssets(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../resources/js/select.js' => public_path('vendor/select/select.js'),
            __DIR__.'/../resources/css/select.css' => public_path('vendor/select/select.css'),
        ], 'select-assets');
    }
}
