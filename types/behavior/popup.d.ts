export type PopupReason = 'user' | 'command' | 'removed';
export type Popup = {
    trigger: HTMLElement | null;
    content: HTMLElement | null;
    /**
     * the trigger's button or link
     */
    control: HTMLElement | null;
    isOpen: () => boolean;
    /**
     * opens (false when there is no content); true once open
     */
    open: () => boolean;
    close: (options?: {
        refocus?: boolean;
    }, reason?: PopupReason) => void;
    toggle: () => void;
    /**
     * a listener removed on destroy
     */
    on: (target: EventTarget | null, type: string, handler: (event: any) => void) => void;
    destroy: () => void;
};
/**
 * @param {HTMLElement} root
 * @param {{ kind: 'dropdown' | 'popover', attribute: string }} options
 * @returns {Popup}
 */
export declare function createPopup(root: HTMLElement, { kind, attribute }: {
    kind: 'dropdown' | 'popover';
    attribute: string;
}): Popup;
