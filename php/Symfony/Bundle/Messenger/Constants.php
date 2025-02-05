<?php

declare(strict_types=1);

namespace Tecsafe\OFCP\Events\Symfony\Bundle\Messenger;

final class Constants
{
    public const MESSENGER_OFCP_EVENTS_BUS_NAME= 'ofcp_events';

    public const EVENT_NAME_HEADER = 'event_name';

    public const TRANSPORT_CONTENT_TYPE = 'application/json';
}
