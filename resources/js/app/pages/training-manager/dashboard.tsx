import { Link } from '@inertiajs/react';
import { BadgeCheck, Layers, type LucideIcon, Users } from 'lucide-react';
import { PrivateSeoHead } from '@shared/components/seo-head';
import { cn } from '@/lib/utils';

type Props = {
    stats: {
        trainers: number;
        batches: number;
        active_batches: number;
    };
};

type StatCard = {
    label: string;
    value: number;
    href: string;
    icon: LucideIcon;
    body: string;
    soft: string;
    description: string;
    accent: number;
};

export default function TrainingManagerDashboard({ stats }: Props) {
    const activeAccent =
        stats.batches > 0
            ? Math.max(8, Math.round((stats.active_batches / stats.batches) * 100))
            : 8;

    const cards: StatCard[] = [
        {
            label: 'Trainers',
            value: stats.trainers,
            href: '/training-manager/trainers',
            icon: Users,
            body: 'bg-brand-cool',
            soft: 'bg-white/15',
            description: 'Assigned trainers',
            accent: 40,
        },
        {
            label: 'Batches',
            value: stats.batches,
            href: '/training-manager/batches',
            icon: Layers,
            body: 'bg-brand-warm',
            soft: 'bg-white/15',
            description: 'All batches',
            accent: 55,
        },
        {
            label: 'Active batches',
            value: stats.active_batches,
            href: '/training-manager/batches',
            icon: BadgeCheck,
            body: 'bg-brand-warm-light',
            soft: 'bg-white/20',
            description: 'Currently running',
            accent: activeAccent,
        },
    ];

    return (
        <>
            <PrivateSeoHead title="Training Manager" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Training Manager
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Manage trainers, batches, and training materials.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    {cards.map((card) => {
                        const Icon = card.icon;

                        return (
                            <Link
                                key={card.label}
                                href={card.href}
                                className={cn(
                                    'group relative flex min-h-[160px] flex-col overflow-hidden rounded-[1.75rem] p-5 text-white shadow-[0_18px_40px_-24px_rgba(3,24,53,0.55)] transition-transform duration-200 hover:-translate-y-0.5',
                                    card.body,
                                )}
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <p className="text-sm font-medium text-white/85">
                                        {card.label}
                                    </p>
                                    <span
                                        className={cn(
                                            'flex size-11 shrink-0 items-center justify-center rounded-2xl',
                                            card.soft,
                                        )}
                                    >
                                        <Icon className="size-5" />
                                    </span>
                                </div>

                                <p className="mt-4 text-4xl font-semibold tracking-tight tabular-nums">
                                    {card.value}
                                </p>
                                <p className="mt-1 text-sm text-white/75">
                                    {card.description}
                                </p>

                                <div className="mt-auto pt-5">
                                    <div className="h-1.5 overflow-hidden rounded-full bg-white/25">
                                        <div
                                            className="h-full rounded-full bg-white transition-[width] duration-300"
                                            style={{ width: `${card.accent}%` }}
                                        />
                                    </div>
                                </div>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </>
    );
}

TrainingManagerDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/training-manager/dashboard',
        },
    ],
};
