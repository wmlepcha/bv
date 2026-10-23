import { cn } from '@/lib/utils';
import {
    HIGHLIGHT_COLORS,
    MEMO_COLORS,
    PEN_COLORS,
    STROKE_WIDTHS,
    highlightBackground
    
} from '@app/components/assignments/grading/grading-colors';
import type {MemoColorScheme} from '@app/components/assignments/grading/grading-colors';
import { GRADING_PANEL_CLASS } from '@app/components/assignments/grading/grading-toolbar';
import type { GradingAnnotation } from '@app/components/assignments/grading/grading-types';
import type { GradingTool } from '@app/components/assignments/grading/grading-types';

type Props = {
    tool: GradingTool;
    penColor: string;
    highlightColor: string;
    memoColor: string;
    strokeWidth: number;
    selectedAnnotation: GradingAnnotation | null;
    onPenColorChange: (color: string) => void;
    onHighlightColorChange: (color: string) => void;
    onMemoColorChange: (colorId: string) => void;
    onStrokeWidthChange: (width: number) => void;
    onRecolorSelected: (color: string, memoColorId?: string) => void;
};

function ColorSwatch({
    color,
    active,
    onClick,
    label,
}: {
    color: string;
    active: boolean;
    onClick: () => void;
    label: string;
}) {
    return (
        <button
            type="button"
            title={label}
            aria-label={label}
            aria-pressed={active}
            className={cn(
                'size-7 rounded-full border-2 transition',
                active
                    ? 'border-white ring-2 ring-white/30'
                    : 'border-transparent hover:border-white/30',
            )}
            style={{ backgroundColor: color }}
            onClick={onClick}
        />
    );
}

function MemoColorSwatch({
    scheme,
    active,
    onClick,
}: {
    scheme: MemoColorScheme;
    active: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            title={scheme.id}
            aria-label={`Memo ${scheme.id}`}
            aria-pressed={active}
            className={cn(
                'size-7 rounded-md border-2 transition',
                active
                    ? 'border-white ring-2 ring-white/30'
                    : 'border-transparent hover:border-white/30',
            )}
            style={{
                backgroundColor: scheme.bg,
                boxShadow: `inset 0 0 0 1px ${scheme.border}`,
            }}
            onClick={onClick}
        />
    );
}

function FlyoutPanel({ children }: { children: React.ReactNode }) {
    return (
        <div className={cn(GRADING_PANEL_CLASS, 'gap-1.5')}>{children}</div>
    );
}

export function GradingToolOptions({
    tool,
    penColor,
    highlightColor,
    memoColor,
    strokeWidth,
    selectedAnnotation,
    onPenColorChange,
    onHighlightColorChange,
    onMemoColorChange,
    onStrokeWidthChange,
    onRecolorSelected,
}: Props) {
    if (tool === 'select' && selectedAnnotation) {
        if (selectedAnnotation.type === 'memo') {
            const activeId =
                (selectedAnnotation.payload.color as string) ?? memoColor;

            return (
                <FlyoutPanel>
                    {MEMO_COLORS.map((scheme) => (
                        <MemoColorSwatch
                            key={scheme.id}
                            scheme={scheme}
                            active={activeId === scheme.id}
                            onClick={() =>
                                onRecolorSelected(scheme.bg, scheme.id)
                            }
                        />
                    ))}
                </FlyoutPanel>
            );
        }

        const currentColor =
            (selectedAnnotation.payload.color as string) ??
            (selectedAnnotation.type === 'pen' ? penColor : highlightColor);

        const palette =
            selectedAnnotation.type === 'pen'
                ? PEN_COLORS
                : HIGHLIGHT_COLORS;

        return (
            <FlyoutPanel>
                {palette.map((color) => (
                    <ColorSwatch
                        key={color}
                        color={
                            selectedAnnotation.type === 'highlight'
                                ? highlightBackground(color)
                                : color
                        }
                        active={currentColor === color}
                        label={color}
                        onClick={() => onRecolorSelected(color)}
                    />
                ))}
            </FlyoutPanel>
        );
    }

    if (tool === 'pen') {
        return (
            <FlyoutPanel>
                {PEN_COLORS.map((color) => (
                    <ColorSwatch
                        key={color}
                        color={color}
                        active={penColor === color}
                        label={`Pen ${color}`}
                        onClick={() => onPenColorChange(color)}
                    />
                ))}
                <div className="mt-0.5 flex flex-col gap-1 border-t border-white/10 pt-1">
                    {STROKE_WIDTHS.map((width) => (
                        <button
                            key={width}
                            type="button"
                            title={`Stroke width ${width}`}
                            aria-label={`Stroke width ${width}`}
                            aria-pressed={strokeWidth === width}
                            className={cn(
                                'flex h-7 w-full items-center justify-center rounded-lg px-1 transition-colors',
                                strokeWidth === width
                                    ? 'bg-black/30'
                                    : 'hover:bg-white/5',
                            )}
                            onClick={() => onStrokeWidthChange(width)}
                        >
                            <span
                                className="block w-5 rounded-full bg-white"
                                style={{ height: width }}
                            />
                        </button>
                    ))}
                </div>
            </FlyoutPanel>
        );
    }

    if (tool === 'highlight') {
        return (
            <FlyoutPanel>
                {HIGHLIGHT_COLORS.map((color) => (
                    <ColorSwatch
                        key={color}
                        color={highlightBackground(color)}
                        active={highlightColor === color}
                        label={`Highlight ${color}`}
                        onClick={() => onHighlightColorChange(color)}
                    />
                ))}
            </FlyoutPanel>
        );
    }

    if (tool === 'memo') {
        return (
            <FlyoutPanel>
                {MEMO_COLORS.map((scheme) => (
                    <MemoColorSwatch
                        key={scheme.id}
                        scheme={scheme}
                        active={memoColor === scheme.id}
                        onClick={() => onMemoColorChange(scheme.id)}
                    />
                ))}
            </FlyoutPanel>
        );
    }

    return null;
}
