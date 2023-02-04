<?php

use RdKafka\Message as RdKafkaMessage;
use Vadimon\Laravel\Kafka\Router\Facades\KafkaRoute;

/*
|--------------------------------------------------------------------------
| Kafka Routes
|--------------------------------------------------------------------------
*/

KafkaRoute::connection('*', function () {
    KafkaRoute::topic('*', function (RdKafkaMessage $message) {
        // Do something with received message
    });
});