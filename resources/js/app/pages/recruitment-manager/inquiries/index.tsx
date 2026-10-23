import { Link } from '@inertiajs/react';
import { ChevronDown, FileText, Inbox } from 'lucide-react';
import { cn } from '@/lib/utils';
import { index } from '@/routes/recruitment-manager/inquiries';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Inquiry = {
    id: number;
    type: 'candidate' | 'recruiter';
    name: string;
    subtitle: string | null;
    submitted_at: string | null;
    is_new: boolean;
    fields: { label: string; value: string }[];
    documents: { label: string; url: string }[];
};

type Props = {
    type: 'candidate' | 'recruiter';
    counts: { candidate: number; recruiter: number };
    inquiries: {
        data: Inquiry[];
        links: { url: string | null; label: string; active: boolean }[];
    };
};

const tabs = [
    { type: 'candidate', label: 'Candidates' },
    { type: 'recruiter', label: 'Recruiters' },
] as const;

function initials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase())
        .join('');
}

export default function InquiriesIndex({ type, counts, inquiries }: Props) {
    return (
        <>
            <PrivateSeoHead title="Inquiries" />
            <div className="flex flex-col gap-5 p-2 sm:px-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Inquiries
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Forms submitted from the website by candidates and
                        recruiters.
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-1 rounded-full bg-muted p-1 md:max-w-sm">
                    {tabs.map((tab) => (
                        <Link
                            key={tab.type}
                            href={index({ query: { type: tab.type } })}
                            preserveScroll
                            className={cn(
                                'flex h-10 items-center justify-center gap-2 rounded-full text-sm font-medium transition-colors',
                                tab.type === type
                                    ? 'bg-background shadow-sm'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {tab.label}
                            <span className="rounded-full bg-foreground/10 px-2 py-0.5 text-[11px] tabular-nums">
                                {counts[tab.type]}
                            </span>
                        </Link>
                    ))}
                </div>

                {inquiries.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-3xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                        <Inbox className="size-8" />
                        No {type} inquiries yet.
                    </div>
                ) : (
                    <div className="flex flex-col gap-3 md:max-w-3xl">
                        {inquiries.data.map((inquiry) => (
                            <details
                                key={inquiry.id}
                                className="group overflow-hidden rounded-3xl border border-border bg-card shadow-sm"
                            >
                                <summary className="flex cursor-pointer list-none items-center gap-3 p-4 transition-colors select-none active:bg-muted/50 [&::-webkit-details-marker]:hidden">
                                    <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-warm/15 text-sm font-semibold text-brand-warm">
                                        {initials(inquiry.name)}
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="flex items-center gap-2">
                                            <span className="truncate text-base font-semibold capitalize">
                                                {inquiry.name}
                                            </span>
                                            {inquiry.is_new && (
                                                <span className="shrink-0 rounded-full bg-brand-warm/15 px-2 py-0.5 text-[11px] font-medium text-brand-warm">
                                                    New
                                                </span>
                                            )}
                                        </span>
                                        <span className="block truncate text-xs text-muted-foreground">
                                            {[
                                                inquiry.subtitle,
                                                inquiry.submitted_at &&
                                                    new Date(
                                                        inquiry.submitted_at,
                                                    ).toLocaleDateString(
                                                        undefined,
                                                        {
                                                            day: 'numeric',
                                                            month: 'short',
                                                            year: 'numeric',
                                                        },
                                                    ),
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </span>
                                    </span>
                                    <ChevronDown className="size-5 shrink-0 text-muted-foreground transition-transform group-open:rotate-180" />
                                </summary>

                                <div className="flex flex-col gap-4 border-t border-border/70 bg-muted/30 p-4">
                                    <dl className="grid gap-3 sm:grid-cols-2">
                                        {inquiry.fields.map((field) => (
                                            <div
                                                key={field.label}
                                                className="min-w-0"
                                            >
                                                <dt className="text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
                                                    {field.label}
                                                </dt>
                                                <dd className="text-sm break-words whitespace-pre-line">
                                                    {field.value}
                                                </dd>
                                            </div>
                                        ))}
                                    </dl>

                                    {inquiry.documents.length > 0 && (
                                        <div className="flex flex-wrap gap-2">
                                            {inquiry.documents.map((doc) => (
                                                <a
                                                    key={doc.label}
                                                    href={doc.url}
                                                    className="inline-flex h-10 items-center gap-2 rounded-full border border-border bg-background px-4 text-sm font-medium transition-transform active:scale-95"
                                                >
                                                    <FileText className="size-4" />
                                                    {doc.label}
                                                </a>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </details>
                        ))}
                    </div>
                )}

                {inquiries.links.length > 3 && (
                    <nav className="flex flex-wrap gap-1">
                        {inquiries.links.map((link, i) =>
                            link.url ? (
                                <Link
                                    key={i}
                                    href={link.url}
                                    preserveScroll
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
