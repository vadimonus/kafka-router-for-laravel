<?php

namespace Vadimon\Laravel\Kafka\Router\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vadimon\Laravel\Kafka\Router\Exceptions\KafkaRouteNotFoundException;
use Vadimon\Laravel\Kafka\Router\Route;
use Vadimon\Laravel\Kafka\Router\RouteCollection;

class RouteCollectionTest extends TestCase
{
    public function testOk()
    {
        $routes = new RouteCollection();
        $route01 = new Route('testConnection01', 'testTopic01', ['class', 'method01']);
        $routes->add($route01);
        $route02 = new Route('*', 'testTopic02', ['class', 'expectedMethod02']);
        $routes->add($route02);
        $route03 = new Route('testConnection03', '*', ['class', 'expectedMethod03']);
        $routes->add($route03);
        $route04 = new Route('*', '*', ['class', 'expectedMethod04']);
        $routes->add($route04);
        $matched01 = $routes->match('testTopic01', 'testConnection01');
        $this->assertEquals($route01, $matched01);
        $matched02 = $routes->match('testTopic02', 'someConnection');
        $this->assertEquals($route02, $matched02);
        $matched03 = $routes->match('someTopic', 'testConnection03');
        $this->assertEquals($route03, $matched03);
        $matched04 = $routes->match('someTopic', 'someConnection');
        $this->assertEquals($route04, $matched04);
    }

    public function testRouteNotFound()
    {
        $routes = new RouteCollection();
        try {
            $routes->match('someTopic', 'someConnection');
            $this->fail('Expected exception');
        } catch (KafkaRouteNotFoundException $exception) {
            $this->assertEquals('someTopic', $exception->getTopicName());
            $this->assertEquals('someConnection', $exception->getConnectionName());
        }
    }
}
