import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
    { value: 'light', icon: Sun, label: 'Light' },
    { value: 'dark', icon: Moon, label: 'Dark' },
    { value: 'system', icon: Monitor, label: 'System' },
];

type AppearanceToggleProps = {
    className?: string;
    /**
     * Locked styles for the white dashboard rail (ignores dark content theme).
     * Header / marketing should keep the default theme-aware variant.
     */
    variant?: 'default' | 'onLightRail';
};

/** Compact light / dark / system control. */
export function AppearanceToggle({
    className,
    variant = 'default',
}: AppearanceToggleProps) {
    const { appearance, updateAppearance } = useAppearance();
    const onLightRail = variant === 'onLightRail';

    return (
        <div
            role="group"
            aria-label="Appearance"
            className={cn(
                'inline-flex items-center gap-0.5 rounded-full border p-0.5',
                onLightRail
                    ? 'border-[#031835]/15 bg-[#031835]/[0.04]'
                    : 'border-foreground/10 bg-foreground/[0.03]',
                className,
            )}
        >
            {tabs.map(({ value, icon: Icon, label }) => {
                const isActive = appearance === value;

                return (
                    <button
                        key={value}
                        type="button"
                        aria-label={label}
                        aria-pressed={isActive}
                        title={label}
                        onClick={() => updateAppearance(value)}
                        className={cn(
                            'flex size-8 items-center justify-center rounded-full transition-colors',
                            isActive
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : onLightRail
                                  ? 'text-[#031835]/60 hover:bg-[#031835]/[0.08] hover:text-[#031835]'
                                  : 'text-foreground/60 hover:bg-foreground/[0.06] hover:text-foreground',
                        )}
                    >
                        <Icon className="size-3.5" aria-hidden />
                    </button>
                );
            })}
        </div>
    );
}
