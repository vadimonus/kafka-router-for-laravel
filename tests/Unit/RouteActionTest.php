<?php

namespace Vadimon\Laravel\Kafka\Router\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vadimon\Laravel\Kafka\Router\RouteAction;

class RouteActionTest extends TestCase
{
    public function testClassAndMethodInArray()
    {
        $this->assertEquals(
            ['uses' => 'class@method'],
            RouteAction::parse(['class', 'method'])
        );
    }

    public function testClassAndMethodInString()
    {
        $this->assertEquals(
            ['uses' => 'class@method'],
            RouteAction::parse('class@method')
        );
    }

    public function testInvokableClass()
    {
        $this->assertEquals(
            ['uses' => RouteActionTestClass::class . '@__invoke'],
            RouteAction::parse(RouteActionTestClass::class)
        );
    }

    public function testClosure()
    {
        $function = function () {
        };
        $this->assertEquals(
            ['uses' => $function],
            RouteAction::parse($function)
        );
    }
}

class RouteActionTestClass
{
    public function __invoke()
    {
    }
}