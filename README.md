# Event SDK

This repo contains the Event SDK which provides, typed amqp clients for OFCP services.

## Installation

**TypeScript** / **JavaScript**:

```sh
npm install @tecsafe/event-sdk
```

**PHP**:

```sh
composer require tecsafe/event-sdk
```

**JsonSchema**:

```sh
curl -O https://tecsafe.github.io/event-sdk/json-schema/latest.json
```

## Usage

Visit [https://tecsafe.github.io/event-sdk/](https://tecsafe.github.io/event-sdk/)
for a more detailed documentation.

There is also an php version of the documentation available at
[https://tecsafe.github.io/event-sdk/php](https://tecsafe.github.io/event-sdk/php).

### Typescript / Sending

```typescript
import { MqService } from '@tecsafe/event-sdk';

(async () => {
  const mqService = new MqService()
  console.log('Sending message')
  await mqService.publish("CUSTOMER_MERGE", {
    newCustomerId: '123',
    oldCustomerId: '456',
    salesChannel: '789',
    test: {foo: 'bar'}
  });
  await mqService.close();
})().then();

```

### Typescript / Receiving

```typescript
import { MqService, MqError } from '@tecsafe/event-sdk';

(async () => {
  const mqService = new MqService('amqp://localhost', 'test')
  await mqService.subscribe("CUSTOMER_MERGE", (msg) => {
    /** Do your processing here */
    return true;
  });
  await mqService.subscribe("CUSTOMER_DELETE", (msg) => {
    /** Do your processing here */
    return new MqError(false);
  });
  await mqService.startConsuming();
})().then();
```

### NestJS

```typescript
// app.module.ts
import { Logger, Module } from '@nestjs/common';
import { NestJsEventModule } from '@tecsafe/event-sdk/adapter/nestjs/dist/index';

@Module({
  imports: [
    NestJsEventModule.forRoot(
      'amqp://localhost', // connection string
      'test', // queue name (normally the service name)
      'general', // exchange name
      true, // requeue unhandled messages
      new Logger('MqService')
    ),
  ],
  providers: [],
})
export class AppModule {}
```

```typescript
// app.service.ts
import { Injectable, OnModuleInit } from '@nestjs/common';
import { MqService, createMqListener } from '@tecsafe/event-sdk';

@Injectable()
export class AppService implements onModuleInit {
  constructor(private readonly mqService: MqService) {}

  onModuleInit() {
    this.mqService.subscribe('CUSTOMER_MERGE', this.handleCustomerMerge.bind(this));
  }

  readonly handleCustomerMerge = createMqListener('CUSTOMER_MERGE', (payload) => {
    console.log('Received CUSTOMER_MERGE event', payload);
  });

  async sendCustomerMergeEvent() {
    await this.mqService.publish('CUSTOMER_MERGE', {
      newCustomerId: '123',
      oldCustomerId: '456',
      salesChannel: '789',
      test: { foo: 'bar' },
    });
  }

  async sendCustomerDeleteEvent() {
    await this.mqService.publish('CUSTOMER_DELETE', {
      customer: '123',
      salesChannel: '789',
    });
  }
}
```

### PHP / Sending

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Tecsafe\OFCP\Events\Models\MergeCustomerPayload;
use Tecsafe\OFCP\Events\Models\TestType;
use Tecsafe\OFCP\Events\MqService;

$service = new MqService();
$service->send_customer_merge(new MergeCustomerPayload(
  /** values... */
));
$service->closeConnection();
```

### PHP / Receiving

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Tecsafe\OFCP\Events\Listeners\DeleteCustomerPayloadListener;
use Tecsafe\OFCP\Events\Models\DeleteCustomerPayload;
use Tecsafe\OFCP\Events\Models\MergeCustomerPayload;
use Tecsafe\OFCP\Events\MqService;
use Tecsafe\OFCP\Events\MqServiceError;

$service = new MqService('localhost', 5672, 'guest', 'guest', 'test');

// Either use a listener class
class CustomerDeleteListener implements DeleteCustomerPayloadListener
{
  public function on_event(DeleteCustomerPayload $payload): MqServiceError | bool
  {
    /** Do your processing here */
    return new MqServiceError(false);
  }
}

$service->subscribe_customer_delete(new CustomerDeleteListener());

// Or use a callback
$service->subscribe_customer_merge(function (MergeCustomerPayload $payload) {
  return true;
});

// Start consuming, this will block the script
$service->startConsuming();
```


## Symfony Bundle

This SDK comes with a symfony bundle, that integrates into symfony messenger.
For this purpose the bundle creates a new MessageBus-instance named `ofcp_events`.
You have to use this new bus and dispatch a message with a special stamp called `EventNameStamp`.
A custom messenger-middleware and serializer are responsible for hiding implementation details.

### Install

1. Register the bundle

    ```php
    #   config/bundles.php 
    
    return [
        // ... other bundles
        Tecsafe\OFCP\Events\Symfony\Bundle\TecsafeOfcpEventsBundle::class => ['all' => true],
    ]

    ```
   
2. Configure bundle with env vars

    ```dotenv
    MESSENGER_TRANSPORT_OFCP_DSN=amqp://rabbitmq:rabbitmq@rabbitmq:5672/%2f/ofcp
    MESSENGER_TRANSPORT_OFCP_EXCHANGE_NAME=your-own-exchange
    MESSENGER_TRANSPORT_OFCP_EXCHANGE_TYPE=topic
    MESSENGER_TRANSPORT_OFCP_QUEUE_NAME=your-own-queue
    ```


### Usage

#### Dispatch message to ofcp event bus

```php
<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Messenger\MessageBusInterface;
use Tecsafe\OFCP\Events\Models\CustomerCreatedEventPayload;
use Tecsafe\OFCP\Events\Symfony\Bundle\Messenger\EventNameStamp;
use Tecsafe\OFCP\Events\EventMap;

class YourService 
{
    public function __construct(
        /**
         * With autowiring, symfony will provide the ofcp_events-bus with this variable name.
         * Otherwise inject it manually.
         */
        private MessageBusInterface $ofcpEvents,
    ) {
        parent::__construct();
    }

    public function yourMethod()
    {
        $this->ofcpEvents->dispatch(new CustomerCreatedEventPayload('foo', 'bar'), [
            new EventNameStamp(EventMap::CUSTOMER_CREATED['name'])
        ]);
    }
}
```

#### Handle message from ofcp event bus

```php
<?php

declare(strict_types=1);

namespace App\Messenger\MessageHandler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tecsafe\OFCP\Events\Models\CustomerCreatedEventPayload;
use Tecsafe\OFCP\Events\Symfony\Bundle\Messenger\Constants;

#[AsMessageHandler(
    bus: Constants::MESSENGER_OFCP_EVENTS_BUS_NAME,
)]
class CustomerCreatedHandler
{
    public function __invoke(CustomerCreatedEventPayload $payload): void
    {
        // Your own logic
    }
}
```

#### Run worker

```shell
$ bin/console messenger:consume ofcp_events
```

### **JsonSchema**

See [https://json-schema.org/](https://json-schema.org/) for more information on how to use JsonSchema.
