import { MousePointer2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { GradingTool } from '@app/components/assignments/grading/grading-types';

/** Custom toolbar icons: storage/app/public/*.svg → /storage/*.svg (48×48) */
const GRADING_TOOL_ASSETS: Partial<
    Record<GradingTool, { src: string; className: string }>
> = {
    pen: { src: '/storage/pen.svg', className: 'size-9' },
    highlight: { src: '/storage/highlighter.svg', className: 'size-9' },
    memo: { src: '/storage/memo.svg', className: 'size-6' },
};

type Props = {
    tool: GradingTool;
    className?: string;
};

export function GradingToolIcon({ tool, className }: Props) {
    const asset = GRADING_TOOL_ASSETS[tool];

    if (asset) {
        return (
            <img
                src={asset.src}
                alt=""
                draggable={false}
                className={cn('object-contain', asset.className, className)}
            />
        );
    }

    return <MousePointer2 className={cn('size-5 text-white', className)} />;
}
