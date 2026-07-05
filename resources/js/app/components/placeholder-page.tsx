import { PrivateSeoHead } from '@shared/components/seo-head';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';

type Props = {
    title: string;
    description: string;
};

export default function PlaceholderPage({ title, description }: Props) {
    return (
        <>
            <PrivateSeoHead title={title} />
            <div className="flex h-full min-h-[50vh] flex-col gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {description}
                    </p>
                </div>
                <div className="relative min-h-[320px] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    <div className="absolute inset-0 flex items-center justify-center">
                        <p className="rounded-full border border-border bg-background/80 px-4 py-2 text-sm text-muted-foreground">
                            Coming soon
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
