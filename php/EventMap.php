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
    public const EVENTS = [];

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