<?php

namespace Vadimon\Laravel\Kafka\Router;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Routing\RouteDependencyResolverTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionFunction;
use function array_merge;
use function func_get_args;
use function is_null;
use function is_string;

class Route
{
    use RouteDependencyResolverTrait;

    /**
     * @var string
     */
    public $connectionName;
    /**
     * @var string
     */
    public $topicName;
    /**
     * @var array
     */
    public $action;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var mixed
     */
    public $controller;
    /**
     * @var array|null
     */
    public $computedMiddleware;

    /**
     * @param Closure|array $action
     */
    public function __construct(string $connectionName, string $topicName, $action)
    {
        $this->connectionName = $connectionName;
        $this->topicName = $topicName;
        $this->action = $this->parseAction($action);
    }

    /**
     * @param callable|array|null $action
     */
    protected function parseAction($action): array
    {
        return RouteAction::parse($action);
    }

    /**
     * @return mixed
     */
    public function getAction(?string $key = null)
    {
        return Arr::get($this->action, $key);
    }

    /**
     * @return $this
     */
    public function setAction(array $action)
    {
        $this->action = $action;

        return $this;
    }

    public function run(): void
    {
        if ($this->isControllerAction()) {
            $this->runController();
        } else {
            $this->runCallable();
        }
    }

    /**
     * Checks whether the route's action is a controller.
     *
     * @return bool
     */
    protected function isControllerAction()
    {
        return is_string($this->action['uses']);
    }

    /**
     * Run the route action and return the response.
     *
     * @return mixed
     */
    protected function runCallable()
    {
        $callable = $this->action['uses'];

        return $callable(
            ...array_values(
                $this->resolveMethodDependencies(
                    [],
                    new ReflectionFunction($this->action['uses'])
                )
            )
        );
    }

    protected function runController(): void
    {
        $this->controllerDispatcher()->dispatch($this->getController(), $this->getControllerMethod());
    }

    /**
     * @return mixed
     */
    public function getController()
    {
        if (!$this->controller) {
            $class = $this->parseControllerCallback()[0];

            $this->controller = $this->container->make($class);
        }

        return $this->controller;
    }

    /**
     * Get the controller method used for the route.
     *
     * @return string
     */
    protected function getControllerMethod()
    {
        return $this->parseControllerCallback()[1];
    }

    /**
     * Parse the controller.
     *
     * @return array
     */
    protected function parseControllerCallback()
    {
        return Str::parseCallback($this->action['uses']);
    }

    public function gatherMiddleware(): array
    {
        return $this->computedMiddleware
            ?? $this->computedMiddleware = Router::uniqueMiddleware(
                array_merge(
                    $this->middleware(),
                    $this->controllerMiddleware()
                )
            );
    }

    /**
     * Get or set the middlewares attached to the route.
     *
     * @param array|string|null $middleware
     * @return $this|array
     */
    public function middleware($middleware = null)
    {
        if (is_null($middleware)) {
            return (array)($this->action['middleware'] ?? []);
        }

        if (is_string($middleware)) {
            $middleware = func_get_args();
        }

        $this->action['middleware'] = array_merge(
            (array)($this->action['middleware'] ?? []),
            $middleware
        );

        return $this;
    }

    /**
     * Get the middleware for the route's controller.
     *
     * @return array
     */
    public function controllerMiddleware()
    {
        if (!$this->isControllerAction()) {
            return [];
        }

        return $this->controllerDispatcher()->getMiddleware(
            $this->getController(),
            $this->getControllerMethod()
        );
    }

    public function controllerDispatcher(): ControllerDispatcher
    {
        return $this->container->make(ControllerDispatcher::class);
    }

    /**
     * @param Container $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }
}
