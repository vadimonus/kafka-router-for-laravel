<?php

namespace Vadimon\Laravel\Kafka\Router\Contracts;

use Closure;
use Vadimon\Laravel\Kafka\Router\Route;

interface Registrar
{
    /**
     * Register a new topic with the router.
     *
     * @param string $topicName
     * @param Closure|array|string|callable $action
     */
    public function topic(string $topicName, $action): Route;

    /**
     * Create a route group with shared attributes.
     *
     * @param Closure|string $routes
     */
    public function group(array $attributes, $routes): void;

    /**
     * Create a route group for connection.
     *
     * @param Closure|string $routes
     */
    public function connection(string $connectionName, $routes): void;

    /**
     * Create a middlewares group.
     *
     * @param Closure|array|string $middleware
     * @param Closure|string $routes
     */
    public function middleware($middleware, $routes): void;
}
