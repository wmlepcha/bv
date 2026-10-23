import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type AnimalId =
    | 'fox'
    | 'panda'
    | 'cat'
    | 'dog'
    | 'owl'
    | 'bear'
    | 'rabbit'
    | 'frog'
    | 'penguin'
    | 'koala';

const INK = '#1f2937';

const Eyes = ({ y = 33, gap = 9 }: { y?: number; gap?: number }) => (
    <>
        <circle cx={32 - gap} cy={y} r="2.4" fill={INK} />
        <circle cx={32 + gap} cy={y} r="2.4" fill={INK} />
    </>
);

const ANIMALS: Record<AnimalId, { label: string; bg: string; art: ReactNode }> =
    {
        fox: {
            label: 'Fox',
            bg: '#FFE3D1',
            art: (
                <>
                    <path d="M12 12 L25 24 L11 34 Z" fill="#E8704A" />
                    <path d="M52 12 L39 24 L53 34 Z" fill="#E8704A" />
                    <path d="M15 17 L22 24 L14 30 Z" fill={INK} />
                    <path d="M49 17 L42 24 L50 30 Z" fill={INK} />
                    <ellipse cx="32" cy="36" rx="19" ry="17" fill="#F28C5B" />
                    <path
                        d="M13 38 Q32 58 51 38 Q44 50 32 51 Q20 50 13 38 Z"
                        fill="#FFF4EA"
                    />
                    <Eyes y={34} gap={9} />
                    <ellipse cx="32" cy="44" rx="3" ry="2.2" fill={INK} />
                </>
            ),
        },
        panda: {
            label: 'Panda',
            bg: '#DDF0E7',
            art: (
                <>
                    <circle cx="16" cy="16" r="8" fill={INK} />
                    <circle cx="48" cy="16" r="8" fill={INK} />
                    <circle cx="32" cy="35" r="21" fill="#FFFFFF" />
                    <ellipse
                        cx="23"
                        cy="33"
                        rx="6"
                        ry="7.5"
                        fill={INK}
                        transform="rotate(20 23 33)"
                    />
                    <ellipse
                        cx="41"
                        cy="33"
                        rx="6"
                        ry="7.5"
                        fill={INK}
                        transform="rotate(-20 41 33)"
                    />
                    <circle cx="23" cy="33" r="2" fill="#fff" />
                    <circle cx="41" cy="33" r="2" fill="#fff" />
                    <ellipse cx="32" cy="43" rx="3.5" ry="2.6" fill={INK} />
                </>
            ),
        },
        cat: {
            label: 'Cat',
            bg: '#E4E9F8',
            art: (
                <>
                    <path d="M13 11 L27 22 L12 33 Z" fill="#9AA5B1" />
                    <path d="M51 11 L37 22 L52 33 Z" fill="#9AA5B1" />
                    <path d="M16 17 L23 22 L15 28 Z" fill="#F4B6C2" />
                    <path d="M48 17 L41 22 L49 28 Z" fill="#F4B6C2" />
                    <ellipse cx="32" cy="37" rx="20" ry="17" fill="#B8C1CC" />
                    <Eyes y={35} gap={9} />
                    <path d="M29.5 42 L34.5 42 L32 45 Z" fill="#E88CA0" />
                    <path
                        d="M32 45 Q28 49 24 47 M32 45 Q36 49 40 47"
                        stroke={INK}
                        strokeWidth="1.4"
                        fill="none"
                        strokeLinecap="round"
                    />
                    <path
                        d="M10 41 L21 43 M10 47 L21 46 M54 41 L43 43 M54 47 L43 46"
                        stroke={INK}
                        strokeWidth="1.2"
                        strokeLinecap="round"
                    />
                </>
            ),
        },
        dog: {
            label: 'Dog',
            bg: '#FFF1C9',
            art: (
                <>
                    <ellipse
                        cx="13"
                        cy="30"
                        rx="7"
                        ry="13"
                        fill="#8B5E3C"
                        transform="rotate(12 13 30)"
                    />
                    <ellipse
                        cx="51"
                        cy="30"
                        rx="7"
                        ry="13"
                        fill="#8B5E3C"
                        transform="rotate(-12 51 30)"
                    />
                    <ellipse cx="32" cy="35" rx="18" ry="19" fill="#D9A96F" />
                    <ellipse cx="32" cy="44" rx="10" ry="8" fill="#F6E3C6" />
                    <Eyes y={32} gap={8} />
                    <ellipse cx="32" cy="41" rx="4" ry="3" fill={INK} />
                    <path
                        d="M32 44 L32 47 M28 48 Q32 51 36 48"
                        stroke={INK}
                        strokeWidth="1.4"
                        fill="none"
                        strokeLinecap="round"
                    />
                </>
            ),
        },
        owl: {
            label: 'Owl',
            bg: '#EADFF7',
            art: (
                <>
                    <path d="M14 12 L26 20 L14 28 Z" fill="#8B6B4A" />
                    <path d="M50 12 L38 20 L50 28 Z" fill="#8B6B4A" />
                    <ellipse cx="32" cy="36" rx="20" ry="20" fill="#A47E58" />
                    <circle cx="23" cy="31" r="9" fill="#FFF8E7" />
                    <circle cx="41" cy="31" r="9" fill="#FFF8E7" />
                    <circle cx="23" cy="31" r="4" fill={INK} />
                    <circle cx="41" cy="31" r="4" fill={INK} />
                    <circle cx="24.4" cy="29.6" r="1.2" fill="#fff" />
                    <circle cx="42.4" cy="29.6" r="1.2" fill="#fff" />
                    <path d="M28.5 40 L35.5 40 L32 47 Z" fill="#F2A33A" />
                </>
            ),
        },
        bear: {
            label: 'Bear',
            bg: '#F6E2D3',
            art: (
                <>
                    <circle cx="15" cy="17" r="8" fill="#8B5E3C" />
                    <circle cx="49" cy="17" r="8" fill="#8B5E3C" />
                    <circle cx="15" cy="17" r="4" fill="#D9A96F" />
                    <circle cx="49" cy="17" r="4" fill="#D9A96F" />
                    <circle cx="32" cy="36" r="20" fill="#A8744C" />
                    <ellipse cx="32" cy="43" rx="9" ry="7" fill="#EBCFAE" />
                    <Eyes y={31} gap={9} />
                    <ellipse cx="32" cy="40.5" rx="3.6" ry="2.6" fill={INK} />
                    <path
                        d="M32 43 L32 46 M28.5 47 Q32 50 35.5 47"
                        stroke={INK}
                        strokeWidth="1.3"
                        fill="none"
                        strokeLinecap="round"
                    />
                </>
            ),
        },
        rabbit: {
            label: 'Rabbit',
            bg: '#FBE0EA',
            art: (
                <>
                    <ellipse cx="23" cy="15" rx="5.5" ry="14" fill="#FFFFFF" />
                    <ellipse cx="41" cy="15" rx="5.5" ry="14" fill="#FFFFFF" />
                    <ellipse cx="23" cy="16" rx="2.6" ry="10" fill="#F4B6C2" />
                    <ellipse cx="41" cy="16" rx="2.6" ry="10" fill="#F4B6C2" />
                    <ellipse cx="32" cy="40" rx="18" ry="16" fill="#FFFFFF" />
                    <Eyes y={38} gap={8} />
                    <path d="M29.5 44 L34.5 44 L32 46.6 Z" fill="#E88CA0" />
                    <path
                        d="M32 46.6 Q29 50 26.5 48.5 M32 46.6 Q35 50 37.5 48.5"
                        stroke={INK}
                        strokeWidth="1.3"
                        fill="none"
                        strokeLinecap="round"
                    />
                </>
            ),
        },
        frog: {
            label: 'Frog',
            bg: '#E0F3D8',
            art: (
                <>
                    <circle cx="20" cy="20" r="9" fill="#6FBF5B" />
                    <circle cx="44" cy="20" r="9" fill="#6FBF5B" />
                    <ellipse cx="32" cy="38" rx="22" ry="17" fill="#7FCB6A" />
                    <circle cx="20" cy="20" r="5.5" fill="#fff" />
                    <circle cx="44" cy="20" r="5.5" fill="#fff" />
                    <circle cx="20" cy="21" r="2.6" fill={INK} />
                    <circle cx="44" cy="21" r="2.6" fill={INK} />
                    <path
                        d="M18 42 Q32 54 46 42"
                        stroke={INK}
                        strokeWidth="2"
                        fill="none"
                        strokeLinecap="round"
                    />
                    <circle cx="28" cy="36" r="1.2" fill={INK} />
                    <circle cx="36" cy="36" r="1.2" fill={INK} />
                    <circle cx="14" cy="42" r="3" fill="#F4A6A6" opacity=".7" />
                    <circle cx="50" cy="42" r="3" fill="#F4A6A6" opacity=".7" />
                </>
            ),
        },
        penguin: {
            label: 'Penguin',
            bg: '#D8ECFA',
            art: (
                <>
                    <ellipse cx="32" cy="36" rx="20" ry="21" fill="#2B3A55" />
                    <ellipse cx="32" cy="40" rx="14" ry="16" fill="#FFFFFF" />
                    <circle cx="25" cy="31" r="2.6" fill={INK} />
                    <circle cx="39" cy="31" r="2.6" fill={INK} />
                    <path d="M27.5 37 L36.5 37 L32 43 Z" fill="#F2A33A" />
                    <circle
                        cx="19"
                        cy="38"
                        r="2.6"
                        fill="#F4A6A6"
                        opacity=".7"
                    />
                    <circle
                        cx="45"
                        cy="38"
                        r="2.6"
                        fill="#F4A6A6"
                        opacity=".7"
                    />
                </>
            ),
        },
        koala: {
            label: 'Koala',
            bg: '#E6ECF2',
            art: (
                <>
                    <circle cx="14" cy="22" r="11" fill="#9AA5B1" />
                    <circle cx="50" cy="22" r="11" fill="#9AA5B1" />
                    <circle cx="14" cy="22" r="6" fill="#E6ECF2" />
                    <circle cx="50" cy="22" r="6" fill="#E6ECF2" />
                    <ellipse cx="32" cy="37" rx="19" ry="18" fill="#B8C1CC" />
                    <Eyes y={34} gap={9} />
                    <ellipse cx="32" cy="42" rx="5" ry="6.5" fill={INK} />
                </>
            ),
        },
    };

export const ANIMAL_IDS = Object.keys(ANIMALS) as AnimalId[];

export function animalLabel(animal: string): string {
    return ANIMALS[animal as AnimalId]?.label ?? 'Avatar';
}

export function AnimalAvatar({
    animal,
    className,
}: {
    animal: string;
    className?: string;
}) {
    const entry = ANIMALS[animal as AnimalId] ?? ANIMALS.fox;

    return (
        <svg
            viewBox="0 0 64 64"
            role="img"
            aria-label={entry.label}
            className={cn('size-full', className)}
        >
            <rect width="64" height="64" fill={entry.bg} />
            {entry.art}
        </svg>
    );
}
