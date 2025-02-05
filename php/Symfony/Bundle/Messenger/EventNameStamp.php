<?php

declare(strict_types=1);

namespace Tecsafe\OFCP\Events\Symfony\Bundle\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

class EventNameStamp implements StampInterface, \Stringable
{
    public function __construct(public string $eventName)
    {}

    public function __toString(): string
    {
        return $this->eventName;
    }
}
