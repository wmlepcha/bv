import { Link } from '@inertiajs/react';
import { PrivateSeoHead } from '@shared/components/seo-head';
import { BRAND_NAME_TM } from '@shared/lib/brand';
import { index as studentsIndex, show as studentShow } from '@/routes/recruiter/students';

type StudentListItem = {
    id: number;
    name: string;
    email: string;
    student_number: string | null;
    headline: string | null;
    photo_url: string | null;
};

type Props = {
    profiles: StudentListItem[];
};

const HEADER_TONES = [
    'bg-[#FFD6C8] text-[#031835]',
    'bg-[#C8E4FF] text-[#031835]',
    'bg-[#FFE8B8] text-[#031835]',
    'bg-[#D4F0F5] text-[#031835]',
    'bg-[#F5D0C8] text-[#031835]',
    'bg-[#D8E8F8] text-[#031835]',
] as const;

function initialsFromName(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);

    if (parts.length === 0) {
        return '?';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return `${parts[0][0] ?? ''}${parts[parts.length - 1][0] ?? ''}`.toUpperCase();
}

export default function StudentsIndex({ profiles }: Props) {
    return (
        <>
            <PrivateSeoHead title="Students" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Students
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Browse candidate profiles, bilingual proficiency, and{' '}
                        {BRAND_NAME_TM} data.
                    </p>
                </div>

                {profiles.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-border p-10 text-center">
                        <p className="text-sm text-muted-foreground">
                            No student profiles are available yet.
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        {profiles.map((profile, index) => (
                            <Link
                                key={profile.id}
                                href={studentShow(profile.id)}
                                className="group flex flex-col overflow-hidden rounded-2xl bg-card shadow-sm transition-shadow hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <div
                                    className={`px-3 py-2.5 ${HEADER_TONES[index % HEADER_TONES.length]}`}
                                >
                                    <p className="truncate text-sm font-bold uppercase leading-snug tracking-tight">
                                        {profile.name}
                                    </p>
                                    {profile.student_number && (
                                        <p className="mt-0.5 truncate text-[11px] font-medium uppercase tracking-wide opacity-70">
                                            ID {profile.student_number}
                                        </p>
                                    )}
                                </div>

                                <div className="relative aspect-[3/4] bg-muted">
                                    {profile.photo_url ? (
                                        <img
                                            src={profile.photo_url}
                                            alt=""
                                            className="absolute inset-0 size-full object-cover object-top"
                                        />
                                    ) : (
                                        <div className="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-muted to-muted-foreground/15">
                                            <span className="text-2xl font-semibold tracking-tight text-muted-foreground/70">
                                                {initialsFromName(profile.name)}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </Link>
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
