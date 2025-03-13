import { EmailHeader } from "./EmailHeader"

/**
* Payload for sending generic emails
*/
export interface GenericEmailEventPayload {
  header: EmailHeader

  /**
   * The title of the email
   */
  title: string

  /**
   * The text content of the email
   */
  text: string
}
