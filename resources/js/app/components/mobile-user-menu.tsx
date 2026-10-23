import { Link, router } from '@inertiajs/react';
import { LogOut, Shield, ShieldCheck, UserPen } from 'lucide-react';
import { useState } from 'react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
} from '@/components/ui/responsive-sheet';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { User } from '@/types';
import { UserAvatar } from '@app/components/user-avatar';
import { AppearanceToggle } from '@shared/components/appearance-toggle';

const rowClass =
    'flex w-full items-center gap-3 rounded-2xl px-3 py-3.5 text-base font-medium transition-colors active:bg-muted';

export function MobileUserMenu({ user }: { user: User }) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                aria-label="Account menu"
                aria-haspopup="dialog"
                className="rounded-full ring-2 ring-transparent transition-transform active:scale-90"
            >
                <UserAvatar user={user} className="size-9" />
            </button>

            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent
                    side="bottom"
                    className="rounded-t-3xl px-4 pt-3 pb-[calc(1.5rem+env(safe-area-inset-bottom))]"
                >
                    <div className="flex items-center gap-3 px-1">
                        <UserAvatar user={user} className="size-14" />
                        <div className="min-w-0">
                            <SheetTitle className="truncate text-lg">
                                {user.name}
                            </SheetTitle>
                            <SheetDescription className="truncate">
                                {user.email}
                            </SheetDescription>
                        </div>
                    </div>

                    <ul className="grid gap-1">
                        <li>
                            <Link
                                href={editProfile()}
                                prefetch
                                onClick={() => setOpen(false)}
                                className={rowClass}
                            >
                                <UserPen className="size-5" />
                                Edit profile
                            </Link>
                        </li>
                        <li>
                            <Link
                                href={editSecurity()}
                                prefetch
                                onClick={() => setOpen(false)}
                                className={rowClass}
                            >
                                <ShieldCheck className="size-5" />
                                Security
                            </Link>
                        </li>
                        {user.role === 'admin' && (
                            <li>
                                <a href="/bv-admin" className={rowClass}>
                                    <Shield className="size-5" />
                                    Admin panel
                                </a>
                            </li>
                        )}
                        <li className={cn(rowClass, 'justify-between')}>
                            <span>Theme</span>
                            <AppearanceToggle />
                        </li>
                        <li>
                            <Link
                                href={logout()}
                                as="button"
                                onClick={() => {
                                    setOpen(false);
                                    router.flushAll();
                                }}
                                className={cn(
                                    rowClass,
                                    'text-destructive active:bg-destructive/10',
                                )}
                            >
                                <LogOut className="size-5" />
                                Log out
                            </Link>
                        </li>
                    </ul>
                </SheetContent>
            </Sheet>
        </>
    );
}
