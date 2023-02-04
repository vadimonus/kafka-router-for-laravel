<?php

namespace Vadimon\Laravel\Kafka\Router\Tests\Unit;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use RdKafka\Message as RdKafkaMessage;
use Vadimon\Laravel\Kafka\Router\Route;
use Vadimon\Laravel\Kafka\Router\Router;
use Vadimon\Laravel\Kafka\Router\Tests\Unit\fixtures\TestControllerWithMiddleware;
use Vadimon\Laravel\Kafka\Router\Tests\Unit\fixtures\TestControllerWithoutMiddleware;
use Vadimon\Laravel\Kafka\Router\Tests\Unit\fixtures\TestInvokableController;

class RouterWithControllerTest extends TestCase
{
    public function testWithMiddleware()
    {
        $container = new Container();
        $expectedRoute = (new Route('testConnection', 'testTopicWithMiddleware', [
            'uses' => TestControllerWithMiddleware::class . '@testMethod',
        ]))->setContainer($container);
        $router = new Router($container);
        $router->group([], __DIR__ . '/fixtures/test_routes.php');
        $this->assertNotEmpty(
            array_filter(
                $router->getRoutes()->getRoutes(),
                function ($route) use ($expectedRoute) {
                    return $route == $expectedRoute;
                }
            )
        );
        $message = new RdKafkaMessage();
        $message->topic_name = 'testTopicWithMiddleware';
        $container->instance(Container::class, $container);
        $container->instance(Router::class, $router);
        $router->dispatch($message, 'testConnection');
        $this->assertTrue($container->bound('testMiddlewareExecuted'));
        /** @var Router $routerReceivedByController */
        $routerReceivedByController = $container->make('testKafkaRouter');
        $this->assertEquals($router, $routerReceivedByController);
        $this->assertEquals('testConnection', $routerReceivedByController->getCurrentConnectionName());
        $this->assertEquals($message, $routerReceivedByController->getCurrentMessage());
        $this->assertEquals($expectedRoute->action, $routerReceivedByController->getCurrentRoute()->action);
    }

    public function testWithoutMiddleware()
    {
        $container = new Container();
        $expectedRoute = (new Route('testConnection', 'testTopicWithoutMiddleware', [
            'uses' => TestControllerWithoutMiddleware::class . '@testMethod',
        ]))->setContainer($container);
        $router = new Router($container);
        $router->group([], __DIR__ . '/fixtures/test_routes.php');
        $this->assertNotEmpty(
            array_filter(
                $router->getRoutes()->getRoutes(),
                function ($route) use ($expectedRoute) {
                    return $route == $expectedRoute;
                }
            )
        );
        $message = new RdKafkaMessage();
        $message->topic_name = 'testTopicWithoutMiddleware';
        $container->instance(Container::class, $container);
        $container->instance(Router::class, $router);
        $router->dispatch($message, 'testConnection');
        $this->assertFalse($container->bound('testMiddlewareExecuted'));
        /** @var Router $routerReceivedByController */
        $routerReceivedByController = $container->make('testKafkaRouter');
        $this->assertEquals($router, $routerReceivedByController);
        $this->assertEquals('testConnection', $routerReceivedByController->getCurrentConnectionName());
        $this->assertEquals($message, $routerReceivedByController->getCurrentMessage());
        $this->assertEquals($expectedRoute->action, $routerReceivedByController->getCurrentRoute()->action);
    }

    public function testInvokable()
    {
        $container = new Container();
        $expectedRoute = (new Route('testConnection', 'testTopicInvokable', [
            'uses' => TestInvokableController::class . '@__invoke',
        ]))->setContainer($container);
        $router = new Router($container);
        $router->group([], __DIR__ . '/fixtures/test_routes.php');
        $this->assertNotEmpty(
            array_filter(
                $router->getRoutes()->getRoutes(),
                function ($route) use ($expectedRoute) {
                    return $route == $expectedRoute;
                }
            )
        );
        $message = new RdKafkaMessage();
        $message->topic_name = 'testTopicInvokable';
        $container->instance(Container::class, $container);
        $container->instance(Router::class, $router);
        $router->dispatch($message, 'testConnection');
        $this->assertFalse($container->bound('testMiddlewareExecuted'));
        /** @var Router $routerReceivedByController */
        $routerReceivedByController = $container->make('testKafkaRouter');
        $this->assertEquals($router, $routerReceivedByController);
        $this->assertEquals('testConnection', $routerReceivedByController->getCurrentConnectionName());
        $this->assertEquals($message, $routerReceivedByController->getCurrentMessage());
        $this->assertEquals($expectedRoute->action, $routerReceivedByController->getCurrentRoute()->action);
    }

    public function testClosure()
    {
        $container = new Container();
        $router = new Router($container);
        $router->group([], __DIR__ . '/fixtures/test_routes.php');
        $message = new RdKafkaMessage();
        $message->topic_name = 'testTopicClosure';
        $container->instance(Container::class, $container);
        $container->instance(Router::class, $router);
        $router->dispatch($message, 'testConnection');
        $this->assertFalse($container->bound('testMiddlewareExecuted'));
        /** @var Router $routerReceivedByController */
        $routerReceivedByController = $container->make('testKafkaRouter');
        $this->assertEquals($router, $routerReceivedByController);
        $this->assertEquals('testConnection', $routerReceivedByController->getCurrentConnectionName());
        $this->assertEquals($message, $routerReceivedByController->getCurrentMessage());
    }
}
