/** Fixed full-viewport brand mesh gradient behind page content. */
export function BrandPageBackground() {
    return (
        <div
            aria-hidden
            className="pointer-events-none fixed inset-0 -z-10 bg-brand-page-gradient"
        />
    );
}
