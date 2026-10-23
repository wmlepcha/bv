import { Check, Monitor, Moon, Sun } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { Appearance as AppearanceMode } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { edit as editAppearance } from '@/routes/appearance';
import { FormGroup } from '@app/components/native/form';
import { haptic } from '@app/lib/native';
import { PrivateSeoHead } from '@shared/components/seo-head';

const OPTIONS: { value: AppearanceMode; label: string; icon: LucideIcon }[] = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
];

export default function Appearance() {
    const { appearance, updateAppearance } = useAppearance();

    return (
        <>
            <PrivateSeoHead title="Appearance settings" />

            <h1 className="sr-only">Appearance settings</h1>

            <FormGroup
                title="Theme"
                footer="System follows your device's light or dark setting."
            >
                {OPTIONS.map(({ value, label, icon: Icon }) => (
                    <button
                        key={value}
                        type="button"
                        role="radio"
                        aria-checked={appearance === value}
                        onClick={() => {
                            haptic();
                            updateAppearance(value);
                        }}
                        className="flex min-h-12 w-full items-center gap-3 px-4 text-left text-base active:bg-muted md:text-sm"
                    >
                        <Icon className="size-5 text-muted-foreground" />
                        <span className="flex-1">{label}</span>
                        {appearance === value && (
                            <Check className="size-5 text-primary" />
                        )}
                    </button>
                ))}
            </FormGroup>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Appearance settings',
            href: editAppearance(),
        },
    ],
};
