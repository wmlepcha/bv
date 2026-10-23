export function AssignmentAudioPlayer({
    assignmentId,
    label,
    portal,
}: {
    assignmentId: number;
    label?: string | null;
    portal: 'student' | 'training-manager' | 'teacher';
}) {
    const base =
        portal === 'student'
            ? '/student/assignments'
            : portal === 'teacher'
              ? '/teacher/assignments'
              : '/training-manager/assignments';

    return (
        <div className="rounded-xl border border-border/70 bg-card p-4">
            <p className="mb-2 text-sm font-medium">
                {label ?? 'Listening audio'}
            </p>
            <audio controls className="w-full" src={`${base}/${assignmentId}/audio`}>
                Your browser does not support audio playback.
            </audio>
        </div>
    );
}
