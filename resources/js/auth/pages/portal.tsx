import { Link } from '@inertiajs/react';
import { Briefcase, GraduationCap, Presentation } from 'lucide-react';
import { PrivateSeoHead } from '@shared/components/seo-head';
import { login } from '@/routes';
import { cn } from '@/lib/utils';

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
        iconClassName: 'bg-brand-cool-light/15 text-brand-cool-light group-hover:bg-brand-cool-light/20',
        borderClassName: 'hover:border-brand-cool-light/40',
    },
    {
        href: login({ query: { portal: 'teacher' } }),
        icon: Presentation,
        title: 'Trainer / Teacher',
        description: 'Lessons, videos, and student learning materials.',
        iconClassName: 'bg-emerald-500/15 text-emerald-400 group-hover:bg-emerald-500/20',
        borderClassName: 'hover:border-emerald-500/40',
    },
] as const;

export default function Portal() {
    return (
        <>
            <PrivateSeoHead title="Sign in" />

            <div className="flex flex-col gap-5">
                <p className="text-center text-sm font-medium text-foreground/90">
                    Select the option that best describes you
                </p>

                <div className="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
                    {portalOptions.map((option) => {
                        const Icon = option.icon;

                        return (
                            <Link
                                key={option.title}
                                href={option.href}
                                className={cn(
                                    'group flex h-full flex-col items-center rounded-2xl border border-white/10 bg-white/[0.03] p-4 text-center transition-colors hover:bg-white/[0.06] sm:p-5',
                                    option.borderClassName,
                                )}
                            >
                                <div
                                    className={cn(
                                        'mb-3 flex h-11 w-11 shrink-0 items-center justify-center rounded-full transition-colors',
                                        option.iconClassName,
                                    )}
                                >
                                    <Icon className="size-5" />
                                </div>
                                <p className="font-semibold leading-snug">{option.title}</p>
                                <p className="mt-2 text-xs leading-relaxed text-muted-foreground sm:text-sm">
                                    {option.description}
                                </p>
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
    description: 'Choose your account type so we can take you to the right sign-in experience.',
    containerClassName: 'max-w-4xl',
};
