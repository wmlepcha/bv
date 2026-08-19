import type { ImgHTMLAttributes } from 'react';
import { BRAND_NAME_TM } from '@shared/lib/brand';
import { cn } from '@/lib/utils';

export const logoUrl = '/storage/images/bv-logo.png';

export default function AppLogoIcon({
    className,
    alt = BRAND_NAME_TM,
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
