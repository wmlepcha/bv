import { Link, usePage } from '@inertiajs/react';
import { BadgeCheck, Briefcase, GraduationCap, Users } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as studentsIndex } from '@/routes/recruiter/students';
import type { Auth } from '@/types/auth';
import CandidateCard from '@app/components/candidate-card';
import type { CandidateCard as CandidateCardData } from '@app/types/recruiter';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Count = { value: string; label: string; count: number };

type Props = {
    stats: {
        total: number;
        open: number;
        hired: number;
        freshers: number;
        experienced: number;
    };
    professions: Count[];
    levels: Count[];
    recent: CandidateCardData[];
};

export default function RecruiterDashboard({
    stats,
    professions,
    levels,
    recent,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const firstName = auth.user?.name?.split(' ')[0] ?? '';
    const openPercent =
        stats.total > 0 ? Math.round((stats.open / stats.total) * 100) : 0;
    const cards: {
        label: string;
        value: number;
        description: string;
        icon: LucideIcon;
        body: string;
        href: string;
    }[] = [
        {
            label: 'Open for work',
            value: stats.open,
            description: `of ${stats.total} candidates`,
            icon: Users,
            body: 'bg-brand-cool',
            href: studentsIndex.url(),
        },
        {
            label: 'Hired',
            value: stats.hired,
            description: 'Already placed',
            icon: BadgeCheck,
            body: 'bg-brand-warm',
            href: studentsIndex.url(),
        },
        {
            label: 'Freshers',
            value: stats.freshers,
            description: 'Open for work',
            icon: GraduationCap,
            body: 'bg-brand-warm-light',
            href: studentsIndex.url({ query: { experience: 'fresher' } }),
        },
        {
            label: 'Experienced',
            value: stats.experienced,
            description: 'Open for work',
            icon: Briefcase,
            body: 'bg-brand-cool',
            href: studentsIndex.url({ query: { experience: 'experienced' } }),
        },
    ];

    return (
        <>
            <PrivateSeoHead title="Dashboard" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div>
                    <p className="text-sm text-muted-foreground md:hidden">
                        Welcome back{firstName && `, ${firstName}`}
                    </p>
                    <h1 className="text-3xl font-bold tracking-tight md:text-2xl md:font-semibold">
                        Dashboard
                    </h1>
                    <p className="mt-2 hidden text-sm text-muted-foreground md:block">
                        Candidates available for placement.
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
                    {cards.map((card, index) => {
                        const Icon = card.icon;
                        const hero = index === 0;

                        return (
                            <Link
                                key={card.label}
                                href={card.href}
                                className={cn(
                                    'flex flex-col rounded-3xl p-4 text-white shadow-[0_18px_40px_-24px_rgba(3,24,53,0.55)] transition-transform duration-200 active:scale-[0.97] md:min-h-[140px] md:rounded-[1.75rem] md:p-5 md:hover:-translate-y-0.5 md:active:scale-100',
                                    hero && 'col-span-2 min-h-36 lg:col-span-1',
                                    !hero && 'min-h-28',
                                    card.body,
                                )}
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <p className="text-sm font-medium text-white/85">
                                        {card.label}
                                    </p>
                                    <span className="flex size-9 items-center justify-center rounded-2xl bg-white/15 md:size-11">
                                        <Icon className="size-4 md:size-5" />
                                    </span>
                                </div>
                                <p
                                    className={cn(
                                        'mt-auto font-semibold tabular-nums',
                                        hero
                                            ? 'text-5xl md:text-4xl'
                                            : 'text-3xl md:text-4xl',
                                    )}
                                >
                                    {card.value}
                                </p>
                                <p className="mt-1 text-xs text-white/75 md:text-sm">
                                    {card.description}
                                </p>
                                {hero && (
                                    <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-white/25 md:hidden">
                                        <div
                                            className="h-full rounded-full bg-white"
                                            style={{ width: `${openPercent}%` }}
                                        />
                                    </div>
                                )}
                            </Link>
                        );
                    })}
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <BreakdownList
                        title="By profession"
                        items={professions}
                        param="profession"
                    />
                    <BreakdownList
                        title="By language level"
                        items={levels}
                        param="level"
                    />
                </div>

                <section className="flex flex-col gap-3">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold tracking-tight">
                            Recently added
                        </h2>
                        <Link
                            href={studentsIndex()}
                            className="text-sm font-medium text-primary active:opacity-60 md:text-muted-foreground md:underline"
                        >
                            See all
                        </Link>
                    </div>
                    {recent.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No open candidates yet.
                        </p>
                    ) : (
                        <div className="-mx-2 flex snap-x snap-mandatory scrollbar-none gap-3 overflow-x-auto px-2 pb-1 md:mx-0 md:grid md:grid-cols-3 md:overflow-visible md:px-0 lg:grid-cols-6 [&>*]:w-40 [&>*]:shrink-0 [&>*]:snap-start md:[&>*]:w-auto">
                            {recent.map((candidate) => (
                                <CandidateCard
                                    key={candidate.id}
                                    candidate={candidate}
                                />
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

function BreakdownList({
    title,
    items,
    param,
}: {
    title: string;
    items: Count[];
    param: string;
}) {
    return (
        <div className="md:rounded-2xl md:border md:border-border md:bg-card/40 md:p-5">
            <h2 className="text-lg font-semibold tracking-tight md:text-base">
                {title}
            </h2>

            <div className="-mx-2 mt-3 flex scrollbar-none gap-2 overflow-x-auto px-2 md:hidden">
                {items.map((item) => (
                    <Link
                        key={item.value}
                        href={studentsIndex.url({
                            query: { [param]: item.value },
                        })}
                        className="flex shrink-0 items-center gap-2 rounded-full border border-border bg-card px-4 py-2 text-sm shadow-sm transition-transform active:scale-95"
                    >
                        {item.label}
                        <span className="rounded-full bg-primary/10 px-2 text-xs font-bold text-primary tabular-nums">
                            {item.count}
                        </span>
                    </Link>
                ))}
            </div>

            <ul className="mt-3 hidden divide-y divide-border text-sm md:block">
                {items.map((item) => (
                    <li key={item.value}>
                        <Link
                            href={studentsIndex.url({
                                query: { [param]: item.value },
                            })}
                            className="flex items-center justify-between py-2 hover:underline"
                        >
                            <span>{item.label}</span>
                            <span className="font-semibold tabular-nums">
                                {item.count}
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>
        </div>
    );
}

RecruiterDashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
