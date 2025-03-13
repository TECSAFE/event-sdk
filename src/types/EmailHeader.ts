/**
 * The header of an email. Reused in all email events.
 */
export interface EmailHeader {
  /**
   * The email address of the recipient. You can include multiple email addresses, separated by commas.
   * Note that everyone who receives a copy of the message will be able to see the list of recipients in the To field.
   * If you want to send multiple emails without disclosing addresses, use the "bcc" field.
   */
  to?: string[]

  /**
   * CC stands for "carbon copy". Sends a copy of the email to the people you list, in addition to the person in the To field.
   * Note that everyone who receives a copy of the message will be able to see the list of recipients in the CC field.
   * In nearly all cases you should use the "bcc" field instead of the "cc" field.
   */
  cc?: string[]

  /**
   * BCC stands for "blind carbon copy". Just like CC, BCC is a way of sending copies of an email to other people.
   * The difference between the two is that no one can see the list of recipients in a BCC message. The BCC list
   * is private.
   */
  bcc?: string[]

  /**
   * The subject of the email.
   */
  subject: string

  /**
   * Set where replies to the email should be sent.
   */
  replyTo?: string

  /**
   * A return path is used to specify where bounced emails are sent and is placed in the email header.
   * It’s an SMTP address separate from the sending address. This is a good practice for email delivery,
   * as it gives bounced emails a place to land – other than in an inbox – making it easier to avoid
   * sending notifications to bounced addresses. However, it’s important for authentication to ensure
   * that the return path domain is the same as the sending domain.
   * 
   * Brands use a return path that stores all bounced emails, which helps them improve deliverability,
   * making them more credible to inbox service providers (ISPs) like Gmail and Yahoo Mail. Return path
   * also helps track email bounces and maintain email list hygiene.
   */
  returnPath?: string
}
