<?php

namespace Vadimon\Laravel\Kafka\Router\Tests\Unit\fixtures;

use Illuminate\Container\Container;
use RdKafka\Message as RdKafkaMessage;
use Vadimon\Laravel\Kafka\Router\Router;

class TestControllerWithoutMiddleware
{
    public function testMethod(Container $container, RdKafkaMessage $message, Router $router)
    {
        $container->instance('testRdKafkaMessage', $message);
        $container->instance('testKafkaRouter', $router);
    }
}
