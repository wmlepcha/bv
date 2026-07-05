import { Link } from '@inertiajs/react';
import { useRef } from 'react';
import { ScaleIn } from '@marketing/components/animations/primitives';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';

interface ServiceCardProps {
    title: string;
    href: string;
    image: string;
    index?: number;
}

/** Premium glass service card with hover interactions. */
export function ServiceCard({ title, href, image, index = 0 }: ServiceCardProps) {
    const cardRef = useRef<HTMLAnchorElement>(null);
    const isMobile = useIsMobile();

    const handleMove = (e: React.MouseEvent<HTMLAnchorElement>) => {
        if (isMobile) {
            return;
        }
        const card = cardRef.current;
        if (!card) {
            return;
        }

        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;

        card.style.transform = `translate(${x * 0.05}px, ${y * 0.05 - 4}px)`;
    };

    const handleLeave = () => {
        const card = cardRef.current;
        if (card) {
            card.style.transform = 'translate(0, 0)';
        }
    };

    return (
        <ScaleIn delay={index * 0.15}>
            <Link
                ref={cardRef}
                href={href}
                className={cn(
                    'group relative block overflow-hidden rounded-3xl border border-white/10 bg-white/5 shadow-xl backdrop-blur-sm transition-[transform,box-shadow] duration-500 hover:shadow-2xl hover:shadow-primary/10',
                )}
                onMouseMove={isMobile ? undefined : handleMove}
                onMouseLeave={isMobile ? undefined : handleLeave}
            >
                <div className="relative aspect-[3/4] overflow-hidden">
                    <img
                        src={image}
                        alt={title}
                        className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                        loading="lazy"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />
                </div>
                <div className="absolute bottom-0 left-0 right-0 p-6">
                    <h3 className="text-lg font-semibold text-white md:text-xl">{title}</h3>
                </div>
            </Link>
        </ScaleIn>
    );
}
