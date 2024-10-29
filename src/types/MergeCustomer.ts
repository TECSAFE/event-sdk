import { CustomerData } from "../CustomerData";

/**
 * Payload for merging customers
 */
export interface MergeCustomerPayload {
  /**
   * The old customer ID
   */
  oldCustomerId: string,
  /**
   * The new customer ID
   */
  newCustomerId: string,
  /**
   * The sales channel ID
   */
  salesChannel?: string,
  data: CustomerData,
}
