/**
 * Payload for deleting customers
 */
export interface BasicCustomerEventPayload {
  /**
   * The customer ID
   */
  customer: string,
  /**
   * The sales channel ID
   */
  salesChannel: string,
}
