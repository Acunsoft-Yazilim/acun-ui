export type ConfirmRequest = {
    /**
     * the dialog's name; default "Are you sure?"
     */
    title?: string;
    /**
     * the question
     */
    text?: string;
    /**
     * default "Confirm"
     */
    confirmLabel?: string;
    /**
     * default "Cancel"
     */
    cancelLabel?: string;
    /**
     * the X's name; default "Close"
     */
    closeLabel?: string;
    /**
     * the confirm button's look: the primary colour, or red for what destroys
     */
    tone?: 'default' | 'danger';
};
/**
 * Asks with the kit's confirmation dialog; resolves true when the user confirms.
 *
 * @param {ConfirmRequest} [request]
 * @returns {Promise<boolean>}
 */
export declare function confirm(request?: ConfirmRequest): Promise<boolean>;
