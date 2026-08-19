import { StaggerChildren } from '@marketing/components/animations/primitives';

interface AboutMissionVisionSectionProps {
    mission: string;
    vision: string;
}

export function AboutMissionVisionSection({ mission, vision }: AboutMissionVisionSectionProps) {
    return (
        <section className="px-4 py-16 sm:px-6 sm:py-24 md:py-32">
            <div className="mx-auto w-full max-w-7xl 2xl:max-w-[90rem]">
                <StaggerChildren className="grid gap-8 md:grid-cols-2">
                    <div className="rounded-2xl border marketing-panel p-8 md:p-10">
                        <p className="text-sm font-medium uppercase tracking-widest text-primary">
                            Mission
                        </p>
                        <p className="mt-4 text-base leading-relaxed text-muted-foreground md:text-lg">
                            {mission}
                        </p>
                    </div>

                    <div className="rounded-2xl border marketing-panel p-8 md:p-10">
                        <p className="text-sm font-medium uppercase tracking-widest text-brand-cool-light">
                            Vision
                        </p>
                        <p className="mt-4 text-base leading-relaxed text-muted-foreground md:text-lg">
                            {vision}
                        </p>
                    </div>
                </StaggerChildren>
            </div>
        </section>
    );
}
