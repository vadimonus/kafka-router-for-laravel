<?php

namespace Vadimon\Laravel\Kafka\Router\Exceptions;

use Exception;

class KafkaRouteNotFoundException extends Exception
{
    /**
     * @var string
     */
    protected $topicName;
    /**
     * @var string
     */
    protected $connectionName;

    public function __construct(string $topicName, string $connectionName)
    {
        parent::__construct('KafkaRouteNotFoundException');
        $this->topicName = $topicName;
        $this->connectionName = $connectionName;
    }

    /**
     * @return string
     */
    public function getTopicName(): string
    {
        return $this->topicName;
    }

    /**
     * @return string
     */
    public function getConnectionName(): string
    {
        return $this->connectionName;
    }
}
