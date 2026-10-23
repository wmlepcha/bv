import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { AssignmentAudioPlayer } from '@app/components/assignments/assignment-audio-player';
import { GradableAnswerCard } from '@app/components/assignments/gradable-answer-card';
import {
    GradingToolbar,
    GradingToolOptions,
    shouldShowGradingFlyout,
    useGradingAnnotations,
} from '@app/components/assignments/grading-workspace';
import type {
    AssignmentData,
    AssignmentSubmissionData,
} from '@app/types/assignments';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = {
    assignment: AssignmentData;
    submission: AssignmentSubmissionData;
    portal: 'teacher' | 'training-manager';
};

export default function GradeSubmissionPage({
    assignment,
    submission,
    portal,
}: Props) {
    const grading = useGradingAnnotations(submission.annotations ?? []);

    const [finalScore, setFinalScore] = useState(
        submission.final_score ?? submission.auto_score ?? 0,
    );
    const [scoreOverridden, setScoreOverridden] = useState(
        submission.score_overridden ?? false,
    );
    const [feedback, setFeedback] = useState(submission.feedback ?? '');

    const autoScore = useMemo(
        () => submission.auto_score ?? 0,
        [submission.auto_score],
    );

    const selectedAnnotation = useMemo(
        () =>
            grading.annotations.find(
                (annotation) => annotation.clientId === grading.selectedClientId,
            ) ?? null,
        [grading.annotations, grading.selectedClientId],
    );

    const showFlyout = shouldShowGradingFlyout(
        grading.tool,
        selectedAnnotation !== null,
    );

    const save = () => {
        router.put(
            `/${portal}/assignments/${assignment.id}/submissions/${submission.id}/grade`,
            {
                final_score: finalScore,
                feedback,
                score_overridden: scoreOverridden,
                annotations: grading.stripForSave(),
            },
        );
    };

    return (
        <>
            <PrivateSeoHead title="Grade submission" />
            <div className="mx-auto flex max-w-5xl gap-4 p-2 sm:px-4">
                <aside className="sticky top-20 z-20 flex shrink-0 items-start gap-0 self-start overflow-visible">
                    <GradingToolbar
                        tool={grading.tool}
                        onToolChange={grading.setTool}
                        canUndo={grading.canUndo}
                        canRedo={grading.canRedo}
                        onUndo={grading.undo}
                        onRedo={grading.redo}
                    />
                    {showFlyout && (
                        <GradingToolOptions
                            tool={grading.tool}
                            penColor={grading.penColor}
                            highlightColor={grading.highlightColor}
                            memoColor={grading.memoColor}
                            strokeWidth={grading.strokeWidth}
                            selectedAnnotation={selectedAnnotation}
                            onPenColorChange={grading.setPenColor}
                            onHighlightColorChange={grading.setHighlightColor}
                            onMemoColorChange={grading.setMemoColor}
                            onStrokeWidthChange={grading.setStrokeWidth}
                            onRecolorSelected={grading.recolorSelected}
                        />
                    )}
                </aside>
                <main className="min-w-0 flex-1 space-y-6">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {submission.student.name}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {assignment.title}
                        </p>
                    </div>

                    {assignment.has_audio && (
                        <AssignmentAudioPlayer
                            assignmentId={assignment.id}
                            portal={portal}
                        />
                    )}

                    {submission.answers.map((entry) => (
                        <GradableAnswerCard
                            key={entry.question_id}
                            questionId={entry.question_id}
                            question={entry.question}
                            answer={entry.answer}
                            isCorrect={entry.is_correct}
                            tool={grading.tool}
                            penColor={grading.penColor}
                            highlightColor={grading.highlightColor}
                            memoColor={grading.memoColor}
                            strokeWidth={grading.strokeWidth}
                            annotations={grading.annotations}
                            selectedClientId={grading.selectedClientId}
                            onSelect={grading.setSelectedClientId}
                            onMutate={grading.mutate}
                            onMemoPlaced={grading.focusMemo}
                            autoFocusClientId={grading.autoFocusClientId}
                            onAutoFocusComplete={grading.clearMemoFocus}
                            assignmentId={assignment.id}
                            portal={portal}
                        />
                    ))}

                    <div className="rounded-xl border border-border/70 p-4">
                        <p className="text-sm">
                            Auto-calculated: {autoScore} / {assignment.max_score}
                        </p>
                        <div className="mt-3 grid gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="final_score">Final score</Label>
                                <Input
                                    id="final_score"
                                    type="number"
                                    min={0}
                                    max={assignment.max_score}
                                    value={finalScore}
                                    onChange={(e) =>
                                        setFinalScore(Number(e.target.value))
                                    }
                                />
                            </div>
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={scoreOverridden}
                                    onCheckedChange={(v) =>
                                        setScoreOverridden(v === true)
                                    }
                                />
                                Override auto-calculated score
                            </label>
                            <div className="grid gap-2">
                                <Label htmlFor="feedback">Overall feedback</Label>
                                <Textarea
                                    id="feedback"
                                    value={feedback}
                                    onChange={(e) => setFeedback(e.target.value)}
                                    rows={4}
                                />
                            </div>
                            <Button onClick={save}>Save grade</Button>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
