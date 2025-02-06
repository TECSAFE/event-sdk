<?php

declare(strict_types=1);

namespace Tecsafe\OFCP\Events\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use Tecsafe\OFCP\Events\MqServiceBase;
use PHPUnit\Framework\TestCase;

#[CoversClass(MqServiceBase::class)]
class MqServiceBaseTest extends TestCase
{

    public function testConnection(): void
    {
        $host = '127.0.0.1';
        $port = 5672;
        $user = 'guest';
        $password = 'guest';
        $queueName = 'test';
        $vhost = '/';
        $exchangeName = 'test';

        $mqService = new MqServiceBase(
            host: $host,
            port: $port,
            user: $user,
            password: $password,
            queueName: $queueName,
            requeueUnhandled: false,
            vhost: $vhost,
            exchangeName: $exchangeName,
        );

        $reflection = new \ReflectionClass($mqService);
        $connectionProperty = $reflection->getProperty('connection');
        $connectionProperty->setAccessible(true);

        $this->assertNull($connectionProperty->getValue($mqService));

        $mqService->openConnection();

        $this->assertInstanceOf(\AMQPConnection::class,  $connectionProperty->getValue($mqService));
        $connection = $connectionProperty->getValue($mqService);
        $this->assertSame($host, $connection->getHost());
        $this->assertSame($port, $connection->getPort());
        $this->assertSame($vhost, $connection->getVhost());
        $this->assertSame($user, $connection->getLogin());

        $mqService->closeConnection();

        $this->assertNull($connectionProperty->getValue($mqService));
    }

    public function testPublishing(): void
    {
        $host = '127.0.0.1';
        $port = 5672;
        $user = 'guest';
        $password = 'guest';
        $queueName = 'test';
        $vhost = '/';
        $exchangeName = 'test';

        $mqService = new MqServiceBase(
            host: $host,
            port: $port,
            user: $user,
            password: $password,
            queueName: $queueName,
            requeueUnhandled: false,
            vhost: $vhost,
            exchangeName: $exchangeName,
        );

        $callback = function (string $payload) {
            return \strtoupper($payload);
        };

        $mqService->subscribe('test-event', $callback);
        $mqService->publish('test-event', 'test-payload');
    }
}
