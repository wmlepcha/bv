import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type { StudentOption } from '@app/types/assignments';

export function AssignmentTargetPicker({
    targetType,
    studentIds,
    students,
    onTargetTypeChange,
    onStudentIdsChange,
}: {
    targetType: 'batch' | 'student';
    studentIds: number[];
    students: StudentOption[];
    onTargetTypeChange: (type: 'batch' | 'student') => void;
    onStudentIdsChange: (ids: number[]) => void;
}) {
    return (
        <div className="grid gap-4 rounded-xl border border-border/70 bg-card p-4">
            <p className="text-sm font-medium">Assign to</p>

            <div className="flex flex-wrap gap-4">
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="radio"
                        name="target_type"
                        checked={targetType === 'batch'}
                        onChange={() => onTargetTypeChange('batch')}
                    />
                    Whole batch
                </label>
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="radio"
                        name="target_type"
                        checked={targetType === 'student'}
                        onChange={() => onTargetTypeChange('student')}
                    />
                    Selected students
                </label>
            </div>

            {targetType === 'student' && (
                <div className="grid gap-2">
                    {students.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No students enrolled in this batch.
                        </p>
                    ) : (
                        students.map((student) => {
                            const checked = studentIds.includes(student.id);

                            return (
                                <label
                                    key={student.id}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        checked={checked}
                                        onCheckedChange={(value) => {
                                            if (value) {
                                                onStudentIdsChange([
                                                    ...studentIds,
                                                    student.id,
                                                ]);
                                            } else {
                                                onStudentIdsChange(
                                                    studentIds.filter(
                                                        (id) =>
                                                            id !== student.id,
                                                    ),
                                                );
                                            }
                                        }}
                                    />
                                    <span>
                                        {student.name}{' '}
                                        <span className="text-muted-foreground">
                                            ({student.email})
                                        </span>
                                    </span>
                                </label>
                            );
                        })
                    )}
                </div>
            )}

            <input type="hidden" name="target_type" value={targetType} />
            {studentIds.map((id) => (
                <input
                    key={id}
                    type="hidden"
                    name="student_ids[]"
                    value={id}
                />
            ))}
        </div>
    );
}
