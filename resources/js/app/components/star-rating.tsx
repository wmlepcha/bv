import { Star } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    label: string;
    value: number;
    onChange: (value: number) => void;
    max?: number;
};

export function StarRating({ label, value, onChange, max = 5 }: Props) {
    const [hovered, setHovered] = useState(0);
    const shown = hovered || value;

    return (
        <div
            role="radiogroup"
            aria-label={label}
            className="flex items-center justify-between gap-1 sm:justify-start"
            onMouseLeave={() => setHovered(0)}
        >
            {Array.from({ length: max }, (_, index) => index + 1).map(
                (star) => (
                    <button
                        key={star}
                        type="button"
                        role="radio"
                        aria-checked={value === star}
                        aria-label={`${star} of ${max} stars`}
                        onClick={() => onChange(star)}
                        onMouseEnter={() => setHovered(star)}
                        className="flex-1 rounded-sm p-1 transition-transform focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none active:scale-90 sm:flex-none sm:p-0.5 sm:hover:scale-110"
                    >
                        <Star
                            className={cn(
                                'mx-auto size-10 sm:size-9',
                                star <= shown
                                    ? 'fill-amber-400 text-amber-400'
                                    : 'text-muted-foreground/40',
                            )}
                        />
                    </button>
                ),
            )}
            {value > 0 && (
                <span className="ml-2 text-sm text-muted-foreground">
                    {value} / {max}
                </span>
            )}
        </div>
    );
}
