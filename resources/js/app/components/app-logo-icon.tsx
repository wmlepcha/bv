import type { ImgHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export const logoUrl = '/storage/images/bv-logo.png';

export default function AppLogoIcon({
    className,
    alt = 'Bilingual Vitae',
    src = logoUrl,
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src={src}
            alt={alt}
            className={cn('object-contain', className)}
            {...props}
        />
    );
}
