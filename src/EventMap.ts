import { CustomerEvents } from "./CustomerEvents";
import { EmailEvents } from "./EmailEvents";

/**
 * @category Events
 */
export const Events = {
  ...CustomerEvents,
  ...EmailEvents
} as const;
