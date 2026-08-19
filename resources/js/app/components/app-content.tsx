import * as React from 'react';
import { SidebarInset } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { AppVariant } from '@/types';

type Props = React.ComponentProps<'main'> & {
    variant?: AppVariant;
};

export function AppContent({
    variant = 'sidebar',
    children,
    className,
    ...props
}: Props) {
    if (variant === 'sidebar') {
        return (
            <SidebarInset
                className={cn(
                    'border-0 border-l-0 bg-background shadow-none',
                    // Flush left into the rail so the cut-out can merge (no card border)
                    'md:peer-data-[variant=inset]:m-0 md:peer-data-[variant=inset]:ml-0',
                    'md:peer-data-[variant=inset]:min-h-svh',
                    'md:peer-data-[variant=inset]:rounded-none md:peer-data-[variant=inset]:rounded-l-none',
                    'md:peer-data-[variant=inset]:shadow-none',
                    className,
                )}
                {...props}
            >
                {children}
            </SidebarInset>
        );
    }

    return (
        <main
            className={cn(
                'mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl',
                className,
            )}
            {...props}
        >
            {children}
        </main>
    );
}
