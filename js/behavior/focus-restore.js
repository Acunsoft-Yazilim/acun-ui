// Gives the focus back to where it was before a dialog opened.

/** Remembers the focused element; the returned function focuses it again if it is still on the page. */
export function rememberFocus() {
    const element = document.activeElement;

    return () => {
        if (element && element !== document.body && element.isConnected && typeof element.focus === 'function') {
            element.focus({ preventScroll: true });
        }
    };
}
