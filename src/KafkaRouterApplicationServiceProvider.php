<?php

namespace Vadimon\Laravel\Kafka\Router;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class KafkaRouterApplicationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(Router::class);
        $this->app->afterResolving(Router::class, function () {
            $this->loadRoutes();
        });
    }

    public function provides()
    {
        return [
            Router::class,
        ];
    }

    protected function loadRoutes(): void
    {
        if (method_exists($this, 'map')) {
            $this->app->call([$this, 'map']);
        }
    }
}
