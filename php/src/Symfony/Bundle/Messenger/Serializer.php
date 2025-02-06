<?php

declare(strict_types=1);

namespace Tecsafe\OFCP\Events\Symfony\Bundle\Messenger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Tecsafe\OFCP\Events\EventMap;
use Tecsafe\OFCP\Events\OfcpEvent;

class Serializer implements SerializerInterface
{
    public function __construct(private LoggerInterface $logger)
    {}

    public function decode(array $encodedEnvelope): Envelope
    {
        $body = $encodedEnvelope['body'];
        $headers = $encodedEnvelope['headers'];

        if (false === $body) {
            throw new MessageDecodingFailedException("Empty body");
        }

        $eventName = $headers[Constants::EVENT_NAME_HEADER] ?? null;

        if ($eventName === null) {
            throw new MessageDecodingFailedException('No event name provided in headers');
        }

        try {
            $message = $this->hydrateMessage($body, $eventName);
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage(), [
                'exception' => $throwable,
                Constants::EVENT_NAME_HEADER => $eventName,
            ]);

            throw new MessageDecodingFailedException($throwable->getMessage(), $throwable->getCode(), $throwable);
        }

        $stamps = [];

        if (isset($headers['stamps'])) {
            try {
                $stamps = unserialize($headers['stamps']);
                $stamps[] = new EventNameStamp($eventName);
            } catch (\Throwable $throwable) {
                $this->logger->error($throwable->getMessage(), [
                    'exception' => $throwable,
                    Constants::EVENT_NAME_HEADER => $eventName,
                ]);
            }
        }

        return new Envelope($message, $stamps);
    }

    /**
     * @param Envelope $envelope
     * @return array
     * @throws \JsonException
     */
    public function encode(Envelope $envelope): array
    {
        $eventNameStamp = $envelope->last(EventNameStamp::class);

        if (!$eventNameStamp instanceof EventNameStamp) {
            throw new InvalidArgumentException('No event name provided');
        }

        $envelope = $envelope
            ->withoutStampsOfType(NonSendableStampInterface::class)
            ->withoutStampsOfType(ErrorDetailsStamp::class)
            ->withoutStampsOfType(EventNameStamp::class)
        ;

        $message = $envelope->getMessage();

        if (!$message instanceof OfcpEvent) {
            throw new \InvalidArgumentException(\sprintf('Unsupported message class. %s expected, but %s given.', OfcpEvent::class, $message::class));
        }

        $allStamps = [];

        foreach ($envelope->all() as $stamps) {
            if ($stamps instanceof StampInterface) {
                $allStamps[] = $stamps;
            } else {
                $allStamps = array_merge($allStamps, $stamps);
            }
        }

        return [
            'body' => json_encode($message, \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION),
            'headers' => [
                // store stamps as a header - to be read in decode()
                'stamps' => serialize($allStamps),
            ],
        ];
    }

    private function hydrateMessage(mixed $body, mixed $eventName): mixed
    {
        $eventType = EventMap::getTypeName($eventName);

        if (!is_subclass_of($eventType, OfcpEvent::class)) {
            throw new \InvalidArgumentException(\sprintf('Unsupported message class. %s expected, but %s given.', OfcpEvent::class, $eventType));
        }

        return $eventType::from_json($body);
    }
}
