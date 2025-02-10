<?php

declare(strict_types=1);

namespace Tecsafe\OFCP\Events\Symfony\Bundle\Messenger;

use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class EventNameMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $eventNameStamp = $envelope->last(EventNameStamp::class);

        if ($eventNameStamp instanceof EventNameStamp) {
            $envelope = $envelope->with(AmqpStamp::createWithAttributes([
                'content_type' => Constants::TRANSPORT_CONTENT_TYPE,
                'headers' => [
                    Constants::EVENT_NAME_HEADER => $eventNameStamp->eventName,
                ],
            ]));
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
