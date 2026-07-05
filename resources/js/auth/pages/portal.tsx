import { Link } from '@inertiajs/react';
import { Briefcase, GraduationCap } from 'lucide-react';
import { PrivateSeoHead } from '@shared/components/seo-head';
import { login } from '@/routes';
import { cn } from '@/lib/utils';

const portalOptions = [
    {
        href: login({ query: { portal: 'student' } }),
        icon: GraduationCap,
        title: 'I am a Candidate',
        description: 'Sign in to access your classes, training materials, and placement resources.',
        iconClassName: 'bg-primary/15 text-primary group-hover:bg-primary/20',
        borderClassName: 'hover:border-primary/40',
    },
    {
        href: login({ query: { portal: 'staff' } }),
        icon: Briefcase,
        title: 'I am a Recruiter or Employee',
        description: 'Sign in to manage hiring, student profiles, and internal workplace tools.',
        iconClassName: 'bg-brand-cool-light/15 text-brand-cool-light group-hover:bg-brand-cool-light/20',
        borderClassName: 'hover:border-brand-cool-light/40',
    },
] as const;

export default function Portal() {
    return (
        <>
            <PrivateSeoHead title="Sign in" />

            <div className="flex flex-col gap-6">
                <div className="space-y-3">
                    <div className="flex gap-2">
                        <div className="h-1.5 flex-1 rounded-full bg-primary" />
                    </div>
                    <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        Step 1 of 1
                    </p>
                </div>

                <p className="text-sm font-medium text-foreground/90">
                    Select the option that best describes you
                </p>

                <div className="grid gap-4">
                    {portalOptions.map((option) => {
                        const Icon = option.icon;

                        return (
                            <Link
                                key={option.title}
                                href={option.href}
                                className={cn(
                                    'group rounded-2xl border border-white/10 bg-white/[0.03] p-5 text-left transition-colors hover:bg-white/[0.06]',
                                    option.borderClassName,
                                )}
                            >
                                <div
                                    className={cn(
                                        'mb-3 flex h-11 w-11 items-center justify-center rounded-full transition-colors',
                                        option.iconClassName,
                                    )}
                                >
                                    <Icon className="size-5" />
                                </div>
                                <p className="font-semibold">{option.title}</p>
                                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
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
};
