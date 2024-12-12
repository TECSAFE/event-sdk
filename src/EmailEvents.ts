import {GenericEmailEventPayload} from "./types/GenericEmailEvent";
import {ButtonEmailEventPayload} from "./types/ButtonEmailEvent";

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
    /**
     * Fired when a email with buttons needs to be sent
     */
    EMAIL_BUTTON: {
        name: 'email.button',
        payload: {} as ButtonEmailEventPayload,
    }
} as const;
