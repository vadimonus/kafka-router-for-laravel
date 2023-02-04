<?php

namespace Vadimon\Laravel\Kafka\Router;

use Vadimon\Laravel\Kafka\Router\Exceptions\KafkaRouteNotFoundException;

class RouteCollection
{
    /**
     * An array of the routes keyed by method.
     *
     * @var array<Route>
     */
    protected $routes = [];

    /**
     * @var array<string,array<string,Route>>
     */
    protected $matchCache = [[]];

    public function add(Route $route): void
    {
        $this->routes[] = $route;
    }

    /**
     * The result of the route lookup is cached.
     * With web routes, each new request is handled by a new process and therefore required more complex caching
     * of route tables in bootstrap/cache/routes.php to quickly find the desired route.
     * For Kafka, the same process handles many messages from the same topic, so it is enough to store the result
     * of the route lookup in a local variable.
     *
     * @throws KafkaRouteNotFoundException
     */
    public function match(string $topicName, string $connectionName): Route
    {
        return $this->matchCache[$connectionName][$topicName]
            ?? $this->matchCache[$connectionName][$topicName] = $this->matchAgainstRoutes($topicName, $connectionName);
    }

    /**
     * @throws KafkaRouteNotFoundException
     */
    protected function matchAgainstRoutes(string $topic, string $connection): Route
    {
        $route = collect($this->routes)
            ->filter(function (Route $route) use ($connection) {
                return $route->connectionName === '*' || $route->connectionName === $connection;
            })
            ->filter(function (Route $route) use ($topic) {
                return $route->topicName === '*' || $route->topicName === $topic;
            })
            ->first();

        if (!$route) {
            throw new KafkaRouteNotFoundException($topic, $connection);
        }

        return $route;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
