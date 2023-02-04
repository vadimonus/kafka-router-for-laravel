<?php

namespace Vadimon\Laravel\Kafka\Router;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;
use RdKafka\Message as RdKafkaMessage;
use Vadimon\Laravel\Kafka\Router\Contracts\Registrar as RegistrarContract;
use Vadimon\Laravel\Kafka\Router\Exceptions\KafkaRouteNotFoundException;
use function array_merge_recursive;
use function array_pop;
use function end;
use function is_string;

class Router implements RegistrarContract
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Routes collection.
     *
     * @var RouteCollection
     */
    protected $routes;

    /**
     * The currently dispatched route instance.
     *
     * @var Route|null
     */
    protected $currentRoute;

    /**
     * The message currently being dispatched.
     *
     * @var RdKafkaMessage|null
     */
    protected $currentMessage;

    /**
     * The connection name of currently dispatched message.
     *
     * @var string|null
     */
    protected $currentConnectionName;

    /**
     * The route group attribute stack.
     *
     * @var array
     */
    protected $groupStack = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->routes = new RouteCollection();
    }

    /**
     * Register a new topic with the router.
     *
     * @param Closure|array|string|callable $action
     */
    public function topic(string $topicName, $action): Route
    {
        $route = $this->createRoute($topicName, $action);
        $this->routes->add($route);
        return $route;
    }

    /**
     * @param Closure|array|string|callable $action
     */
    protected function createRoute(string $topicName, $action): Route
    {
        // If the route is routing to a controller we will parse the route action into
        // an acceptable array format before registering it and creating this route
        // instance itself. We need to build the Closure that will call this out.
        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
        }

        $route = $this->newRoute($topicName, $action);

        // If we have groups that need to be merged, we will merge them now after this
        // route has already been created and is ready to go. After we're done with
        // the merge we will be ready to return the route back out to the caller.
        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoRoute($route);
        }

        return $route;
    }

    /**
     * Determine if the action is routing to a controller.
     *
     * @param Closure|array|string|callable $action
     */
    protected function actionReferencesController($action): bool
    {
        if (!$action instanceof Closure) {
            return is_string($action) || (isset($action['uses']) && is_string($action['uses']));
        }

        return false;
    }

    /**
     * @param array|string $action
     */
    protected function convertToControllerAction($action): array
    {
        if (is_string($action)) {
            $action = ['uses' => $action];
        }

        return $action;
    }

    /**
     * @param mixed $action
     */
    protected function newRoute(string $topic, $action): Route
    {
        return (new Route($this->getLastGroupConnection(), $topic, $action))
            ->setContainer($this->container);
    }

    protected function mergeGroupAttributesIntoRoute(Route $route): void
    {
        $route->setAction($this->mergeWithLastGroup($route->getAction()));
    }

    /**
     * @param Closure|string $routes
     */
    public function connection(string $connectionName, $routes): void
    {
        $this->group(['connection' => $connectionName], $routes);
    }

    /**
     * @param array|string $middleware
     * @param Closure|string $routes
     */
    public function middleware($middleware, $routes): void
    {
        $this->group(['middleware' => $middleware], $routes);
    }

    /**
     * Create a route group with shared attributes.
     *
     * @param array $attributes
     * @param Closure|string $routes
     * @return void
     */
    public function group(array $attributes, $routes): void
    {
        $this->updateGroupStack($attributes);

        // Once we have updated the group stack, we'll load the provided routes and
        // merge in the group's attributes when the routes are created. After we
        // have created the routes, we will pop the attributes off the stack.
        $this->loadRoutes($routes);

        array_pop($this->groupStack);
    }

    /**
     * Update the group stack with the given attributes.
     *
     * @param array $attributes
     * @return void
     */
    protected function updateGroupStack(array $attributes)
    {
        if ($this->hasGroupStack()) {
            $attributes = $this->mergeWithLastGroup($attributes);
        }

        $this->groupStack[] = $attributes;
    }

    /**
     * Determine if the router currently has a group stack.
     *
     * @return bool
     */
    protected function hasGroupStack(): bool
    {
        return !empty($this->groupStack);
    }

    public function mergeWithLastGroup(array $new): array
    {
        $old = end($this->groupStack);
        unset($old['connection']);
        return array_merge_recursive($old, $new);
    }

    /**
     * @param Closure|string $routes
     */
    protected function loadRoutes($routes): void
    {
        if ($routes instanceof Closure) {
            $routes($this);
        } elseif (is_string($routes)) {
            $router = $this;
            require $routes;
        } else {
            // @codeCoverageIgnoreStart
            throw new InvalidArgumentException();
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Get connection from the last group on the stack.
     *
     * @return string
     */
    protected function getLastGroupConnection(): string
    {
        if ($this->hasGroupStack()) {
            $last = end($this->groupStack);

            return $last['connection'] ?? '*';
        }

        return '*';
    }

    /**
     * @throws KafkaRouteNotFoundException
     */
    public function dispatch(RdKafkaMessage $message, string $connectionName = 'default'): void
    {
        $this->currentMessage = $message;
        $this->currentConnectionName = $connectionName;
        $this->container->instance(RdKafkaMessage::class, $message);
        $this->dispatchToRoute($message, $connectionName);
    }

    /**
     * @throws KafkaRouteNotFoundException
     */
    protected function dispatchToRoute(RdKafkaMessage $message, string $connectionName = 'default'): void
    {
        $this->runRoute($message, $this->findRoute($message->topic_name, $connectionName));
    }

    /**
     * @throws KafkaRouteNotFoundException
     */
    protected function findRoute(string $topicName, string $connectionName = 'default'): Route
    {
        $this->currentRoute = $route = $this->routes->match($topicName, $connectionName);

        $this->container->instance(Route::class, $route);

        return $route;
    }

    protected function runRoute(RdKafkaMessage $message, Route $route): void
    {
        $this->runRouteWithinStack($route, $message);
    }

    protected function runRouteWithinStack(Route $route, RdKafkaMessage $message): void
    {
        $middleware = $route->gatherMiddleware();

        (new Pipeline($this->container))
            ->send($message)
            ->through($middleware)
            ->then(function () use ($route) {
                $route->run();
            });
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    public function getCurrentMessage(): ?RdKafkaMessage
    {
        return $this->currentMessage;
    }

    public function getCurrentConnectionName(): ?string
    {
        return $this->currentConnectionName;
    }

    public function getCurrentRoute(): ?Route
    {
        return $this->currentRoute;
    }

    /**
     * Remove any duplicate middleware from the given array.
     *
     * @param  array  $middleware
     * @return array
     */
    public static function uniqueMiddleware(array $middleware)
    {
        $seen = [];
        $result = [];

        foreach ($middleware as $value) {
            $key = \is_object($value) ? \spl_object_id($value) : $value;

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $value;
            }
        }

        return $result;
    }
}
