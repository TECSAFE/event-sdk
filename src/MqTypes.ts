import { EventsMap } from "./TypeHelper";

/**
 * An event handler for a specific channel.
 * @category Service
 * @param payload The payload of the event.
 * @returns If true or void is returned, the message will be acknowledged. If false or a MqError instance is returned, the message will NOT be acknowledged and will be requeued.
 */
export type MqHandler<TChannel extends keyof EventsMap> = (
  payload: EventsMap[TChannel]['payload']
) => void | Promise<void | boolean | MqError>;

/**
 * If returned from a handler, the message will NOT be acknowledged and will be requeued.
 * @category Service
 * @param requeue If true, the message will be requeued, otherwise it will be discarded.
 */
export class MqError {
  /**
   * Create a new MqError instance.
   * @param requeue If true, the message will be requeued.
   */
  constructor(public readonly requeue: boolean) {}
}
