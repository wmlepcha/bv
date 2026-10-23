import { Redo2, Undo2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { GradingTool } from '@app/components/assignments/grading/grading-types';
import { GradingToolIcon } from '@app/components/assignments/grading/icons/grading-tool-icon';

const TOOLS: Array<{ id: GradingTool; label: string }> = [
    { id: 'select', label: 'Select' },
    { id: 'pen', label: 'Pen' },
    { id: 'highlight', label: 'Highlight' },
    { id: 'memo', label: 'Memo' },
];

const PANEL_CLASS =
    'flex flex-col gap-1 overflow-visible rounded-2xl border border-white/10 bg-[#243447] p-1.5 shadow-lg';

type Props = {
    tool: GradingTool;
    onToolChange: (tool: GradingTool) => void;
    canUndo: boolean;
    canRedo: boolean;
    onUndo: () => void;
    onRedo: () => void;
};

export function GradingToolbar({
    tool,
    onToolChange,
    canUndo,
    canRedo,
    onUndo,
    onRedo,
}: Props) {
    return (
        <div className={PANEL_CLASS}>
            {TOOLS.map(({ id, label }) => {
                const active = tool === id;

                return (
                    <button
                        key={id}
                        type="button"
                        title={label}
                        aria-label={label}
                        aria-pressed={active}
                        className={cn(
                            'flex size-9 items-center justify-center rounded-xl transition-all duration-200',
                            active
                                ? 'relative z-10 translate-x-2 scale-110'
                                : 'hover:bg-white/10',
                        )}
                        onClick={() => onToolChange(id)}
                    >
                        <GradingToolIcon tool={id} />
                    </button>
                );
            })}
            <div className="my-0.5 border-t border-white/10" />
            <button
                type="button"
                title="Undo"
                aria-label="Undo"
                disabled={!canUndo}
                className="flex size-9 items-center justify-center rounded-xl text-white/70 transition-colors hover:bg-white/10 disabled:opacity-30"
                onClick={onUndo}
            >
                <Undo2 className="size-4" />
            </button>
            <button
                type="button"
                title="Redo"
                aria-label="Redo"
                disabled={!canRedo}
                className="flex size-9 items-center justify-center rounded-xl text-white/70 transition-colors hover:bg-white/10 disabled:opacity-30"
                onClick={onRedo}
            >
                <Redo2 className="size-4" />
            </button>
        </div>
    );
}

export function shouldShowGradingFlyout(
    tool: GradingTool,
    hasSelectedAnnotation: boolean,
): boolean {
    if (tool === 'pen' || tool === 'highlight' || tool === 'memo') {
        return true;
    }

    return tool === 'select' && hasSelectedAnnotation;
}

export { PANEL_CLASS as GRADING_PANEL_CLASS };
