const authBackgroundImageUrl = '/storage/bg-form.webp';

/** Blurred photo background for auth pages. */
export function AuthPageBackground() {
    return (
        <div aria-hidden className="pointer-events-none absolute inset-0 overflow-hidden">
            <img
                src={authBackgroundImageUrl}
                alt=""
                className="absolute inset-0 h-full w-full scale-110 object-cover blur-lg"
            />
            <div className="absolute inset-0 bg-black/50" />
            <div className="absolute inset-0 bg-gradient-to-b from-black/30 via-black/20 to-black/70" />
        </div>
    );
}
