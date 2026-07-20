<?php

declare(strict_types=1);

namespace LaravelUi5\OData;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LaravelUi5\OData\Console\CacheCommand;
use LaravelUi5\OData\Console\ClearCommand;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Contracts\ReadAuthorizerInterface;
use LaravelUi5\OData\Service\AllowAllReadAuthorizer;

class ODataServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'odata');

        $this->app->singleton(ODataServiceRegistryInterface::class, fn ($app) => $app->make(
                config('odata.service_registry', ODataServiceRegistry::class)
            ),
        );

        // The read-authorization forward-exit. Default is the no-op AllowAll — OData stays
        // security-agnostic. Hosts bind their own enforcer via the config key or by rebinding.
        // bind (not singleton): the real enforcer is request-scoped (it authorizes the current
        // actor), so a fresh resolution per request avoids a stale-actor trap under Octane.
        $this->app->bind(
            ReadAuthorizerInterface::class,
            config('odata.read_authorizer', AllowAllReadAuthorizer::class),
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config.php' => config_path('odata.php')], 'config');
            $this->commands([
                CacheCommand::class,
                ClearCommand::class,
            ]);
        }

        if (config('odata.register_routes', true)) {
            Route::prefix(config('odata.prefix', 'odata'))
                 ->middleware(config('odata.middleware', []))
                 ->group(__DIR__ . '/../routes/odata.php');
        }
    }
}
