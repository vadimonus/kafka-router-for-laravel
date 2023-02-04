<?php

namespace Vadimon\Laravel\Kafka\Router\Tests\Unit;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use RdKafka\Message as RdKafkaMessage;
use Vadimon\Laravel\Kafka\Router\Route;
use Vadimon\Laravel\Kafka\Router\Router;

class RouterTest extends TestCase
{
    public function testOnlyTopic()
    {
        $container = new Container();
        $expectedRoute = (new Route('*', 'testTopic', [
            'uses' => 'expectedClass@expectedMethod',
        ]))->setContainer($container);
        $router = new Router($container);
        $router->topic('testTopic', ['expectedClass', 'expectedMethod']);
        $this->assertEquals([$expectedRoute], $router->getRoutes()->getRoutes());
        $router = new Router($container);
        $router->topic('testTopic', 'expectedClass@expectedMethod');
        $this->assertEquals([$expectedRoute], $router->getRoutes()->getRoutes());
    }

    public function testConnections()
    {
        $container = new Container();
        $expectedRoute = (new Route('testConnection02', 'testTopic', [
            'uses' => 'expectedClass@expectedMethod',
        ]))->setContainer($container);
        $router = new Router($container);
        $router->group(['connection' => 'testConnection01'], function (Router $router) {
            $router->group(['connection' => 'testConnection02'], function (Router $router) {
                $router->topic('testTopic', ['expectedClass', 'expectedMethod']);
            });
        });
        $this->assertEquals([$expectedRoute], $router->getRoutes()->getRoutes());
        $router = new Router($container);
        $router->connection('testConnection01', function (Router $router) {
            $router->connection('testConnection02', function (Router $router) {
                $router->topic('testTopic', ['expectedClass', 'expectedMethod']);
            });
        });
        $this->assertEquals([$expectedRoute], $router->getRoutes()->getRoutes());
    }

    public function testMiddlewares()
    {
        $container = new Container();
        $expectedRoute = (new Route('*', 'testTopic', [
            'uses' => 'expectedClass@expectedMethod',
            'middleware' => [
                'testMiddleware01',
                'testMiddleware02',
            ],
        ]))->setContainer($container);
        $router = new Router($container);
        $router->group(['middleware' => 'testMiddleware01'], function (Router $router) {
            $router->group(['middleware' => 'testMiddleware02'], function (Router $router) {
                $router->topic('testTopic', ['expectedClass', 'expectedMethod']);
            });
        });
        $this->assertEquals([$expectedRoute], $router->getRoutes()->getRoutes());
        $router = new Router($container);
        $router->group(['middleware' => ['testMiddleware01', 'testMiddleware02']], function (Router $router) {
            $router->topic('testTopic', ['expectedClass', 'expectedMethod']);
        });
        $this->assertEquals([$expectedRoute], $router->getRoutes()->getRoutes());
        $router = new Router($container);
        $router->middleware('testMiddleware01', function (Router $router) {
            $router->middleware('testMiddleware02', function (Router $router) {
                $router->topic('testTopic', ['expectedClass', 'expectedMethod']);
            });
        });
        $this->assertEquals([$expectedRoute], $router->getRoutes()->getRoutes());
        $router = new Router($container);
        $router->middleware(['testMiddleware01', 'testMiddleware02'], function (Router $router) {
            $router->topic('testTopic', ['expectedClass', 'expectedMethod']);
        });
        $this->assertEquals([$expectedRoute], $router->getRoutes()->getRoutes());
        $router = new Router($container);
        $router->middleware('testMiddleware01', function (Router $router) {
            $router->topic('testTopic', ['expectedClass', 'expectedMethod'])
                ->middleware('testMiddleware02');
        });
        $this->assertEquals([$expectedRoute], $router->getRoutes()->getRoutes());
    }

    public function testDispatch()
    {
        $container = new Container();
        $router = new Router($container);
        $middlewareExecuted = false;
        $middleware = function (RdKafkaMessage $message, callable $next) use (&$middlewareExecuted) {
            $middlewareExecuted = true;
            $next($message);
        };
        $handlerExecuted = false;
        $handler = function (RdKafkaMessage $message) use (&$handlerExecuted) {
            $handlerExecuted = true;
        };
        $router->group(['middleware' => $middleware], function (Router $router) use ($handler) {
            $router->topic('testTopic', $handler);
        });
        $message = new RdKafkaMessage();
        $message->topic_name = 'testTopic';
        $router->dispatch($message);
        $this->assertTrue($middlewareExecuted);
        $this->assertTrue($handlerExecuted);
    }

    public function testUniqueMiddleware()
    {
        $container = new Container();
        $router = new Router($container);
        $middlewareExecuted = 0;
        $middleware = function (RdKafkaMessage $message, callable $next) use (&$middlewareExecuted) {
            $middlewareExecuted++;
            $next($message);
        };
        $handlerExecuted = false;
        $handler = function (RdKafkaMessage $message) use (&$handlerExecuted) {
            $handlerExecuted = true;
        };
        $router->group(['middleware' => $middleware], function (Router $router) use ($middleware, $handler) {
            $router->group(['middleware' => $middleware], function (Router $router) use ($handler) {
                $router->topic('testTopic', $handler);
            });
        });
        $message = new RdKafkaMessage();
        $message->topic_name = 'testTopic';
        $router->dispatch($message);
        $this->assertEquals(1, $middlewareExecuted);
        $this->assertTrue($handlerExecuted);
    }
}
