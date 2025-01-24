/**
 * Payload for sending generic emails
 */
export interface GenericEmailEventPayload {
    /**
     * Email header attreibutes
     * to, from, cc, bcc, subject, are Required and are self explaining
     * replyTo: Optional the recipient of the reply
     * returnPath : Optional return path address of the email
     */
    header: {
        to: string[]
        from: string
        cc?: string[]
        bcc?: string[]
        subject: string
        replyTo?: string
        returnPath?: string
    }
    /**
     * Email body attributs
     * text: Email text that will be added in the template {{ text }} without html tags Links are parsed to anchor tags
     * title: Email title that will be added in the template {{ title }} not the subject
     */
    body: {
        text: string
        title: string
    }
}
