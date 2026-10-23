import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useSidebar } from '@/components/ui/sidebar';
import { cn, toUrl } from '@/lib/utils';
import { search as searchRoute } from '@/routes';
import type { NavItem } from '@/types/navigation';

type ContentResult = {
    group: string;
    label: string;
    sublabel: string;
    href: string;
};

type GroupedResults = Array<{ group: string; results: ContentResult[] }>;

function groupResults(results: ContentResult[]): GroupedResults {
    const order: string[] = [];
    const byGroup = new Map<string, ContentResult[]>();

    for (const result of results) {
        const existing = byGroup.get(result.group);

        if (existing) {
            existing.push(result);
        } else {
            byGroup.set(result.group, [result]);
            order.push(result.group);
        }
    }

    return order.map((group) => ({ group, results: byGroup.get(group)! }));
}

export function SidebarSearch({ navItems }: { navItems: NavItem[] }) {
    const { isMobile, setOpenMobile } = useSidebar();
    const [query, setQuery] = useState('');
    const [open, setOpen] = useState(false);
    const [contentResults, setContentResults] = useState<ContentResult[]>([]);
    const [loading, setLoading] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    const trimmedQuery = query.trim();
    const navResults = trimmedQuery
        ? navItems.filter((item) =>
              item.title.toLowerCase().includes(trimmedQuery.toLowerCase()),
          )
        : [];
    const groupedContent = groupResults(contentResults);
    const hasResults = navResults.length > 0 || contentResults.length > 0;

    useEffect(() => {
        if (trimmedQuery.length < 2) {
            return;
        }

        let cancelled = false;

        const timeout = window.setTimeout(() => {
            fetch(searchRoute.url({ query: { q: trimmedQuery } }), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            })
                .then((response) => (response.ok ? response.json() : null))
                .then((payload: { results?: ContentResult[] } | null) => {
                    if (!cancelled) {
                        setContentResults(payload?.results ?? []);
                    }
                })
                .catch(() => {
                    if (!cancelled) {
                        setContentResults([]);
                    }
                })
                .finally(() => {
                    if (!cancelled) {
                        setLoading(false);
                    }
                });
        }, 250);

        return () => {
            cancelled = true;
            window.clearTimeout(timeout);
        };
    }, [trimmedQuery]);

    useEffect(() => {
        function handlePointerDown(event: PointerEvent) {
            if (
                containerRef.current &&
                !containerRef.current.contains(event.target as Node)
            ) {
                setOpen(false);
            }
        }

        document.addEventListener('pointerdown', handlePointerDown);

        return () =>
            document.removeEventListener('pointerdown', handlePointerDown);
    }, []);

    const navigate = (href: string) => {
        router.visit(href);
        setOpen(false);
        setQuery('');

        if (isMobile) {
            setOpenMobile(false);
        }
    };

    const showDropdown = open && trimmedQuery.length > 0;

    return (
        <div ref={containerRef} className="relative">
            <div className="relative">
                <Search className="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-nav-rail-muted" />
                <input
                    type="search"
                    value={query}
                    onChange={(event) => {
                        const value = event.target.value;
                        setQuery(value);
                        setOpen(true);

                        if (value.trim().length < 2) {
                            setContentResults([]);
                            setLoading(false);
                        } else {
                            setLoading(true);
                        }
                    }}
                    onFocus={() => setOpen(true)}
                    placeholder="Search"
                    aria-label="Search"
                    className="h-10 w-full rounded-full border-0 bg-muted/70 pr-3 pl-10 text-sm text-nav-rail-foreground placeholder:text-nav-rail-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                />
            </div>

            {showDropdown && (
                <div className="absolute inset-x-0 top-full z-50 mt-2 max-h-[70vh] overflow-y-auto rounded-2xl border border-border/60 bg-popover p-2 shadow-lg">
                    {navResults.length > 0 && (
                        <div className="mb-1">
                            <p className="px-2 py-1 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                                Pages
                            </p>
                            {navResults.map((item) => (
                                <button
                                    key={item.title}
                                    type="button"
                                    onClick={() => navigate(toUrl(item.href))}
                                    className="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-sm hover:bg-muted"
                                >
                                    {item.icon && (
                                        <item.icon className="size-4 shrink-0 text-muted-foreground" />
                                    )}
                                    <span className="truncate">
                                        {item.title}
                                    </span>
                                </button>
                            ))}
                        </div>
                    )}

                    {groupedContent.map(({ group, results }) => (
                        <div key={group} className="mb-1">
                            <p className="px-2 py-1 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                                {group}
                            </p>
                            {results.map((result, index) => (
                                <button
                                    key={`${group}-${index}`}
                                    type="button"
                                    onClick={() => navigate(result.href)}
                                    className="flex w-full flex-col items-start rounded-lg px-2 py-2 text-left hover:bg-muted"
                                >
                                    <span className="truncate text-sm">
                                        {result.label}
                                    </span>
                                    <span className="truncate text-xs text-muted-foreground">
                                        {result.sublabel}
                                    </span>
                                </button>
                            ))}
                        </div>
                    ))}

                    {loading && (
                        <p className="px-2 py-2 text-xs text-muted-foreground">
                            Searching…
                        </p>
                    )}

                    {!loading && !hasResults && (
                        <p
                            className={cn(
                                'px-2 py-4 text-center text-sm text-muted-foreground',
                            )}
                        >
                            No results for “{trimmedQuery}”.
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
