<?php

declare(strict_types=1);

namespace Tecsafe\OFCP\Events;

/**
 * If a consumer returns an instance of this class,
 * the MqService will NOT acknowledge the message and requeue it.
 */
class MqServiceError
{
    /**
     * Create a new instance of the MqServiceError class.
     * @param bool $requeue Whether to requeue the message or not.
     */
    public function __construct(
        private readonly bool $requeue = true,
    ) {
    }

    /**
     * Get whether to requeue the message or not.
     * @return bool Whether to requeue the message or not.
     */
    public function getRequeue(): bool
    {
        return $this->requeue;
    }
}
