/**
 * Payload for deleting customers
 */
export interface DeleteCustomerPayload {
  /**
   * The customer ID
   */
  customer: string,
  /**
   * The sales channel ID
   */
  salesChannel: string,
}
