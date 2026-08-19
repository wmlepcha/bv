import type { CoreValue } from '@marketing/types/about-page';

interface LeapfrogValueCardProps {
    value: CoreValue;
}

export function LeapfrogValueCard({ value }: LeapfrogValueCardProps) {
    return (
        <div className="group relative overflow-hidden rounded-2xl border marketing-hairline bg-gradient-to-b from-primary/10 to-foreground/[0.03] p-6 transition-all hover:border-primary/30 hover:bg-foreground/[0.05] md:p-8">
            <div className="relative mb-4">
                <span
                    aria-hidden
                    className="absolute -left-2 -top-2 h-16 w-16 rounded-full bg-primary/20 blur-2xl transition-opacity group-hover:bg-primary/30"
                />
                <span className="changa-one-regular relative block text-5xl leading-none text-primary md:text-6xl">
                    {value.letter}
                </span>
            </div>
            <h3 className="text-lg font-semibold text-foreground">{value.title}</h3>
            <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                {value.description}
            </p>
        </div>
    );
}
