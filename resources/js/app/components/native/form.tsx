import { Check, ChevronRight, Eye, EyeOff } from 'lucide-react';
import { useId, useState } from 'react';
import type { ComponentProps, ReactNode, Ref } from 'react';
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerTitle,
} from '@/components/ui/drawer';
import { cn } from '@/lib/utils';
import { haptic } from '@app/lib/native';
import InputError from '@shared/components/input-error';

/** Page wrapper: large iOS-style title on phones, regular heading on desktop. */
export function NativePage({
    title,
    description,
    children,
    className,
}: {
    title: string;
    description?: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'mx-auto flex w-full max-w-2xl flex-col gap-6 p-2 pb-8 sm:px-4',
                className,
            )}
        >
            <header>
                <h1 className="text-3xl font-bold tracking-tight md:text-2xl md:font-semibold">
                    {title}
                </h1>
                {description && (
                    <p className="mt-1 text-sm text-muted-foreground md:mt-2">
                        {description}
                    </p>
                )}
            </header>
            {children}
        </div>
    );
}

/** iOS inset-grouped section: small caps header, rounded card of rows, footnote. */
export function FormGroup({
    title,
    footer,
    children,
    className,
}: {
    title?: string;
    footer?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section className={cn('flex flex-col gap-1.5', className)}>
            {title && (
                <h2 className="px-4 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    {title}
                </h2>
            )}
            <div className="divide-y divide-border/70 overflow-hidden rounded-2xl border border-border bg-card/70 shadow-sm">
                {children}
            </div>
            {footer && (
                <p className="px-4 text-xs text-muted-foreground">{footer}</p>
            )}
        </section>
    );
}

const rowBase = 'flex min-h-11 items-center gap-3 px-4 py-1.5';

/** A label + custom control row. */
export function FormRow({
    label,
    htmlFor,
    error,
    children,
    stacked = false,
}: {
    label: string;
    htmlFor?: string;
    error?: string;
    children: ReactNode;
    stacked?: boolean;
}) {
    return (
        <div>
            <div
                className={cn(
                    rowBase,
                    stacked && 'flex-col items-stretch gap-1 py-3',
                )}
            >
                <label
                    htmlFor={htmlFor}
                    className={cn(
                        'shrink-0 text-sm font-medium',
                        !stacked && 'w-32 sm:w-44',
                    )}
                >
                    {label}
                </label>
                <div className={cn('min-w-0 flex-1', !stacked && 'text-right')}>
                    {children}
                </div>
            </div>
            {error && <InputError className="px-4 pb-2" message={error} />}
        </div>
    );
}

const bareInput =
    'h-11 w-full bg-transparent text-base outline-none placeholder:text-muted-foreground/70 disabled:opacity-60 md:text-sm';

export function InputRow({
    label,
    error,
    className,
    id,
    ...input
}: { label: string; error?: string } & ComponentProps<'input'>) {
    const generated = useId();
    const inputId = id ?? generated;

    return (
        <FormRow label={label} htmlFor={inputId} error={error}>
            <input
                id={inputId}
                className={cn(bareInput, 'text-right sm:text-left', className)}
                aria-invalid={error ? true : undefined}
                {...input}
            />
        </FormRow>
    );
}

export function PasswordRow({
    label,
    error,
    className,
    id,
    ref,
    ...input
}: { label: string; error?: string; ref?: Ref<HTMLInputElement> } & Omit<
    ComponentProps<'input'>,
    'type'
>) {
    const generated = useId();
    const inputId = id ?? generated;
    const [visible, setVisible] = useState(false);

    return (
        <FormRow label={label} htmlFor={inputId} error={error}>
            <div className="flex items-center gap-2">
                <input
                    id={inputId}
                    ref={ref}
                    type={visible ? 'text' : 'password'}
                    className={cn(
                        bareInput,
                        'text-right sm:text-left',
                        className,
                    )}
                    aria-invalid={error ? true : undefined}
                    {...input}
                />
                <button
                    type="button"
                    onClick={() => setVisible((value) => !value)}
                    aria-label={visible ? 'Hide password' : 'Show password'}
                    className="flex size-9 shrink-0 items-center justify-center rounded-full text-muted-foreground active:bg-muted"
                >
                    {visible ? (
                        <EyeOff className="size-4" />
                    ) : (
                        <Eye className="size-4" />
                    )}
                </button>
            </div>
        </FormRow>
    );
}

export function TextAreaRow({
    label,
    error,
    className,
    id,
    ...textarea
}: { label: string; error?: string } & ComponentProps<'textarea'>) {
    const generated = useId();
    const textareaId = id ?? generated;

    return (
        <FormRow label={label} htmlFor={textareaId} error={error} stacked>
            <textarea
                id={textareaId}
                rows={4}
                className={cn(
                    'min-h-24 w-full resize-none bg-transparent text-base outline-none placeholder:text-muted-foreground/70 md:text-sm',
                    className,
                )}
                aria-invalid={error ? true : undefined}
                {...textarea}
            />
        </FormRow>
    );
}

export function DateRow({
    label,
    error,
    ...input
}: { label: string; error?: string } & Omit<ComponentProps<'input'>, 'type'>) {
    return <InputRow label={label} error={error} type="date" {...input} />;
}

export function SwitchRow({
    label,
    description,
    checked,
    onCheckedChange,
}: {
    label: string;
    description?: string;
    checked: boolean;
    onCheckedChange: (checked: boolean) => void;
}) {
    return (
        <div className={cn(rowBase, 'justify-between py-2.5')}>
            <div className="min-w-0">
                <p className="text-sm font-medium">{label}</p>
                {description && (
                    <p className="text-xs text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            <button
                type="button"
                role="switch"
                aria-checked={checked}
                aria-label={label}
                onClick={() => {
                    haptic();
                    onCheckedChange(!checked);
                }}
                className={cn(
                    'relative h-8 w-14 shrink-0 rounded-full transition-colors',
                    checked ? 'bg-emerald-500' : 'bg-muted-foreground/30',
                )}
            >
                <span
                    className={cn(
                        'absolute top-1 left-1 size-6 rounded-full bg-white shadow transition-transform',
                        checked && 'translate-x-6',
                    )}
                />
            </button>
        </div>
    );
}

export function SegmentedControl<T extends string>({
    value,
    onChange,
    options,
    label,
}: {
    value: T;
    onChange: (value: T) => void;
    options: { value: T; label: string }[];
    label: string;
}) {
    return (
        <div
            role="radiogroup"
            aria-label={label}
            className="flex rounded-xl bg-muted p-1"
        >
            {options.map((option) => (
                <button
                    key={option.value}
                    type="button"
                    role="radio"
                    aria-checked={value === option.value}
                    onClick={() => {
                        haptic();
                        onChange(option.value);
                    }}
                    className={cn(
                        'h-9 flex-1 rounded-lg text-sm font-medium transition-all',
                        value === option.value
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground',
                    )}
                >
                    {option.label}
                </button>
            ))}
        </div>
    );
}

/** A select rendered as an iOS row that opens a bottom list instead of a desktop popover. */
export function PickerRow({
    label,
    value,
    onChange,
    options,
    placeholder = 'Select',
    error,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    options: { value: string; label: string }[];
    placeholder?: string;
    error?: string;
}) {
    const [open, setOpen] = useState(false);
    const selected = options.find((option) => option.value === value);

    return (
        <>
            <FormRow label={label} error={error}>
                <button
                    type="button"
                    onClick={() => setOpen(true)}
                    aria-haspopup="listbox"
                    className="flex h-11 w-full items-center justify-end gap-1.5 text-base md:text-sm"
                >
                    <span
                        className={cn(
                            'truncate',
                            !selected && 'text-muted-foreground/70',
                        )}
                    >
                        {selected?.label ?? placeholder}
                    </span>
                    <ChevronRight className="size-4 shrink-0 text-muted-foreground/60" />
                </button>
            </FormRow>

            <Drawer open={open} onOpenChange={setOpen}>
                <DrawerContent>
                    <div className="px-5 pt-2 pb-2">
                        <DrawerTitle>{label}</DrawerTitle>
                        <DrawerDescription className="sr-only">
                            Choose an option
                        </DrawerDescription>
                    </div>
                    <ul
                        role="listbox"
                        className="min-h-0 flex-1 overflow-y-auto px-3 pb-[calc(1rem+env(safe-area-inset-bottom))]"
                    >
                        {options.map((option) => (
                            <li key={option.value}>
                                <button
                                    type="button"
                                    role="option"
                                    aria-selected={option.value === value}
                                    onClick={() => {
                                        haptic();
                                        onChange(option.value);
                                        setOpen(false);
                                    }}
                                    className="flex min-h-12 w-full items-center justify-between gap-3 rounded-xl px-3 text-left text-base active:bg-muted"
                                >
                                    {option.label}
                                    {option.value === value && (
                                        <Check className="size-5 text-primary" />
                                    )}
                                </button>
                            </li>
                        ))}
                    </ul>
                </DrawerContent>
            </Drawer>
        </>
    );
}

/** Sticky primary action that stays above the tab bar while a long form scrolls. */
export function ActionBar({ children }: { children: ReactNode }) {
    return (
        <div className="sticky bottom-[var(--app-bottom-h,0px)] z-10 -mx-2 border-t border-border/60 bg-background/90 px-4 py-3 backdrop-blur-xl sm:-mx-4 md:bottom-0">
            <div className="mx-auto flex max-w-2xl gap-3">{children}</div>
        </div>
    );
}
