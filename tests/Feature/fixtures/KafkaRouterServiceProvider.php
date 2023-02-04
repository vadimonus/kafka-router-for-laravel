<?php

namespace Vadimon\Laravel\Kafka\Router\Tests\Feature\fixtures;

use Vadimon\Laravel\Kafka\Router\Facades\KafkaRoute;
use Vadimon\Laravel\Kafka\Router\KafkaRouterApplicationServiceProvider as BaseProvider;

class KafkaRouterServiceProvider extends BaseProvider
{
    /**
     * Registers Kafka routes
     */
    public function map(): void
    {
        KafkaRoute::group([], __DIR__ . '/test_routes.php');
    }
}
