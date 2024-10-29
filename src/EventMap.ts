import { DeleteCustomerPayload } from "./types/DeleteCustomer";
import { MergeCustomerPayload } from "./types/MergeCustomer";

/**
 * @category Events
 */
export const Events = {
  /**
   * Fired when customers need to be merged,
   * most commonly when a guest customer is converted to a registered customer,
   * but not limited to that.
   */
  MERGE_CUSTOMER: {
    channel: 'customer/merge',
    payload: {} as MergeCustomerPayload,
  },
  /**
   * Fired when a customer needs to be deleted.
   */
  DELETE_CUSTOMER: {
    channel: 'customer/delete',
    payload: {} as DeleteCustomerPayload,
  },
} as const;
