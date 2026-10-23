import { Link } from '@inertiajs/react';
import { Briefcase, Languages, Sprout, Stethoscope } from 'lucide-react';
import { cn } from '@/lib/utils';
import { show as studentShow } from '@/routes/recruiter/students';
import type { CandidateCard as CandidateCardData } from '@app/types/recruiter';

const FALLBACK_GRADIENTS = [
    'from-[#2F5D9E] to-[#7FB2E5]',
    'from-[#E8704A] to-[#F4B183]',
    'from-[#2E8B7A] to-[#8CD3C2]',
    'from-[#6C5BB5] to-[#B7A9E8]',
];

function initialsFromName(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);

    if (parts.length === 0) {
        return '?';
    }

    return `${parts[0][0] ?? ''}${parts.length > 1 ? (parts[parts.length - 1][0] ?? '') : (parts[0][1] ?? '')}`.toUpperCase();
}

function titleCase(name: string): string {
    return name
        .toLowerCase()
        .replace(/\b\p{L}/gu, (char) => char.toUpperCase());
}

const LEVEL_STYLES: Record<string, string> = {
    A1: 'bg-sky-600',
    A2: 'bg-emerald-600',
    B1: 'bg-amber-600',
    B2: 'bg-violet-600',
};

const glass =
    'inline-flex max-w-full items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-medium text-white backdrop-blur-md ring-1 ring-white/20 sm:px-2.5 sm:py-1 sm:text-[11px]';

export default function CandidateCard({
    candidate,
}: {
    candidate: CandidateCardData;
}) {
    const hired = candidate.placement_status === 'hired';
    const gradient =
        FALLBACK_GRADIENTS[candidate.id % FALLBACK_GRADIENTS.length];
    const ExperienceIcon =
        candidate.experience === 'fresher' ? Sprout : Briefcase;

    const locked = !candidate.viewable;
    const Wrapper = locked ? 'div' : Link;
    const wrapperProps = locked
        ? { 'aria-disabled': true, title: 'This candidate has been hired' }
        : { href: studentShow(candidate.id) };

    return (
        <Wrapper
            {...(wrapperProps as object)}
            className={cn(
                'group relative block aspect-[3/4] overflow-hidden rounded-3xl bg-muted shadow-sm ring-1 ring-black/5',
                locked
                    ? 'cursor-not-allowed select-none'
                    : 'transition-all duration-300 hover:-translate-y-1 hover:shadow-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
            )}
        >
            {candidate.photo_url ? (
                <img
                    src={candidate.photo_url}
                    alt=""
                    className={cn(
                        'absolute inset-0 size-full object-cover object-top transition-transform duration-500',
                        !locked && 'group-hover:scale-105',
                        hired && 'grayscale',
                    )}
                />
            ) : (
                <div
                    className={cn(
                        'absolute inset-0 flex items-center justify-center bg-gradient-to-br',
                        gradient,
                    )}
                >
                    <span className="text-5xl font-semibold tracking-tight text-white/90">
                        {initialsFromName(candidate.name)}
                    </span>
                </div>
            )}

            <div className="absolute inset-x-0 bottom-0 h-3/5 bg-gradient-to-t from-black/85 via-black/45 to-transparent" />

            {locked && (
                <div className="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/35 pb-[30%] sm:pb-0">
                    <span className="-rotate-12 rounded-lg border-2 border-white/80 px-2.5 py-0.5 text-lg font-black tracking-[0.15em] text-white/80 uppercase sm:rounded-xl sm:border-4 sm:px-5 sm:py-1 sm:text-3xl sm:tracking-[0.2em] lg:text-4xl">
                        Hired
                    </span>
                </div>
            )}

            <div className="absolute inset-x-2 top-2 flex items-start justify-between gap-1 sm:inset-x-3 sm:top-3 sm:gap-2">
                <span className="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap text-neutral-900 shadow-sm backdrop-blur sm:px-2.5 sm:py-1 sm:text-[11px]">
                    <span
                        className={cn(
                            'size-1.5 rounded-full',
                            hired ? 'bg-destructive' : 'bg-emerald-500',
                        )}
                    />
                    {hired ? (
                        'Hired'
                    ) : (
                        <>
                            Open<span className="max-sm:hidden"> for work</span>
                        </>
                    )}
                </span>
                {candidate.language_level && (
                    <span
                        className={cn(
                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold text-white shadow-sm ring-1 ring-white/30 sm:px-2.5 sm:py-1 sm:text-[11px]',
                            LEVEL_STYLES[candidate.language_level] ??
                                'bg-slate-600',
                        )}
                    >
                        <Languages className="size-3" />
                        {candidate.language_level}
                    </span>
                )}
            </div>

            <div className="absolute inset-x-0 bottom-0 flex flex-col gap-1.5 p-3 text-white sm:gap-2 sm:p-4">
                <div className="min-w-0">
                    <p className="truncate text-sm leading-tight font-semibold sm:text-base">
                        {titleCase(candidate.name)}
                    </p>
                    {candidate.headline && (
                        <p className="mt-0.5 line-clamp-1 text-[11px] text-white/75 sm:text-xs">
                            {candidate.headline}
                        </p>
                    )}
                </div>
                <div className="flex flex-wrap gap-1 sm:gap-1.5">
                    <span className={glass}>
                        <ExperienceIcon className="size-3" />
                        {candidate.experience === 'fresher'
                            ? 'Fresher'
                            : 'Experienced'}
                    </span>
                    {candidate.professions[0] && (
                        <span className={glass}>
                            <Stethoscope className="size-3" />
                            {candidate.professions[0]}
                        </span>
                    )}
                </div>
            </div>
        </Wrapper>
    );
}
