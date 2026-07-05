import { PrivateSeoHead } from '@shared/components/seo-head';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { index as studentsIndex } from '@/routes/recruiter/students';

type Profile = {
    id: number;
    name: string;
    email: string;
    student_number: string | null;
    headline: string | null;
    languages: string[];
    proficiency_summary: string | null;
    vitae_data: Record<string, unknown>;
};

type Props = {
    profile: Profile;
};

export default function StudentShow({ profile }: Props) {
    return (
        <>
            <PrivateSeoHead title={profile.name} />
            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {profile.name}
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {profile.email}
                        {profile.student_number
                            ? ` · ${profile.student_number}`
                            : ''}
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-xl border border-border p-4">
                        <h2 className="font-medium">Bilingual proficiency</h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            {profile.proficiency_summary ??
                                'Proficiency details will appear here once the student profile is completed.'}
                        </p>
                    </div>
                    <div className="rounded-xl border border-border p-4">
                        <h2 className="font-medium">Languages</h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            {profile.languages.length > 0
                                ? profile.languages.join(', ')
                                : 'No languages listed yet.'}
                        </p>
                    </div>
                </div>

                <div className="relative min-h-[320px] overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    <div className="absolute inset-0 flex items-center justify-center">
                        <p className="rounded-full border border-border bg-background/80 px-4 py-2 text-sm text-muted-foreground">
                            Bilingual Vitae profile coming soon
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}

StudentShow.layout = {
    breadcrumbs: [
        {
            title: 'Students',
            href: studentsIndex(),
        },
    ],
};
