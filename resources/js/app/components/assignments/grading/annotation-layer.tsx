import { cn } from '@/lib/utils';
import {
    DEFAULT_PEN_COLOR,
    memoColorById,
} from '@app/components/assignments/grading/grading-colors';
import type { GradingAnnotation, GradingTool } from '@app/components/assignments/grading/grading-types';
import { MemoNote } from '@app/components/assignments/grading/memo-note';
import { penPathsFromPayload } from '@app/components/assignments/grading/pen-stroke';

type Props = {
    annotations: GradingAnnotation[];
    tool: GradingTool;
    selectedClientId: string | null;
    autoFocusClientId?: string | null;
    readOnly?: boolean;
    onSelect: (clientId: string | null) => void;
    onUpdate: (clientId: string, payload: Record<string, unknown>) => void;
    onAutoFocusComplete?: () => void;
};

export function AnnotationLayer({
    annotations,
    tool,
    selectedClientId,
    autoFocusClientId = null,
    readOnly = false,
    onSelect,
    onUpdate,
    onAutoFocusComplete,
}: Props) {
    const penInteractive = !readOnly && tool === 'select';

    return (
        <div className="pointer-events-none absolute inset-0 z-10">
            {annotations.map((annotation) => {
                if (annotation.type === 'pen') {
                    const paths = penPathsFromPayload(annotation.payload);
                    const color =
                        (annotation.payload.color as string) ?? DEFAULT_PEN_COLOR;
                    const selected = selectedClientId === annotation.clientId;

                    return (
                        <svg
                            key={annotation.clientId}
                            className={cn(
                                'pointer-events-none absolute inset-0 h-full w-full',
                                penInteractive && 'pointer-events-auto cursor-pointer',
                            )}
                            onClick={(event) => {
                                if (!penInteractive) {
                                    return;
                                }

                                event.stopPropagation();
                                onSelect(annotation.clientId);
                            }}
                        >
                            {paths.map((path, pathIndex) => (
                                <path
                                    key={pathIndex}
                                    d={path}
                                    fill={color}
                                    className={cn(
                                        selected &&
                                            'outline outline-2 outline-primary outline-offset-2',
                                    )}
                                />
                            ))}
                        </svg>
                    );
                }

                if (annotation.type === 'memo') {
                    const memoEditable =
                        !readOnly && (tool === 'select' || tool === 'memo');

                    return (
                        <MemoNote
                            key={annotation.clientId}
                            annotation={annotation}
                            selected={selectedClientId === annotation.clientId}
                            readOnly={!memoEditable}
                            autoFocus={
                                autoFocusClientId === annotation.clientId
                            }
                            onSelect={() => onSelect(annotation.clientId)}
                            onUpdate={(payload) =>
                                onUpdate(annotation.clientId, payload)
                            }
                            onAutoFocusComplete={onAutoFocusComplete}
                        />
                    );
                }

                return null;
            })}
        </div>
    );
}

export function PenPreviewPath({
    path,
    color,
}: {
    path: string;
    color: string;
}) {
    if (!path) {
        return null;
    }

    return (
        <svg className="pointer-events-none absolute inset-0 z-20 h-full w-full">
            <path d={path} fill={color} />
        </svg>
    );
}

export { memoColorById };
