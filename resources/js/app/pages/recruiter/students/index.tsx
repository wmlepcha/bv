import { router } from '@inertiajs/react';
import {
    Briefcase,
    Languages,
    Search,
    SlidersHorizontal,
    Stethoscope,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { index as studentsIndex } from '@/routes/recruiter/students';
import CandidateCard from '@app/components/candidate-card';
import FilterSelect from '@app/components/filter-select';
import StudentsFilterSheet from '@app/components/students-filter-sheet';
import type { CandidateCard as CandidateCardData } from '@app/types/recruiter';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Filters = {
    profession: string | null;
    experience: string | null;
    level: string | null;
    status: string | null;
    q: string | null;
};

type Props = {
    profiles: {
        total?: number;
        data: CandidateCardData[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: Filters;
    filterOptions: {
        professions: Record<string, string>;
        levels: Record<string, string>;
    };
};

export default function StudentsIndex({
    profiles,
    filters,
    filterOptions,
}: Props) {
    const apply = (next: Partial<Filters>) => {
        const merged = { ...filters, ...next };
        const query = Object.fromEntries(
            Object.entries(merged).filter(([, value]) => value),
        );

        router.get(studentsIndex.url(), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const hasFilters = Object.values(filters).some(Boolean);
    const [filterSheetOpen, setFilterSheetOpen] = useState(false);
    const [query, setQuery] = useState(filters.q ?? '');
    const appliedQuery = filters.q ?? '';

    const [previousApplied, setPreviousApplied] = useState(appliedQuery);

    if (previousApplied !== appliedQuery) {
        setPreviousApplied(appliedQuery);
        setQuery(appliedQuery);
    }

    useEffect(() => {
        if (query === appliedQuery) {
            return;
        }

        const timer = window.setTimeout(() => apply({ q: query }), 350);

        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [query]);

    const clearAll = () => {
        setQuery('');
        router.get(studentsIndex.url(), {}, { replace: true });
    };
    const activeFilterCount = [
        filters.profession,
        filters.experience,
        filters.level,
    ].filter(Boolean).length;

    return (
        <>
            <PrivateSeoHead title="Students" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight md:text-2xl md:font-semibold">
                        Students
                    </h1>
                    <p className="mt-1 flex items-center gap-3 text-sm text-muted-foreground md:mt-2">
                        {profiles.total !== undefined
                            ? `${profiles.total} ${profiles.total === 1 ? 'candidate' : 'candidates'}`
                            : 'Browse candidates and find the right fit.'}

                        {hasFilters && (
                            <button
                                type="button"
                                onClick={clearAll}
                                className="font-medium text-primary active:opacity-60 md:hidden"
                            >
                                Clear all
                            </button>
                        )}
                    </p>
                </div>

                <div className="sticky top-[var(--app-top-h,0px)] z-20 -mx-2 flex flex-col gap-3 border-b border-border/60 bg-background/95 px-2 py-3 backdrop-blur sm:-mx-4 sm:px-4">
                    <div
                        role="radiogroup"
                        aria-label="Availability"
                        className="inline-flex w-full rounded-full border border-border bg-card p-1 shadow-sm md:w-fit [&>button]:flex-1 md:[&>button]:flex-none"
                    >
                        {(
                            [
                                ['', 'All'],
                                ['open', 'Open for work'],
                                ['hired', 'Hired'],
                            ] as const
                        ).map(([value, label]) => {
                            const selected = (filters.status ?? '') === value;

                            return (
                                <button
                                    key={value}
                                    type="button"
                                    role="radio"
                                    aria-checked={selected}
                                    onClick={() => apply({ status: value })}
                                    className={cn(
                                        'inline-flex h-8 items-center justify-center gap-1.5 rounded-full px-3 text-[13px] whitespace-nowrap transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none md:gap-2 md:px-4 md:text-sm',
                                        selected
                                            ? 'bg-primary font-medium text-primary-foreground shadow-sm'
                                            : 'text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    {value !== '' && (
                                        <span
                                            className={cn(
                                                'size-2 rounded-full',
                                                value === 'hired'
                                                    ? 'bg-destructive'
                                                    : 'bg-emerald-500',
                                            )}
                                        />
                                    )}
                                    {label}
                                </button>
                            );
                        })}
                    </div>

                    <div className="flex items-center gap-2 md:flex-wrap">
                        <div className="relative min-w-0 flex-1 md:flex-none md:shrink-0">
                            <Search className="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground" />
                            <input
                                type="search"
                                value={query}
                                onChange={(event) =>
                                    setQuery(event.target.value)
                                }
                                placeholder="Search by name"
                                aria-label="Search by name"
                                className="h-11 w-full rounded-full border border-border bg-card pr-10 pl-10 text-sm shadow-sm transition-colors outline-none placeholder:text-muted-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 md:h-10 md:w-56 [&::-webkit-search-cancel-button]:appearance-none"
                            />
                            {query && (
                                <button
                                    type="button"
                                    onClick={() => setQuery('')}
                                    aria-label="Clear search"
                                    className="absolute top-1/2 right-2 flex size-7 -translate-y-1/2 items-center justify-center rounded-full text-muted-foreground active:bg-muted"
                                >
                                    <X className="size-4" />
                                </button>
                            )}
                        </div>
                        <button
                            type="button"
                            onClick={() => setFilterSheetOpen(true)}
                            aria-label="Open filters"
                            className="relative flex size-11 shrink-0 items-center justify-center rounded-full border border-border bg-card shadow-sm transition-transform active:scale-90 md:hidden"
                        >
                            <SlidersHorizontal className="size-5" />
                            {activeFilterCount > 0 && (
                                <span className="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-primary-foreground">
                                    {activeFilterCount}
                                </span>
                            )}
                        </button>
                        <div className="hidden items-center gap-2 md:flex md:flex-wrap">
                            <FilterSelect
                                label="Profession"
                                icon={Stethoscope}
                                value={filters.profession}
                                allLabel="All professions"
                                options={Object.entries(
                                    filterOptions.professions,
                                ).map(([value, label]) => ({ value, label }))}
                                onChange={(profession) => apply({ profession })}
                            />
                            <FilterSelect
                                label="Experience"
                                icon={Briefcase}
                                value={filters.experience}
                                allLabel="Fresher & Experienced"
                                options={[
                                    { value: 'fresher', label: 'Fresher' },
                                    {
                                        value: 'experienced',
                                        label: 'Experienced',
                                    },
                                ]}
                                onChange={(experience) => apply({ experience })}
                            />
                            <FilterSelect
                                label="Language level"
                                icon={Languages}
                                value={filters.level}
                                allLabel="All language levels"
                                options={Object.entries(
                                    filterOptions.levels,
                                ).map(([value, label]) => ({ value, label }))}
                                onChange={(level) => apply({ level })}
                            />
                            {hasFilters && (
                                <button
                                    type="button"
                                    className="inline-flex h-10 items-center gap-1.5 rounded-full px-3 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                    onClick={clearAll}
                                >
                                    <X className="size-4" />
                                    Clear
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                <StudentsFilterSheet
                    open={filterSheetOpen}
                    onOpenChange={setFilterSheetOpen}
                    current={{
                        profession: filters.profession ?? '',
                        experience: filters.experience ?? '',
                        level: filters.level ?? '',
                    }}
                    professions={Object.entries(filterOptions.professions).map(
                        ([value, label]) => ({ value, label }),
                    )}
                    levels={Object.entries(filterOptions.levels).map(
                        ([value, label]) => ({ value, label }),
                    )}
                    onApply={apply}
                />

                {profiles.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-2 rounded-3xl border border-dashed border-border p-10 text-center">
                        <Search className="size-8 text-muted-foreground/60" />
                        <p className="text-sm text-muted-foreground">
                            No candidates match these filters.
                        </p>
                        {hasFilters && (
                            <Button
                                type="button"
                                variant="outline"
                                className="mt-2 rounded-full"
                                onClick={clearAll}
                            >
                                Clear all filters
                            </Button>
                        )}
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        {profiles.data.map((candidate) => (
                            <CandidateCard
                                key={candidate.id}
                                candidate={candidate}
                            />
                        ))}
                    </div>
                )}

                {profiles.links.length > 3 && (
                    <nav
                        className="flex flex-wrap gap-1"
                        aria-label="Pagination"
                    >
                        {profiles.links.map((link, index) => (
                            <button
                                key={index}
                                type="button"
                                disabled={!link.url || link.active}
                                onClick={() =>
                                    link.url &&
                                    router.get(
                                        link.url,
                                        {},
                                        { preserveState: true },
                                    )
                                }
                                className={`rounded-md border px-3 py-1 text-sm disabled:opacity-60 ${link.active ? 'bg-muted font-semibold' : ''}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </nav>
                )}
            </div>
        </>
    );
}

StudentsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Students',
            href: studentsIndex(),
        },
    ],
};
