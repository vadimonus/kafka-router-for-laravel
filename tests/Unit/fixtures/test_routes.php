<?php

use Illuminate\Container\Container;
use RdKafka\Message as RdKafkaMessage;
use Vadimon\Laravel\Kafka\Router\Router;
use Vadimon\Laravel\Kafka\Router\Tests\Unit\fixtures\TestControllerWithMiddleware;
use Vadimon\Laravel\Kafka\Router\Tests\Unit\fixtures\TestControllerWithoutMiddleware;
use Vadimon\Laravel\Kafka\Router\Tests\Unit\fixtures\TestInvokableController;

/**
 * @var Router $router
 */

$router->connection('testConnection', function (Router $router) {
    $router->topic('testTopicWithMiddleware', TestControllerWithMiddleware::class . '@testMethod');
    $router->topic('testTopicWithoutMiddleware', [TestControllerWithoutMiddleware::class, 'testMethod']);
    $router->topic('testTopicInvokable', TestInvokableController::class);
    $router->topic('testTopicClosure', function (Container $container, RdKafkaMessage $message, Router $router) {
        $container->instance('testRdKafkaMessage', $message);
        $container->instance('testKafkaRouter', $router);
    });
});
