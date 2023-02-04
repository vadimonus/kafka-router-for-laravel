<?php

namespace Vadimon\Laravel\Kafka\Router\Tests\Feature;

use Orchestra\Testbench\TestCase;
use RdKafka\Message as RdKafkaMessage;
use Vadimon\Laravel\Kafka\Router\Facades\KafkaRoute;
use Vadimon\Laravel\Kafka\Router\KafkaRouterPackageServiceProvider;
use Vadimon\Laravel\Kafka\Router\Tests\Feature\fixtures\KafkaRouterServiceProvider;

class RoutingTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            KafkaRouterPackageServiceProvider::class,
            KafkaRouterServiceProvider::class
        ];
    }

    protected function getAnnotations()
    {
        return [];
    }

    public function testRouting()
    {
        $message = new RdKafkaMessage();
        $message->topic_name = 'testTopic';
        KafkaRoute::dispatch($message);
        $processedMessage = $this->app->make('ProcessedRdKafkaMessage');
        $this->assertNotEmpty($processedMessage);
        $this->assertSame($message, $processedMessage);
    }
}