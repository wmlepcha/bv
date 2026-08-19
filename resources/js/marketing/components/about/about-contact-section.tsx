import { usePage } from '@inertiajs/react';
import { FadeUp, StaggerChildren } from '@marketing/components/animations/primitives';

export function AboutContactSection() {
    const { siteLayout } = usePage().props;
    const { contactEmail, contactPhone, offices } = siteLayout.footer;

    return (
        <section className="px-4 py-16 sm:px-6 sm:py-24 md:py-32">
            <div className="mx-auto w-full max-w-7xl 2xl:max-w-[90rem]">
                <FadeUp>
                    <h2 className="changa-one-regular text-3xl tracking-tight md:text-4xl lg:text-5xl">
                        Contact
                    </h2>
                </FadeUp>

                <StaggerChildren className="mt-12 grid gap-8 lg:grid-cols-3">
                    {offices.map((office) => (
                        <div
                            key={office.name}
                            className="rounded-2xl border marketing-panel p-8"
                        >
                            <h3 className="text-lg font-semibold">{office.name}</h3>
                            <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                                {office.address}
                            </p>
                            <div className="mt-6 space-y-2 text-sm">
                                <p>
                                    <span className="font-medium text-muted-foreground">Email: </span>
                                    <a
                                        href={`mailto:${contactEmail}`}
                                        className="transition-colors hover:text-primary"
                                    >
                                        {contactEmail}
                                    </a>
                                </p>
                                <p>
                                    <span className="font-medium text-muted-foreground">Phone: </span>
                                    <a
                                        href={`tel:${contactPhone.replace(/\s/g, '')}`}
                                        className="transition-colors hover:text-primary"
                                    >
                                        {contactPhone}
                                    </a>
                                </p>
                            </div>
                        </div>
                    ))}
                </StaggerChildren>
            </div>
        </section>
    );
}
