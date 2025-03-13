<?php

declare (strict_types=1);
namespace Tecsafe\OFCP\Events;

/**
 * The events constant must have the following structure:
 *
 * @example
 * public const EVENTS = [
 *      [
 *          'name' => 'customer.merge',
 *          'type' =>  'MergeCustomerPayload',
 *      ],
 *      [
 *          'name' => 'customer.delete',
 *          'type' => 'CustomerDeletePayload',
 *      ],
 *      [
 *          'name' => 'customer.created',
 *          'type' => 'CustomerCreatedPayload',
 *      ]
 * ];
 */
final class EventMap
{
    const CUSTOMER_MERGE = ['name' => 'customer.merge', 'type' => 'MergeCustomerPayload'];
    const CUSTOMER_DELETE = ['name' => 'customer.delete', 'type' => 'CustomerDeleteEventPayload'];
    const CUSTOMER_CREATED = ['name' => 'customer.created', 'type' => 'CustomerCreatedEventPayload'];
    const EMAIL_GENERIC = ['name' => 'email.generic', 'type' => 'GenericEmailEventPayload'];
    const EMAIL_BUTTON = ['name' => 'email.button', 'type' => 'ButtonEmailEventPayload'];
    const COCKPIT_ADDREGISTRATION = ['name' => 'cockpit.addRegistration', 'type' => 'CockpitAddRegistrationPayload'];
    public const EVENTS = [self::CUSTOMER_MERGE, self::CUSTOMER_DELETE, self::CUSTOMER_CREATED, self::EMAIL_GENERIC, self::EMAIL_BUTTON, self::COCKPIT_ADDREGISTRATION];
    public static function getTypeName(string $eventName, bool $fqdn = true): string
    {
        $typeName = null;
        foreach (self::EVENTS as $event) {
            if ($event['name'] === $eventName) {
                $typeName = $event['type'];
            }
        }
        if ($typeName === null) {
            throw new \Error();
        }
        if ($fqdn) {
            $typeName = 'Tecsafe\OFCP\Events\Models\\' . $typeName;
            if (class_exists($typeName)) {
                return $typeName;
            } else {
                throw new \Error();
            }
        } else {
            return $typeName;
        }
    }
    public static function getEventName(string $typeName): string
    {
        foreach (self::EVENTS as $event) {
            if ($event['type'] === $typeName) {
                return $event['name'];
            }
        }
        throw new \Error();
    }
}