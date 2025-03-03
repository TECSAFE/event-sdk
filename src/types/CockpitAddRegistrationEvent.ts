/**
 * Payload for add an user to the Cockpit
 */
export interface CockpitAddRegistrationPayload {
    /**
     * user data attributes
     * username, name, email, are Required and are self explaining
     * type: is Required and Define the type of the user [internal┃external┃service_account┃internal_service_account]
     * groups : Optional, array of uuids of the groups the user is in
     */
    user: {
      username: string
      name: string
      groups?: string[]
      email: string
      type: string
    }
    role: string;
    expiration_date: Date | null;
    password: string
}
