# Laravel router for Kafka messages

## Introduction

This package provides a way to route Kafka messages to controllers same way as for web routes.

## Installation

Require this package with composer.

```shell
composer require vadimon/kafka-router-for-laravel
```

Install application service provider and route map file.

```shell
php artisan vendor:publish --provider="Vadimon\\Laravel\\Kafka\\Router\\KafkaRouterPackageServiceProvider"
```

Register `KafkaRouteServiceProvider` in `config/app.php`

```php
'providers' => [
    // Other Service Providers

    App\Providers\KafkaRouterServiceProvider::class,
],
```

## Usage

### Consume

This package is aimed only to route messages. You need some other solution to consume message.

### Route

Simply pass consumed message to `KafkaRoute::dispatch`, and it will be routed to controller, specified by route map.

```php
KafkaRoute::dispatch($message);
```

If you consume messages from multiple connections, you can also provide connection name.

```php
KafkaRoute::dispatch($message, $connectionName);
```

### Route map

By default, route map is defined in `routes/kafka.php`. You can override this in `KafkaRouterServiceProvider::map()`
method.

#### Topics

The handler for messages is defined by `KafkaRoute::topic` method.

```php
KafkaRoute::topic('FirstTopic', 'KafkaController@handleFirst']);
KafkaRoute::topic('SecondTopic', [KafkaController::class, 'handleSecond']);
KafkaRoute::topic('ThirdTopic', InvokableController::class);
KafkaRoute::topic('FourthTopic', function (\RdKafka\Message $message) {
    // handle message
});
KafkaRoute::topic('*', function () {
});
```

Topic names are case-sensitive. `*` mean any topic.

#### Fallback route

Routes are matched in order, in which they are provided. If you need some fallback, put handler
for `*` after all other routes
```php
// All other routes
KafkaRoute::topic('*', [KafkaController::class, 'fallbackHandler']);
```

#### Connections

You can use `KafkaRoute::connection` to specify different handlers for different connections.

```php
KafkaRoute::connection('FirstConnection', base_path('routes/kafka/first.php'));
KafkaRoute::connection('SecondConnection', function () {
    KafkaRoute::topic('FirstTopic', 'KafkaController@handleFirstTopicOfSecondConnection']);
    KafkaRoute::topic('*', 'KafkaController@handleAnyTopicOfSecondConnection']);
});
KafkaRoute::topic('ThirdTopic', 'KafkaController@handleThirdTopicOfAnyConnection']);
```

Connection names are case-sensitive. `*` mean any connection.

#### Middleware

You can assign middleware in route table
```php
// Middleware, defined as class
KafkaRoute::middleware(KaffkaMiddleware::class, function () {
    KafkaRoute::topic('*', 'KafkaController@handleMessage']);
});
// Middleware, defined as closure
$middleware = function (\RdKafka\Message $message, \Closure $next) {
    // some action
    $next($message);
}
KafkaRoute::middleware($middleware, function () {
    KafkaRoute::topic('*', 'KafkaController@handleMessage']);
});
// Multiple middlewares at once
KafkaRoute::middleware([Middleware1::class, Middleware2::class], function () {
    KafkaRoute::topic('*', 'KafkaController@handleMessage']);
});
```

#### Groups

Groups allow to specify `connection` and `middleware` in one call.
```php
KafkaRoute::group(['connection'=> 'SecondConnection', 'middleware' => KafkaMiddleware::class], function () {
    KafkaRoute::topic('*', 'KafkaController@handleMessage']);
});
```

### Controller

You can write controllers same way, you write them for HTTP requests.
The only difference - you do not need to return anything from controller.

To access message, connection name and router in controller method, you can 
use Dependency Injection or `KafkaRoute` facade.  

```php
<?php

namespace App\Kafka\Controllers;

use Illuminate\Routing\Controller;
use Vadimon\Laravel\Kafka\Router\Facades\KafkaRoute;
use Vadimon\Laravel\Kafka\Router\Router;

class KafkaController extends Controller
{
    public function handleFirstTopic(\RdKafka\Message $message, Router $router)
    {
        $currentRoute = $router->getCurrentRoute();
        $connectionName = $router->getCurrentConnectionName();
        // handle $message
    }

    public function handleSecondTopic()
    {
        $message = KafkaRoute::getCurrentMessage();
        $currentRoute = KafkaRoute::getCurrentRoute();
        $connectionName = KafkaRoute::getCurrentConnectionName();
        // handle $message
    }
}
```

### Middleware

You can write middleware same way, you write them for HTTP requests.
The only difference - you do not need to return anything from middleware.

#### Writing middleware

Closure middleware example: 

```php
function (\RdKafka\Message $message, \Closure $next) {
    // some action before routing to controller
    $next($message);
    // some action after routing to controller
}
```

Class middleware example:

```php
<?php
 
namespace App\Kafka\Middleware;
 
class KafkaMiddleware
{
    public function handle(\RdKafka\Message $request, \Closure $next)
    {
        // some action before routing to controller
        $next($request);
        // some action after routing to controller
    }
}
```

#### Assigning middleware

You can assign middleware in routing table (see above), or in controller constructor,
same way this is done in HTTP controllers.

```php
class KafkaController extends Controller
{
    public function __construct()
    {
        $this->middleware(CommonMiddleware::class);
        $this->middleware(FirstTopicMiddleware::class)->only('handleFirstTopic');
        $this->middleware(OtherTopicsMiddleware::class)->except('handleFirstTopic');
    }
    
    public function handleFirstTopic(\RdKafka\Message $message)
    {
        // handle $message
    }
}
```
