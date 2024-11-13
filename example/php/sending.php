<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Tecsafe\OFCP\Events\Models\MergeCustomerPayload;
use Tecsafe\OFCP\Events\Models\TestType;
use Tecsafe\OFCP\Events\MqService;

$service = new MqService();
$service->send_customer_merge(new MergeCustomerPayload(
  '123',
  '456',
  '789',
  new TestType('test')
));
$service->closeConnection();
