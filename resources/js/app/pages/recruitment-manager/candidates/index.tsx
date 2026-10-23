import { Link, router } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/responsive-select';
import { cn } from '@/lib/utils';
import { show as studentShow } from '@/routes/recruiter/students';
import {
    index as candidatesIndex,
    update as updateCandidate,
} from '@/routes/recruitment-manager/candidates';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Profile = {
    id: number;
    name: string;
    email: string;
    student_number: string | null;
    batch: string | null;
    registration_purpose: string;
    placement_status: string;
    placement_requested: boolean;
};

type Props = {
    profiles: {
        data: Profile[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q: string; purpose: string; status: string; requested: boolean };
    purposes: Record<string, string>;
    statuses: Record<string, string>;
};

const ALL = '__all__';

function PickSelect({
    value,
    onChange,
    options,
    allLabel,
    title,
    className,
}: {
    value: string;
    onChange: (value: string) => void;
    options: Record<string, string>;
    allLabel?: string;
    title: string;
    className?: string;
}) {
    return (
        <Select
            value={value === '' ? ALL : value}
            onValueChange={(next) => onChange(next === ALL ? '' : next)}
        >
            <SelectTrigger
                className={cn('h-10 w-full rounded-xl text-sm', className)}
            >
                <SelectValue placeholder={title} />
            </SelectTrigger>
            <SelectContent>
                {allLabel && <SelectItem value={ALL}>{allLabel}</SelectItem>}
                {Object.entries(options).map(([val, label]) => (
                    <SelectItem key={val} value={val}>
                        {label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

export default function CandidatesIndex({
    profiles,
    filters,
    purposes,
    statuses,
}: Props) {
    const [q, setQ] = useState(filters.q);

    const filter = (patch: Partial<Props['filters']>) =>
        router.get(
            candidatesIndex.url(),
            { ...filters, ...patch },
            { preserveState: true, replace: true },
        );

    const save = (id: number, patch: Record<string, string>) =>
        router.put(updateCandidate.url(id), patch, { preserveScroll: true });

    return (
        <>
            <PrivateSeoHead title="Candidates" />
            <div className="flex flex-col gap-5 p-2 sm:px-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Candidates
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Switch what a student registered for and mark who is
                        placed. Recruiters only see Training + Placement
                        students.
                    </p>
                </div>

                <div className="sticky top-[var(--app-top-h,0px)] z-20 -mx-2 grid grid-cols-2 gap-2 border-b border-border/60 bg-background/95 px-2 py-2.5 backdrop-blur sm:-mx-4 sm:flex sm:flex-wrap sm:items-center sm:px-4">
                    <form
                        className="col-span-2 min-w-48 sm:flex-1"
                        onSubmit={(e) => {
                            e.preventDefault();
                            filter({ q });
                        }}
                    >
                        <Input
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Search name or candidate ID"
                        />
                    </form>
                    <PickSelect
                        title="Purpose"
                        allLabel="All purposes"
                        options={purposes}
                        value={filters.purpose}
                        onChange={(purpose) => filter({ purpose })}
                        className="sm:w-48"
                    />
                    <PickSelect
                        title="Status"
                        allLabel="Any status"
                        options={statuses}
                        value={filters.status}
                        onChange={(status) => filter({ status })}
                        className="sm:w-44"
                    />
                    <button
                        type="button"
                        aria-pressed={filters.requested}
                        onClick={() =>
                            filter({ requested: !filters.requested })
                        }
                        className={cn(
                            'col-span-2 flex h-10 items-center justify-center gap-2 rounded-xl border text-sm font-medium transition-colors',
                            filters.requested
                                ? 'border-brand-warm bg-brand-warm/10 text-brand-warm'
                                : 'border-input text-muted-foreground',
                        )}
                    >
                        <span
                            className={cn(
                                'size-2 rounded-full',
                                filters.requested
                                    ? 'bg-brand-warm'
                                    : 'bg-muted-foreground/40',
                            )}
                        />
                        Requested placement only
                    </button>
                </div>

                <div className="flex flex-col gap-3 md:hidden">
                    {profiles.data.map((profile) => (
                        <div
                            key={profile.id}
                            className={cn(
                                'overflow-hidden rounded-3xl border bg-card shadow-sm',
                                profile.placement_requested
                                    ? 'border-brand-warm/60'
                                    : 'border-border',
                            )}
                        >
                            <Link
                                href={studentShow(profile.id)}
                                className="flex items-center gap-3 p-4 active:bg-muted/50"
                            >
                                <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-warm/15 text-sm font-semibold text-brand-warm">
                                    {profile.name
                                        .split(' ')
                                        .slice(0, 2)
                                        .map((word) => word[0]?.toUpperCase())
                                        .join('')}
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-base font-semibold capitalize">
                                        {profile.name}
                                    </span>
                                    <span className="block truncate text-xs text-muted-foreground">
                                        {profile.student_number ??
                                            profile.email}
                                    </span>
                                    <span className="mt-1 flex flex-wrap gap-1.5">
                                        <span className="rounded-full bg-muted px-2 py-0.5 text-[11px] text-muted-foreground">
                                            {profile.batch ?? 'No batch'}
                                        </span>
                                        {profile.placement_requested && (
                                            <span className="rounded-full bg-brand-warm/15 px-2 py-0.5 text-[11px] font-medium text-brand-warm">
                                                Placement requested
                                            </span>
                                        )}
                                    </span>
                                </span>
                                <ChevronRight className="size-5 shrink-0 text-muted-foreground" />
                            </Link>
                            <div className="grid grid-cols-[1.25fr_1fr] gap-2 border-t border-border/70 bg-muted/30 p-3">
                                <div className="flex min-w-0 flex-col gap-1">
                                    <span className="px-1 text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
                                        Registered for
                                    </span>
                                    <PickSelect
                                        title="Registered for"
                                        options={purposes}
                                        value={profile.registration_purpose}
                                        onChange={(registration_purpose) =>
                                            save(profile.id, {
                                                registration_purpose,
                                            })
                                        }
                                        className="h-9 bg-background px-2.5 text-[13px]"
                                    />
                                </div>
                                <div className="flex min-w-0 flex-col gap-1">
                                    <span className="px-1 text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
                                        Status
                                    </span>
                                    <PickSelect
                                        title="Status"
                                        options={statuses}
                                        value={profile.placement_status}
                                        onChange={(placement_status) =>
                                            save(profile.id, {
                                                placement_status,
                                            })
                                        }
                                        className="h-9 bg-background px-2.5 text-[13px]"
                                    />
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="hidden overflow-x-auto rounded-2xl border border-border bg-card/70 md:block">
                    <table className="w-full min-w-[720px] text-left text-sm">
                        <thead className="border-b border-border text-xs text-muted-foreground">
                            <tr>
                                <th className="p-3 font-medium">Candidate</th>
                                <th className="p-3 font-medium">Batch</th>
                                <th className="p-3 font-medium">
                                    Registered for
                                </th>
                                <th className="p-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/70">
                            {profiles.data.map((profile) => (
                                <tr key={profile.id}>
                                    <td className="p-3">
                                        <Link
                                            href={studentShow(profile.id)}
                                            className="font-medium hover:underline"
                                        >
                                            {profile.name}
                                        </Link>
                                        <p className="text-xs text-muted-foreground">
                                            {[
                                                profile.student_number,
                                                profile.email,
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </p>
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {profile.batch ?? '—'}
                                    </td>
                                    <td className="p-3">
                                        <PickSelect
                                            title="Registered for"
                                            options={purposes}
                                            value={profile.registration_purpose}
                                            onChange={(registration_purpose) =>
                                                save(profile.id, {
                                                    registration_purpose,
                                                })
                                            }
                                            className={cn(
                                                'w-44',
                                                profile.placement_requested &&
                                                    'border-brand-warm',
                                            )}
                                        />
                                        {profile.placement_requested && (
                                            <p className="mt-1 text-xs text-brand-warm">
                                                Placement requested
                                            </p>
                                        )}
                                    </td>
                                    <td className="p-3">
                                        <PickSelect
                                            title="Status"
                                            options={statuses}
                                            value={profile.placement_status}
                                            onChange={(placement_status) =>
                                                save(profile.id, {
                                                    placement_status,
                                                })
                                            }
                                            className="w-44"
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {profiles.links.length > 3 && (
                    <nav className="flex flex-wrap gap-1">
                        {profiles.links.map((link, index) =>
                            link.url ? (
                                <Link
                                    key={index}
                                    href={link.url}
                                    className={cn(
                                        'rounded-full px-3 py-1 text-xs',
                                        link.active
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-muted hover:bg-muted/70',
                                    )}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ) : null,
                        )}
                    </nav>
                )}
            </div>
        </>
    );
}
