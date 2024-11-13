<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Tecsafe\OFCP\Events\Listeners\DeleteCustomerPayloadListener;
use Tecsafe\OFCP\Events\Models\DeleteCustomerPayload;
use Tecsafe\OFCP\Events\Models\MergeCustomerPayload;
use Tecsafe\OFCP\Events\MqService;
use Tecsafe\OFCP\Events\MqServiceError;

class CustomerDeleteListener implements DeleteCustomerPayloadListener
{
  public function on_event(DeleteCustomerPayload $payload): MqServiceError
  {
    echo "Received delete customer event: " . json_encode($payload) . "\n";
    return new MqServiceError(false);
  }
}

$service = new MqService('localhost', 5672, 'guest', 'guest', 'test');

$service->subscribe_customer_merge(function (MergeCustomerPayload $payload) {
  echo "Received merge customer event: " . json_encode($payload) . "\n";
  return true;
});

$service->subscribe_customer_delete(new CustomerDeleteListener());

$service->startConsuming();
