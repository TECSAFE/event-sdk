import { EmailHeader } from "./EmailHeader"

/**
* Payload for sending emails with one to two buttons
*/
export interface ButtonEmailEventPayload {
  header: EmailHeader

  /**
   * The title of the email
   */
  title: string
  /**
   * The text content of the email
   */
  text: string

  /**
   * The primary button
   */
  primaryButton: {
    /**
     * The title of the button
     */
    title: string

    /**
     * The URL the button should link to
     */
    url: string
  }

  /**
   * The secondary button
   */
  secondaryButton?: {
    /**
     * The title of the button
     */
    title: string

    /**
     * The URL the button should link to
     */
    url: string
  }
}
