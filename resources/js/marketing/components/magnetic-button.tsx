import { useRef   } from 'react';
import type {ReactNode, ButtonHTMLAttributes} from 'react';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';

interface MagneticButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    children: ReactNode;
    strength?: number;
}

/** Button with subtle magnetic hover pull effect. */
export function MagneticButton({
    children,
    className,
    strength = 0.3,
    ...props
}: MagneticButtonProps) {
    const buttonRef = useRef<HTMLButtonElement>(null);
    const isMobile = useIsMobile();

    const handleMove = (e: React.MouseEvent<HTMLButtonElement>) => {
        if (isMobile) {
            return;
        }

        const btn = buttonRef.current;

        if (!btn) {
            return;
        }

        const rect = btn.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;

        btn.style.transform = `translate(${x * strength}px, ${y * strength}px)`;
    };

    const handleLeave = () => {
        const btn = buttonRef.current;

        if (btn) {
            btn.style.transform = 'translate(0, 0)';
        }
    };

    return (
        <button
            ref={buttonRef}
            className={cn(
                'inline-flex items-center justify-center rounded-full bg-primary px-8 py-3 text-sm font-medium text-primary-foreground shadow-lg shadow-primary/25 transition-[transform,box-shadow,background-color] duration-300 hover:bg-brand-warm-dark hover:shadow-xl hover:shadow-primary/30',
                className,
            )}
            onMouseMove={isMobile ? undefined : handleMove}
            onMouseLeave={isMobile ? undefined : handleLeave}
            {...props}
        >
            {children}
        </button>
    );
}
