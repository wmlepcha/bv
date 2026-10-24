import { Link } from '@inertiajs/react';
import {
    Briefcase,
    ChevronRight,
    GraduationCap,
    Presentation,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { home, login } from '@/routes';
import { PrivateSeoHead } from '@shared/components/seo-head';

const portalOptions = [
    {
        href: login({ query: { portal: 'student' } }),
        icon: GraduationCap,
        title: 'Candidate',
        description: 'Classes, training materials, and placement resources.',
        iconClassName: 'bg-primary/15 text-primary group-hover:bg-primary/20',
        borderClassName: 'hover:border-primary/40',
    },
    {
        href: login({ query: { portal: 'staff' } }),
        icon: Briefcase,
        title: 'Recruiter / Employee',
        description: 'Hiring, student profiles, and workplace tools.',
        iconClassName:
            'bg-brand-cool-light/15 text-brand-cool-light group-hover:bg-brand-cool-light/20',
        borderClassName: 'hover:border-brand-cool-light/40',
    },
    {
        href: login({ query: { portal: 'teacher' } }),
        icon: Presentation,
        title: 'Trainer / Teacher',
        description: 'Lessons, videos, and student learning materials.',
        iconClassName:
            'bg-emerald-500/15 text-emerald-400 group-hover:bg-emerald-500/20',
        borderClassName: 'hover:border-emerald-500/40',
    },
] as const;

export default function Portal() {
    return (
        <>
            <PrivateSeoHead title="Sign in" />

            <div className="flex flex-col gap-5">
                <p className="text-center text-sm font-medium text-foreground/90 max-sm:hidden">
                    Select the option that best describes you
                </p>

                <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-3 sm:gap-4">
                    {portalOptions.map((option) => {
                        const Icon = option.icon;

                        return (
                            <Link
                                key={option.title}
                                href={option.href}
                                className={cn(
                                    'group flex h-full flex-col items-center rounded-2xl border border-white/10 bg-white/[0.03] p-4 text-center transition-colors hover:bg-white/[0.06] max-sm:min-h-[72px] max-sm:flex-row max-sm:gap-4 max-sm:border-border max-sm:bg-card max-sm:text-left max-sm:transition-transform max-sm:active:scale-[0.98] max-sm:active:bg-white/10 sm:p-5',
                                    option.borderClassName,
                                )}
                            >
                                <div
                                    className={cn(
                                        'mb-3 flex h-11 w-11 shrink-0 items-center justify-center rounded-full transition-colors max-sm:mb-0',
                                        option.iconClassName,
                                    )}
                                >
                                    <Icon className="size-5" />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="leading-snug font-semibold">
                                        {option.title}
                                    </p>
                                    <p className="mt-2 text-xs leading-relaxed text-muted-foreground max-sm:mt-0.5 sm:text-sm">
                                        {option.description}
                                    </p>
                                </div>
                                <ChevronRight className="size-5 shrink-0 text-muted-foreground sm:hidden" />
                            </Link>
                        );
                    })}
                </div>
            </div>
        </>
    );
}

Portal.layout = {
    title: 'Who are you signing in as?',
    description:
        'Choose your account type so we can take you to the right sign-in experience.',
    containerClassName: 'max-w-4xl',
    backHref: home().url,
};
