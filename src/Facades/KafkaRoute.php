<?php

namespace Vadimon\Laravel\Kafka\Router\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use RdKafka\Message as RdKafkaMessage;
use Vadimon\Laravel\Kafka\Router\Contracts\Registrar as RegistrarContract;
use Vadimon\Laravel\Kafka\Router\Route;
use Vadimon\Laravel\Kafka\Router\RouteCollection;
use Vadimon\Laravel\Kafka\Router\Router;

/**
 * Route registration
 * @method static void topic(string $topicName, Closure|array|string|callable|null $action = null)
 * @method static void group(Closure|string|array $attributes, Closure|string $routes)
 * @method static void connection(string $connectionName, Closure|string $routes)
 * @method static void middleware(Closure|array|string $middleware, Closure|string $routes)
 *
 * Message handle
 * @method static void dispatch(RdKafkaMessage $message, string $connection = 'default')
 *
 * Get current route and message
 * @method static Route|null getCurrentRoute()
 * @method static RdKafkaMessage|null getCurrentMessage()
 * @method static string|null getCurrentConnectionName()
 *
 * Get all routes
 * @method static RouteCollection getRoutes()
 *
 * @see Router
 * @see RegistrarContract
 */
class KafkaRoute extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Router::class;
    }
}
