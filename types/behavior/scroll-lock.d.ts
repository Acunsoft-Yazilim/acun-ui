export declare const scrollLock: {
    lock(): void;
    unlock(): void;
    /** No dialog open any more (a wire:navigate page change). */
    reset(): void;
    readonly count: number;
    readonly locked: boolean;
};
