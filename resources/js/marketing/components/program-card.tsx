import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { ScaleIn } from '@marketing/components/animations/primitives';

interface ProgramCardProps {
    title: string;
    description: string;
    href: string;
    image: string;
    index?: number;
}

export function ProgramCard({ title, description, href, image, index = 0 }: ProgramCardProps) {
    const imageOnTop = index % 2 === 0;
    const isFeatured = index === 1;

    const imageBlock = (
        <div className="relative aspect-[4/3] overflow-hidden">
            <img
                src={image}
                alt={title}
                className="h-full w-full object-cover grayscale transition-transform duration-700 group-hover:scale-105"
                loading="lazy"
            />
        </div>
    );

    const textBlock = (
        <div className="flex flex-1 flex-col justify-center gap-3 p-6 md:p-8">
            <h3
                className={cn(
                    'text-xl font-semibold tracking-tight md:text-2xl',
                    isFeatured ? 'text-white' : 'text-foreground',
                )}
            >
                {title}
            </h3>
            <p
                className={cn(
                    'text-sm leading-relaxed md:text-base',
                    isFeatured ? 'text-white/80' : 'text-muted-foreground',
                )}
            >
                {description}
            </p>
            <span
                className={cn(
                    'mt-2 inline-flex items-center text-sm font-semibold transition-opacity group-hover:opacity-90',
                    isFeatured ? 'text-brand-cool-light' : 'text-primary',
                )}
            >
                Learn more →
            </span>
        </div>
    );

    return (
        <ScaleIn delay={index * 0.12}>
            <Link
                href={href}
                className={cn(
                    'group flex h-full flex-col overflow-hidden rounded-[1.75rem] border transition-[transform,box-shadow,background-color] duration-500',
                    'hover:-translate-y-1 hover:shadow-2xl',
                    isFeatured
                        ? 'border-brand-cool-dark/40 bg-brand-cool-dark shadow-lg shadow-brand-cool-dark/25 hover:shadow-brand-cool-dark/35'
                        : 'marketing-panel marketing-hairline hover:shadow-primary/10',
                )}
            >
                {imageOnTop ? (
                    <>
                        {imageBlock}
                        {textBlock}
                    </>
                ) : (
                    <>
                        {textBlock}
                        {imageBlock}
                    </>
                )}
            </Link>
        </ScaleIn>
    );
}
