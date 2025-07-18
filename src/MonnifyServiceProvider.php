<?php

namespace Monnify\MonnifyLaravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\FileStore;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Cache\Repository;
/**
 * Class MonnifyServiceProvider
 */
class MonnifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/Config/monnify.php', 'monnify'
        );

        $this->app->singleton('monnify', function($app) {
            return new Monnify(
                config('monnify.api_key'),
                config('monnify.secret_key'),
                config('monnify.environment')
            );
        });

        $this->app['config']['cache.stores.monnify_file'] = [
            'driver' => 'monnify_file',
        ];
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/monnify.php' => config_path('monnify.php'),
        ], 'config');

        // Register a custom cache store named "monnify_file"
        Cache::extend('monnify_file', function ($app) {
            $storePath = storage_path('framework/cache/monnify');

            // Ensure directory exists
            if (!is_dir($storePath)) {
                @mkdir($storePath, 0755, true);

                if (!is_dir($storePath)) {
                    throw new \RuntimeException("Unable to create monnify cache directory: $storePath");
                }
            }

            $store = new FileStore(new Filesystem, $storePath);

            return new Repository($store);
        });
    }
}