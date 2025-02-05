<?php

declare(strict_types=1);

namespace Tecsafe\OFCP\Events;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class MqServiceBase
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;
    private array $eventHandlers = [];

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
     * @param string $exchange  The AMQP exchange name
     */
    public function __construct(
        private readonly string $host = 'localhost',
        private readonly int $port = 5672,
        private readonly string $user = 'guest',
        private readonly string $password = 'guest',
        private readonly ?string $queueName = null,
        private readonly bool $requeueUnhandled = false,
        private readonly string $vhost = '/',
        private readonly string $exchange = 'general',
    ) {
    }

    /**
     * Get the AMQP connection instance. If it does not exist, it will be created.
     *
     * @return AMQPStreamConnection The connection instance
     */
    public function getConnection(): AMQPStreamConnection
    {
        if ($this->connection === null) {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password,
                $this->vhost,
            );
        }

        return $this->connection;
    }

    /**
     * Close the AMQP connection and channel,
     * and therefore remove all subscriptions.
     */
    public function closeConnection(): void
    {
        // Close the channel first
        if ($this->channel !== null && $this->channel->is_open()) {
            $this->channel->close();
        }
        $this->channel = null;

        // Then close the connection
        if ($this->connection !== null) {
            $this->connection->close();
            $this->connection = null;
        }
    }

    /**
     * Get the AMQP channel. If it does not exist, it will be created along with the queue.
     *
     * @return AMQPChannel The channel instance
     */
    private function getChannel(): AMQPChannel
    {
        if ($this->channel === null) {
            $this->channel = $this->getConnection()->channel();

            // Declare queue with durable flag for persistence
            if ($this->queueName !== null) {
                $this->channel->queue_declare(
                    $this->queueName,  // queue
                    false,             // passive
                    true,              // durable
                    false,             // exclusive
                    false,              // auto_delete
                );
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
        try {
            $channel = $this->getChannel();

            $headers = new AMQPTable(['event_name' => $eventName]);

            $message = new AMQPMessage(
                $payload,
                [
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'content_type' => 'application/json',
                    'timestamp' => time(),
                    'application_headers' => $headers,
                ],
            );

            $channel->basic_publish(
                $message,
                $this->exchange,
                $eventName,
            );
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

            // Enable prefetch for better load distribution
            $channel->basic_qos(
                0,     // prefetch_size
                1,     // prefetch_count
                false,  // global
            );

            $channel->basic_consume(
                $this->queueName,  // queue
                '',                // consumer_tag
                false,             // no_local
                false,             // no_ack
                false,             // exclusive
                false,             // nowait
                function (AMQPMessage $message) {
                    $this->handleMessage($message);
                },
            );

            // Start consuming messages
            while ($channel->is_consuming()) {
                $channel->wait();
            }
        } catch (\Exception $e) {
            throw new \Exception('Failed to start consuming messages: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Handle an incoming message by routing it to the appropriate event handler.
     *
     * @param AMQPMessage $message The incoming AMQP message
     */
    private function handleMessage(AMQPMessage $message): void
    {
        try {
            // Get event name from message headers
            $props = $message->get_properties();
            $headers = [];
            if (isset($props['application_headers'])) {
                $headers = $props['application_headers']->getNativeData();
            }
            $eventName = $headers['event_name'] ?? null;

            // Check if we have a handler for this event
            if ($eventName === null || !isset($this->eventHandlers[$eventName])) {
                // No handler found, reject and requeue the message
                $message->reject($this->requeueUnhandled);

                return;
            }

            // Execute the specific event handler
            $handler = $this->eventHandlers[$eventName];
            $res = $handler($message->getBody());

            if ($res instanceof MqServiceError) {
                $message->reject($res->getRequeue());
            } elseif ($res !== true) {
                $message->reject(true);
            } else {
                $message->ack();
            }
        } catch (\Exception $e) {
            // Reject message and requeue it
            $message->reject(true);
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
