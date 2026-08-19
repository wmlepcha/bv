const FULL_CV_PRINT_STYLE_ID = 'bv-full-cv-print-page';

/**
 * Full CV uses generous in-sheet padding for spacing (more reliable than @page
 * margins across browsers). Keep @page at zero so padding is the single source.
 */
export function printCvDocument(variant: 'basic' | 'full' = 'basic'): void {
    const previousTitle = document.title;
    document.title = ' ';

    let styleEl = document.getElementById(
        FULL_CV_PRINT_STYLE_ID,
    ) as HTMLStyleElement | null;

    if (variant === 'full') {
        if (!styleEl) {
            styleEl = document.createElement('style');
            styleEl.id = FULL_CV_PRINT_STYLE_ID;
            document.head.appendChild(styleEl);
        }

        // Zero page margin — spacing comes from .cv-full-sheet-body padding.
        styleEl.textContent = `
@page {
  size: A4;
  margin: 0;
}
`;
    } else if (styleEl) {
        styleEl.remove();
    }

    window.setTimeout(() => {
        window.print();
        document.title = previousTitle;

        const injected = document.getElementById(FULL_CV_PRINT_STYLE_ID);
        injected?.remove();
    }, 200);
}
