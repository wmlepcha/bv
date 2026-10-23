import { cn } from '@/lib/utils';
import AppLogoIcon from '@app/components/app-logo-icon';
import { cvLabels  } from '@app/lib/cv-labels';
import type {CvLocale} from '@app/lib/cv-labels';

type Props = {
    locale?: CvLocale;
    className?: string;
};

export default function CvCreatedAtFooter({
    locale = 'en',
    className,
}: Props) {
    const labels = cvLabels[locale];

    return (
        <footer
            className={cn(
                'mt-auto flex shrink-0 items-center justify-center gap-2 border-t border-neutral-200 px-4 py-3 text-sm text-neutral-500 print:py-2',
                className,
            )}
        >
            <span>{labels.createdAt}</span>
            <AppLogoIcon
                className="h-10 w-auto print:h-9"
                alt={labels.brandAlt}
                src="/storage/images/bv-logo-light.png"
            />
        </footer>
    );
}
