import {CockpitAddRegistrationPayload} from "./types/CockpitAddRegistrationEvent";

/**
 * @category Events
 */
export const AuthentikEvents = {
    /**
     * Fired when a user needs to be added to authentik
     */
    COCKPIT_ADD_REGISTRATION: {
        name: 'cockpit.addRegistration',
        payload: {} as CockpitAddRegistrationPayload,
    },
} as const;
