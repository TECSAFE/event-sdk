/**
 * Payload for sending emails with one to two buttons
 */
export interface ButtonEmailEventPayload {
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
     * data: holds the data for the buttons
     */
    body: {
        text: string
        title: string
        data: {
            primatyButton: {
                title: string
                url: string
            }
            secondaryButton?: {
                title: string
                url: string
            }
        }
    }
}
