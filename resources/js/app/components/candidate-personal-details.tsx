import {
    Cake,
    Car,
    ChevronRight,
    Clock,
    Flag,
    GraduationCap,
    Heart,
    House,
    IdCard,
    Mail,
    MapPin,
    MessageCircle,
    Phone,
    Sparkles,
    User,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import type { CvProfile } from '@app/types/cv';

function formatDate(value: string | null): string | null {
    if (!value) {
        return null;
    }

    const date = new Date(`${value}T00:00:00`);

    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleDateString('en-GB', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          });
}

function ageFrom(value: string | null): number | null {
    const date = value ? new Date(`${value}T00:00:00`) : null;

    if (!date || Number.isNaN(date.getTime())) {
        return null;
    }

    const now = new Date();
    const age = now.getFullYear() - date.getFullYear();
    const birthdayPassed =
        now.getMonth() > date.getMonth() ||
        (now.getMonth() === date.getMonth() && now.getDate() >= date.getDate());

    return birthdayPassed ? age : age - 1;
}

function digits(value: string): string {
    return value.replace(/\D/g, '');
}

function Card({
    title,
    children,
    className,
}: {
    title: string;
    icon?: LucideIcon;
    className?: string;
    children: ReactNode;
}) {
    return (
        <section className={cn('flex flex-col gap-2', className)}>
            <h2 className="px-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                {title}
            </h2>
            <div className="overflow-hidden rounded-3xl border border-border bg-card/60 shadow-sm">
                {children}
            </div>
        </section>
    );
}

function Field({
    label,
    icon: Icon,
    href,
    external,
    children,
}: {
    label: string;
    icon: LucideIcon;
    wide?: boolean;
    href?: string | null;
    external?: boolean;
    children?: ReactNode;
}) {
    const body = (
        <>
            <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-cool/15 text-brand-cool">
                <Icon className="size-4" />
            </span>
            <span className="min-w-0 flex-1 text-left">
                <span className="block text-xs text-muted-foreground">
                    {label}
                </span>
                <span className="block text-sm break-words">
                    {children || (
                        <span className="text-muted-foreground/60 italic">
                            Not provided
                        </span>
                    )}
                </span>
            </span>
            {href && children ? (
                <ChevronRight className="size-5 shrink-0 text-muted-foreground/60" />
            ) : null}
        </>
    );
    const rowClass =
        'flex w-full items-center gap-3 border-b border-border/60 px-4 py-3 last:border-b-0';

    return href && children ? (
        <a
            href={href}
            target={external ? '_blank' : undefined}
            rel="noreferrer"
            className={cn(rowClass, 'transition-colors active:bg-muted')}
        >
            {body}
        </a>
    ) : (
        <div className={rowClass}>{body}</div>
    );
}

function Pills({ items }: { items: string[] }) {
    return (
        <div className="flex flex-wrap gap-2">
            {items.map((item) => (
                <span
                    key={item}
                    className="rounded-full bg-brand-cool/15 px-3 py-1 text-xs font-semibold text-brand-cool"
                >
                    {item}
                </span>
            ))}
        </div>
    );
}

function splitList(value: string | null): string[] {
    return (value ?? '')
        .split(/[|,;\n]+/)
        .map((part) => part.trim())
        .filter(Boolean);
}

export default function CandidatePersonalDetails({ cv }: { cv: CvProfile }) {
    const age = ageFrom(cv.date_of_birth);
    const hobbies = splitList(cv.hobbies);
    const mapsUrl = (address: string | null) =>
        address
            ? `https://maps.google.com/?q=${encodeURIComponent(address)}`
            : null;

    return (
        <div className="grid items-start gap-5 lg:grid-cols-2">
            <Card title="Contact">
                <Field
                    label="Email"
                    icon={Mail}
                    href={cv.email ? `mailto:${cv.email}` : null}
                >
                    {cv.email}
                </Field>
                <Field
                    label="Secondary email"
                    icon={Mail}
                    href={
                        cv.secondary_email
                            ? `mailto:${cv.secondary_email}`
                            : null
                    }
                >
                    {cv.secondary_email}
                </Field>
                <Field
                    label="WhatsApp / mobile"
                    icon={MessageCircle}
                    href={
                        cv.mobile_whatsapp
                            ? `https://wa.me/${digits(cv.mobile_whatsapp)}`
                            : null
                    }
                    external
                >
                    {cv.mobile_whatsapp}
                </Field>
                <Field
                    label="Secondary mobile"
                    icon={Phone}
                    href={
                        cv.secondary_mobile
                            ? `tel:${cv.secondary_mobile}`
                            : null
                    }
                >
                    {cv.secondary_mobile}
                </Field>
                <Field
                    label="Current address"
                    icon={MapPin}
                    href={mapsUrl(cv.current_address)}
                    external
                >
                    {cv.current_address}
                </Field>
                <Field
                    label="Permanent address"
                    icon={House}
                    href={mapsUrl(cv.permanent_address)}
                    external
                >
                    {cv.permanent_address}
                </Field>
            </Card>

            <Card title="Personal">
                <Field label="Date of birth" icon={Cake}>
                    {cv.date_of_birth && (
                        <>
                            {formatDate(cv.date_of_birth)}
                            {age !== null && (
                                <span className="text-muted-foreground">
                                    {' '}
                                    · {age} yrs
                                </span>
                            )}
                        </>
                    )}
                </Field>
                <Field label="Place of birth" icon={MapPin}>
                    {cv.place_of_birth}
                </Field>
                <Field label="Gender" icon={User}>
                    {cv.gender}
                </Field>
                <Field label="Civil status" icon={Heart}>
                    {cv.civil_status}
                </Field>
                <Field label="Nationality" icon={Flag}>
                    {cv.nationality}
                </Field>
                <Field label="Passport / ID number" icon={IdCard}>
                    {cv.passport_or_id_number}
                </Field>
                <Field label="Driver's license" icon={Car}>
                    {cv.drivers_license}
                </Field>
            </Card>

            <Card title="Status & interests" className="lg:col-span-2">
                <Field label="Current status" icon={Clock}>
                    {cv.current_status && (
                        <span className="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold">
                            {cv.current_status}
                        </span>
                    )}
                </Field>
                <Field label="Pursuing" icon={GraduationCap}>
                    {cv.pursuing_details}
                </Field>
                <Field label="Gap reasons" icon={Users}>
                    {cv.gap_reasons}
                </Field>
                <Field label="Skills" icon={Sparkles}>
                    {cv.skills.length > 0 && <Pills items={cv.skills} />}
                </Field>
                <Field label="Hobbies & interests" icon={Heart}>
                    {hobbies.length > 0 && <Pills items={hobbies} />}
                </Field>
            </Card>
        </div>
    );
}
