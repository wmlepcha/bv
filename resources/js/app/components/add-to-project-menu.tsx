import { Link, router } from '@inertiajs/react';
import { Check, ChevronDown, FolderPlus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';
import { index } from '@/routes/recruiter/projects';
import { destroy, store } from '@/routes/recruiter/projects/candidates';
import type { ShortlistProject } from '@app/types/recruiter';

export default function AddToProjectMenu({
    profileId,
    projects,
}: {
    profileId: number;
    projects: ShortlistProject[];
}) {
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);
    const savedCount = projects.filter((project) => project.added).length;

    useEffect(() => {
        if (!open) {
            return;
        }

        const onPointerDown = (event: MouseEvent) => {
            if (!ref.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onPointerDown);

        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [open]);

    const toggle = (project: ShortlistProject) => {
        const route = {
            project: project.id,
            studentProfile: profileId,
        };

        if (project.added) {
            router.delete(destroy.url(route), { preserveScroll: true });
        } else {
            router.post(store.url(route), {}, { preserveScroll: true });
        }
    };

    return (
        <div ref={ref} className="relative self-start xl:self-auto">
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="relative inline-flex items-center gap-2 rounded-xl border border-border bg-background px-4 py-2.5 text-sm font-medium transition-colors hover:bg-muted active:scale-95 max-sm:size-10 max-sm:justify-center max-sm:rounded-full max-sm:p-0"
            >
                <FolderPlus className="size-4" />
                <span className="max-sm:sr-only">
                    {savedCount > 0
                        ? `In ${savedCount} project${savedCount > 1 ? 's' : ''}`
                        : 'Add to project'}
                </span>
                <ChevronDown className="size-4 opacity-70 max-sm:hidden" />
                {savedCount > 0 && (
                    <span className="absolute -top-1 -right-1 flex size-4 items-center justify-center rounded-full bg-emerald-600 text-[10px] font-bold text-white sm:hidden">
                        {savedCount}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute end-0 z-30 mt-2 w-64 overflow-hidden rounded-xl border border-border bg-background shadow-lg">
                    {projects.length === 0 ? (
                        <p className="px-4 py-3 text-sm text-muted-foreground">
                            You have no projects yet.
                        </p>
                    ) : (
                        <ul className="max-h-64 overflow-y-auto py-1">
                            {projects.map((project) => (
                                <li key={project.id}>
                                    <button
                                        type="button"
                                        onClick={() => toggle(project)}
                                        className="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm hover:bg-muted"
                                    >
                                        <Check
                                            className={cn(
                                                'size-4 shrink-0',
                                                project.added
                                                    ? 'text-emerald-600'
                                                    : 'opacity-0',
                                            )}
                                        />
                                        <span className="truncate">
                                            {project.name}
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                    <Link
                        href={index()}
                        className="block border-t border-border px-4 py-2.5 text-sm text-muted-foreground hover:bg-muted"
                    >
                        Manage projects…
                    </Link>
                </div>
            )}
        </div>
    );
}
