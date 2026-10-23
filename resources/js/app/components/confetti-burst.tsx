const COLORS = [
    '#E8704A',
    '#3B7DD8',
    '#F2A33A',
    '#2E8B7A',
    '#6C5BB5',
    '#F4B6C2',
];

const PIECES = Array.from({ length: 36 }, (_, index) => ({
    left: (index * 37) % 100,
    delay: ((index * 53) % 20) / 10,
    duration: 2.4 + ((index * 17) % 14) / 10,
    size: 6 + ((index * 7) % 7),
    color: COLORS[index % COLORS.length],
    round: index % 3 === 0,
    drift: ((index * 29) % 60) - 30,
    rest: 6 + ((index * 41) % 80),
}));

/** Decorative confetti raining inside a relatively-positioned parent. */
export function ConfettiBurst() {
    return (
        <div
            aria-hidden
            className="pointer-events-none absolute inset-0 overflow-hidden"
        >
            {PIECES.map((piece, index) => (
                <span
                    key={index}
                    className="confetti-piece"
                    style={
                        {
                            left: `${piece.left}%`,
                            width: piece.size,
                            height: piece.round ? piece.size : piece.size * 1.8,
                            backgroundColor: piece.color,
                            borderRadius: piece.round ? '9999px' : '2px',
                            animationDelay: `${piece.delay}s`,
                            animationDuration: `${piece.duration}s`,
                            '--confetti-drift': `${piece.drift}px`,
                            '--confetti-rest': `${piece.rest}%`,
                        } as React.CSSProperties
                    }
                />
            ))}
        </div>
    );
}
