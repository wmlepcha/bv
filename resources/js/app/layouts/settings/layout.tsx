import { Link, router } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { logout } from '@/routes';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';
import { FormGroup } from '@app/components/native/form';
import { AppearanceToggle } from '@shared/components/appearance-toggle';
import Heading from '@shared/components/heading';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: edit(),
        icon: null,
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: null,
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="px-2 py-4 sm:px-4 md:py-6">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <div className="mt-4 flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full lg:w-48">
                    <nav
                        className="flex rounded-xl bg-muted p-1 lg:flex-col lg:space-y-1 lg:bg-transparent lg:p-0"
                        aria-label="Settings"
                    >
                        {sidebarNavItems.map((item, index) => (
                            <Link
                                key={`${toUrl(item.href)}-${index}`}
                                href={item.href}
                                className={cn(
                                    'flex h-9 flex-1 items-center justify-center rounded-lg px-3 text-sm font-medium transition-all active:scale-95 lg:flex-none lg:justify-start',
                                    isCurrentOrParentUrl(item.href)
                                        ? 'bg-background text-foreground shadow-sm lg:bg-muted lg:shadow-none'
                                        : 'text-muted-foreground lg:hover:bg-muted/60',
                                )}
                            >
                                {item.title}
                            </Link>
                        ))}
                    </nav>
                </aside>

                <div className="mt-5 flex-1 md:max-w-2xl lg:mt-0 lg:max-w-3xl">
                    <FormGroup className="mb-5 md:hidden">
                        <div className="flex min-h-12 items-center justify-between gap-3 px-4">
                            <span className="text-sm font-medium">Theme</span>
                            <AppearanceToggle />
                        </div>
                        <Link
                            href={logout()}
                            as="button"
                            onClick={() => router.flushAll()}
                            className="flex min-h-12 w-full items-center gap-3 px-4 text-left text-sm font-medium text-destructive active:bg-destructive/10"
                        >
                            <LogOut className="size-4" />
                            Log out
                        </Link>
                    </FormGroup>

                    <section className="w-full max-w-none space-y-8 md:max-w-2xl lg:max-w-3xl">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
