<?php

namespace Vadimon\Laravel\Kafka\Router;

use Illuminate\Support\Arr;
use Illuminate\Support\Reflector;
use Illuminate\Support\Str;
use UnexpectedValueException;
use function is_array;
use function is_numeric;
use function is_string;
use function method_exists;

class RouteAction
{
    /**
     * @param mixed $action
     */
    public static function parse($action): array
    {
        // If the action is already a Closure instance, we will just set that instance
        // as the "uses" property, because there is nothing else we need to do when
        // it is available. Otherwise we will need to find it in the action list.
        if (Reflector::isCallable($action, true)) {
            $action = !is_array($action) ? ['uses' => $action] : [
                'uses' => $action[0] . '@' . $action[1],
            ];
        }

        if (is_string($action['uses']) && !Str::contains($action['uses'], '@')) {
            $action['uses'] = static::makeInvokable($action['uses']);
        }

        return $action;
    }

    protected static function makeInvokable(string $action): string
    {
        if (!method_exists($action, '__invoke')) {
            // @codeCoverageIgnoreStart
            throw new UnexpectedValueException("Invalid route action: [{$action}].");
            // @codeCoverageIgnoreStart
        }

        return $action . '@__invoke';
    }
}
