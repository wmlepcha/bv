import { Link } from '@inertiajs/react';
import { PrivateSeoHead } from '@shared/components/seo-head';
import { Button } from '@/components/ui/button';
import { index as studentsIndex, show as studentShow } from '@/routes/recruiter/students';

type StudentListItem = {
    id: number;
    name: string;
    email: string;
    student_number: string | null;
    headline: string | null;
};

type Props = {
    profiles: StudentListItem[];
};

export default function StudentsIndex({ profiles }: Props) {
    return (
        <>
            <PrivateSeoHead title="Students" />
            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Students
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Browse candidate profiles, bilingual proficiency, and
                        Bilingual Vitae data.
                    </p>
                </div>

                {profiles.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-border p-10 text-center">
                        <p className="text-sm text-muted-foreground">
                            No student profiles are available yet.
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-4">
                        {profiles.map((profile) => (
                            <div
                                key={profile.id}
                                className="flex flex-col gap-3 rounded-xl border border-border p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <p className="font-medium">{profile.name}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {profile.email}
                                    </p>
                                    {profile.student_number && (
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Student ID: {profile.student_number}
                                        </p>
                                    )}
                                </div>
                                <Button asChild variant="outline">
                                    <Link href={studentShow(profile.id)}>
                                        View profile
                                    </Link>
                                </Button>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

StudentsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Students',
            href: studentsIndex(),
        },
    ],
};
