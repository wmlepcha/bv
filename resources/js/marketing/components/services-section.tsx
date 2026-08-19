import { FadeUp } from '@marketing/components/animations/primitives';
import { StaggerChildren } from '@marketing/components/animations/primitives';
import { ServiceCard } from '@marketing/components/service-card';
import type { HomePageProps } from '@marketing/types/home-page';

interface ServicesSectionProps {
    services: HomePageProps['services'];
}

export function ServicesSection({ services }: ServicesSectionProps) {
    return (
        <section className="px-4 py-16 sm:px-6 sm:py-24 md:py-32">
            <div className="mx-auto max-w-7xl">
                <FadeUp className="text-center">
                    <h2 className="changa-one-regular mx-auto max-w-4xl text-3xl tracking-tight md:text-5xl">
                        {services.heading}
                    </h2>
                </FadeUp>
                <FadeUp className="mt-4 text-center text-lg text-secondary" delay={0.2}>
                    {services.subtitle}
                </FadeUp>

                <StaggerChildren className="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {services.cards.map((service, index) => (
                        <ServiceCard
                            key={service.href}
                            title={service.title}
                            href={service.href}
                            image={service.imageUrl}
                            index={index}
                        />
                    ))}
                </StaggerChildren>
            </div>
        </section>
    );
}
