/**
 * Payload for merging customers
 */
export interface MergeCustomerPayload {
  /**
   * The old external customer ID
   */
  oldExternalCustomerId: string,
  /**
   * The new customer ID
   */
  newExternalCustomerId: string,
  /**
   * The old sales channel ID
   */
  oldSalesChannelId: string,
  /**
   * The new sales channel ID
   */
  newSalesChannelId: string
  /**
   * The old customer ID
   */
  oldCustomerId: string,
  /**
   * The new customer ID
   */
  newCustomerId: string,
}
