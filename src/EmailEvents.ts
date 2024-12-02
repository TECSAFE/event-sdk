import {GenericEmailEventPayload} from "./types/GenericEmailEvent";

/**
 * @category Events
 */
export const EmailEvents = {
    /**
     * Fired when a generic email needs to be sent
     */
    EMAIL_GENERIC: {
        name: 'email.generic',
        payload: {} as GenericEmailEventPayload,
    },
} as const;
