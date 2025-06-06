import { MergeCustomerPayload } from "./types/MergeCustomer";
import {CustomerDeleteEventPayload} from "./types/CustomerDeleteEvent";
import {CustomerCreatedEventPayload} from "./types/CustomerCreatedEvent";

/**
 * @category Events
 */
export const CustomerEvents = {
  /**
   * Fired when customers need to be merged,
   * most commonly when a guest customer is converted to a registered customer,
   * but not limited to that.
   */
  CUSTOMER_MERGE: {
    name: 'customer.merge',
    payload: {} as MergeCustomerPayload,
  },
  /**
   * Fired when a customer needs to be deleted.
   */
  CUSTOMER_DELETE: {
    name: 'customer.delete',
    payload: {} as CustomerDeleteEventPayload,
  },
  /**
   * Fired after a customer was created by the gateway in zitadel.
   */
  CUSTOMER_CREATED: {
    name: 'customer.created',
    payload: {} as CustomerCreatedEventPayload,
  },
} as const;
