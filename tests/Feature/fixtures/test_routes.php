<?php

use RdKafka\Message as RdKafkaMessage;
use Vadimon\Laravel\Kafka\Router\Router;

/**
 * @var Router $router
 */

$router->connection('*', function (Router $router) {
    $router->topic('*', function (RdKafkaMessage $message) {
        app()->instance('ProcessedRdKafkaMessage', $message);
    });
});
