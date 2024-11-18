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

Visit [https://tecsafe.github.io/event-sdk/](https://tecsafe.github.io/event-sdk/) for a more detailed documentation.

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
      'amqp://localhost',
      'test',
      'general',
      true,
      new Logger('MqService')
    ),
  ],
  providers: [],
})
export class AppModule {}
```

```typescript
// app.service.ts
import { Injectable } from '@nestjs/common';
import { MergeCustomerPayload, MqService } from '@tecsafe/event-sdk';

@Injectable()
export class AppService {
  constructor(private readonly mqService: MqService) {
    mqService.subscribe('CUSTOMER_MERGE', this.handleCustomerMerge.bind(this));
  }

  async handleCustomerMerge(payload: MergeCustomerPayload) {
    console.log('Received CUSTOMER_MERGE event', payload);
  }

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

### **JsonSchema**

See [https://json-schema.org/](https://json-schema.org/) for more information on how to use JsonSchema.
