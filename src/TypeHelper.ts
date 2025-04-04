import { Events } from './EventMap';

/**
 * @category Helpers
 */
export type EventsMap = typeof Events;

/**
 * @category Helpers
 */
export type EventChannels = EventsMap[keyof EventsMap]['name'];

/**
 * @category Helpers
 */
export type EventPayload<TChannel extends EventChannels> = {
  [K in keyof EventsMap]: EventsMap[K]['name'] extends TChannel 
    ? EventsMap[K]['payload'] 
    : never
}[keyof EventsMap];
