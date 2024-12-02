/**
 * Payload for deleting customers
 */
export interface GenericEmailEventPayload {
    /**
     * The receiving email address
     */
    email: string,
    /**
     * The email subject ID
     */
    subject: string,
    /**
     * The email text, only normal text and links are supported, and should not contain HTML
     */
    text: string,
}
