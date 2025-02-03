<?php

declare(strict_types=1);

namespace Tecsafe\OFCP\Events;

/**
 * Basic interface for OFCP-Events.
 *
 * All OFCP-Related events must implement this interface for seamless integration in php environments.
 */
interface OfcpEvent extends \JsonSerializable
{
    public static function from_json(string|array $json): self;
}
