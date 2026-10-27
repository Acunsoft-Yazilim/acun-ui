// A button that saves a text as a file in the browser (acun:recovery-codes' Download), in plain JavaScript:
//
//     <button type="button" data-acun-download-text data-text="…" data-filename="recovery-codes.txt">Download</button>

/** @import { PluginDefinition } from '../core/plugins.js' */

/**
 * @param {HTMLElement} root
 */
function createDownloadText(root) {
    const onClick = () => {
        const link = document.createElement('a');
        link.href = URL.createObjectURL(new Blob([`${root.dataset.text ?? ''}\n`], { type: 'text/plain' }));
        link.download = root.dataset.filename || 'download.txt';
        link.click();
        setTimeout(() => URL.revokeObjectURL(link.href), 0);
    };
    root.addEventListener('click', onClick);

    return {
        element: root,
        destroy() {
            root.removeEventListener('click', onClick);
        },
    };
}

/** @type {PluginDefinition} */
export const downloadTextPlugin = {
    attribute: 'data-acun-download-text',
    mount: createDownloadText,
};
