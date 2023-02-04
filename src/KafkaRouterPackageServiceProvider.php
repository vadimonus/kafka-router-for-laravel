<?php

namespace Vadimon\Laravel\Kafka\Router;

use Illuminate\Support\ServiceProvider;

class KafkaRouterPackageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../stubs/KafkaRouterServiceProvider.php' => app_path('Providers/KafkaRouterServiceProvider.php'),
                __DIR__.'/../stubs/kafka.php' => base_path('routes/kafka.php'),
            ]);
        }
    }
}
