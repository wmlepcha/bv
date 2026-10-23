import { usePage } from '@inertiajs/react';
import { AboutContactSection } from '@marketing/components/about/about-contact-section';
import { AboutCoreValuesSection } from '@marketing/components/about/about-core-values-section';
import { AboutHeroSection } from '@marketing/components/about/about-hero-section';
import { AboutImpactSection } from '@marketing/components/about/about-impact-section';
import { AboutLeapfrogSection } from '@marketing/components/about/about-leapfrog-section';
import { AboutMissionVisionSection } from '@marketing/components/about/about-mission-vision-section';
import { AboutOverviewSection } from '@marketing/components/about/about-overview-section';
import { JourneyTimeline } from '@marketing/components/about/journey-timeline';
import { LearnMoreInquiryModal } from '@marketing/components/learn-more-inquiry-modal';
import { useInquiryModal } from '@marketing/hooks/use-inquiry-modal';
import type { AboutPageProps } from '@marketing/types/about-page';
import { SeoHead } from '@shared/components/seo-head';

export default function About() {
    const { open, initialRole, openModal, onOpenChange } = useInquiryModal();
    const { aboutPage } = usePage<{ aboutPage: AboutPageProps }>().props;

    return (
        <>
            <SeoHead
                title={aboutPage.seo.title}
                description={aboutPage.seo.description}
                canonical="/about"
            />
            <main>
                <AboutHeroSection title={aboutPage.hero.title} />
                <AboutOverviewSection overview={aboutPage.overview} />
                <AboutMissionVisionSection mission={aboutPage.mission} vision={aboutPage.vision} />
                <AboutLeapfrogSection
                    leapfrog={aboutPage.leapfrog}
                    values={aboutPage.coreValues.values}
                />
                <AboutCoreValuesSection coreValues={aboutPage.coreValues} />
                <AboutImpactSection impact={aboutPage.impact} onLearnMore={() => openModal()} />
                <JourneyTimeline milestones={aboutPage.journey.milestones} />
                <AboutContactSection />
            </main>
            <LearnMoreInquiryModal open={open} onOpenChange={onOpenChange} initialRole={initialRole} />
        </>
    );
}
