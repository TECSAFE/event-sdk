import {AuthentikAddUserPayload} from "./types/AuthentikAddUserEvent";

/**
 * @category Events
 */
export const AuthentikEvents = {
    /**
     * Fired when a user needs to be added to authentik
     */
    AUTHENTIK_ADD_USER: {
        name: 'authentik.add_user',
        payload: {} as AuthentikAddUserPayload,
    },
} as const;
