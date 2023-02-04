<?php

namespace Vadimon\Laravel\Kafka\Router\Tests\Unit\fixtures;

use Illuminate\Container\Container;
use Illuminate\Routing\Controller;
use RdKafka\Message as RdKafkaMessage;
use Vadimon\Laravel\Kafka\Router\Router;

class TestControllerWithMiddleware extends Controller
{
    public function __construct(Container $container)
    {
        $this->middleware(function (RdKafkaMessage $message, callable $next) use ($container) {
            $container->instance('testMiddlewareExecuted', true);
            $next($message);
        });
    }

    public function testMethod(Container $container, RdKafkaMessage $message, Router $router)
    {
        $container->instance('testRdKafkaMessage', $message);
        $container->instance('testKafkaRouter', $router);
    }
}
