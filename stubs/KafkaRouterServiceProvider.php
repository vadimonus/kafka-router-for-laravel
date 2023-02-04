<?php

namespace App\Providers;

use Vadimon\Laravel\Kafka\Router\Facades\KafkaRoute;
use Vadimon\Laravel\Kafka\Router\KafkaRouterApplicationServiceProvider as BaseProvider;

class KafkaRouterServiceProvider extends BaseProvider
{
    /**
     * Registers Kafka routes
     */
    public function map(): void
    {
        KafkaRoute::group([], base_path('routes/kafka.php'));
    }
}
