<?php

declare(strict_types=1);

namespace Tecsafe\OFCP\Events;

class MqServiceBase
{
    private ?\AMQPConnection $connection = null;
    private ?\AMQPChannel $channel = null;
    private array $eventHandlers = [];
    private ?\AmqpExchange $exchange = null;

    /**
     * Create a new instance of the MqService class.
     * It will automatically connect to the AMQP server as soon as a method is called,
     * and disconnect when the object is destroyed or the closeConnection method is called.
     * If you just want to send a message, you still have to call the closeConnection method.
     *
     * @param string $host      The AMQP server hostname
     * @param int    $port      The AMQP server port
     * @param string $user      The AMQP server username
     * @param string $password  The AMQP server password
     * @param string $queueName The queue name for the service, normally the service name
     * @param string $vhost     The AMQP server vhost
     * @param string $exchangeName  The AMQP exchange name
     */
    public function __construct(
        private readonly string $host = 'localhost',
        private readonly int $port = 5672,
        private readonly string $user = 'guest',
        private readonly string $password = 'guest',
        private readonly ?string $queueName = null,
        private readonly bool $requeueUnhandled = false,
        private readonly string $vhost = '/',
        private readonly string $exchangeName = 'general',
    ) {
    }

    /**
     * Get the AMQP connection instance. If it does not exist, it will be created.
     *
     * @return \AMQPConnection The connection instance
     */
    public function openConnection(): void
    {
        if ($this->connection === null) {
            $this->connection = new \AMQPConnection([
                'host' => $this->host,
                'port' => $this->port,
                'login' => $this->user,
                'password' => $this->password,
                'vhost' => $this->vhost,
            ]);
            $this->connection->connect();
        }
    }

    /**
     * Close the AMQP connection and channel,
     * and therefore remove all subscriptions.
     */
    public function closeConnection(): void
    {
        // Close the channel first
        if ($this->channel !== null && $this->channel->isConnected()) {
            $this->channel->close();
        }

        $this->channel = null;

        // Then close the connection
        if ($this->connection !== null) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }

    /**
     * Get the AMQP channel. If it does not exist, it will be created along with the queue.
     *
     * @return \AMQPChannel The channel instance
     */
    private function getChannel(): \AMQPChannel
    {
        if ($this->channel === null) {
            $this->channel = new \AMQPChannel($this->connection);

            // Declare queue with durable flag for persistence
            if ($this->queueName !== null) {
                $queue = new \AMQPQueue($this->channel);
                $queue->setFlags(\AMQP_DURABLE);
                $queue->declareQueue();
            }

            if ($this->exchangeName !== null && $this->exchange === null) {
                $this->exchange = new \AMQPExchange($this->channel);
                $this->exchange->setName($this->exchangeName);
                $this->exchange->setFlags(\AMQP_DURABLE);
            }
        }

        return $this->channel;
    }

    /**
     * Publish a message to the service's queue, including the event name in the headers.
     *
     * @param string $eventName The event name
     * @param string $payload   The message content
     *
     * @throws \Exception If publishing fails
     */
    public function publish(string $eventName, string $payload): void
    {
        $this->openConnection();

        try {
            $channel = $this->getChannel();

            $headers = [
                'event_name' => $eventName,
            ];

            $attributes = [
                'delivery_mode' => \AMQP_DELIVERY_MODE_PERSISTENT,
                'content_type' => 'application/json',
                'timestamp' => time(),
                'application_headers' => $headers,
                'headers' => $headers,
            ];
            $this->exchange->publish($payload, $eventName, \AMQP_NOPARAM, $attributes);
        } catch (\Exception $e) {
            throw new \Exception('Failed to publish message: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Register a handler for a specific event type.
     *
     * @param string   $eventName The event name to subscribe to
     * @param callable $handler   Function to process received messages (function(string $payload))
     *
     * @throws \Exception If handler registration fails
     */
    public function subscribe(string $eventName, callable $handler): void
    {
        if (isset($this->eventHandlers[$eventName])) {
            throw new \Exception("Handler already registered for event: {$eventName}");
        }

        $this->eventHandlers[$eventName] = $handler;
    }

    /**
     * Start consuming messages and routing them to registered handlers.
     * This method will block until the channel is closed.
     *
     * @throws \Exception If consumption fails
     */
    public function startConsuming(): void
    {
        if ($this->queueName === null) {
            throw new \Exception('Queue name not set');
        }
        if (empty($this->eventHandlers)) {
            throw new \Exception('No event handlers registered');
        }

        try {
            $channel = $this->getChannel();

            $channel->qos(0, 1, false);

            $queue = new \AMQPQueue($channel);
            $queue->setFlags(AMQP_DURABLE);
            $queue->declareQueue();
            $queue->consume(function (\AMQPEnvelope $message, \AMQPQueue $queue): void {
                $this->handleMessage($message, $queue);
            });

        } catch (\Exception $e) {
            throw new \Exception('Failed to start consuming messages: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Handle an incoming message by routing it to the appropriate event handler.
     *
     * @param \AMQPEnvelope $message The incoming AMQP message
     */
    private function handleMessage(\AMQPEnvelope $message, \AMQPQueue $queue): void
    {
        try {
            // Get event name from message headers
            $props = $message->getHeaders();
            $headers = [];
            if (isset($props['application_headers'])) {
                $headers = $props['application_headers']->getNativeData();
            }
            $eventName = $headers['event_name'] ?? null;

            // Check if we have a handler for this event
            if ($eventName === null || !isset($this->eventHandlers[$eventName])) {
                // No handler found, reject and requeue the message
                $queue->reject($message->getDeliveryTag(), $this->requeueUnhandled ? \AMQP_REQUEUE : \AMQP_NOPARAM);

                return;
            }

            // Execute the specific event handler
            $handler = $this->eventHandlers[$eventName];
            $res = $handler($message->getBody());

            if ($res instanceof MqServiceError) {
                $queue->reject($message->getDeliveryTag(), $res->getRequeue() ? \AMQP_REQUEUE : \AMQP_NOPARAM);
            } elseif ($res !== true) {
                $queue->reject($message->getDeliveryTag(), \AMQP_REQUEUE);
            } else {
                $queue->ack($message->getDeliveryTag());
            }
        } catch (\Exception $e) {
            $queue->reject($message->getDeliveryTag(), \AMQP_REQUEUE);
            // Reject message and requeue it
            throw $e;
        }
    }

    /**
     * Destructor to ensure connections are properly closed
     */
    public function __destruct()
    {
        $this->closeConnection();
    }
}
