import { usePage } from '@inertiajs/react';
import { SeoHead } from '@shared/components/seo-head';
import { HeroSection } from '@marketing/components/hero-section';
import { CraftingSection } from '@marketing/components/crafting-section';
import { RecruitersSection } from '@marketing/components/recruiters-section';
import { ServicesSection } from '@marketing/components/services-section';
import { LearnMoreInquiryModal } from '@marketing/components/learn-more-inquiry-modal';
import { ScrollProgressIndicator } from '@marketing/components/animations/progress';
import { useInquiryModal } from '@marketing/hooks/use-inquiry-modal';
import { buildHomeJsonLd } from '@marketing/seo/json-ld';
import type { HomePageProps } from '@marketing/types/home-page';

export default function Home() {
    const { open, initialRole, openModal, onOpenChange } = useInquiryModal();
    const { appUrl, seo, homePage } = usePage<{ homePage: HomePageProps }>().props;

    return (
        <>
            <SeoHead
                title={homePage.seo.title}
                description={homePage.seo.description}
                canonical="/"
                jsonLd={buildHomeJsonLd({
                    appUrl,
                    siteName: seo.siteName,
                    description: homePage.seo.description,
                    logoUrl: `${appUrl}${seo.defaultOgImage}`,
                })}
            />
            <ScrollProgressIndicator />
            <main>
                <HeroSection hero={homePage.hero} />
                <CraftingSection crafting={homePage.crafting} onLearnMore={() => openModal()} />
                <RecruitersSection recruiters={homePage.recruiters} onLearnMore={() => openModal()} />
                <ServicesSection services={homePage.services} />
            </main>
            <LearnMoreInquiryModal open={open} onOpenChange={onOpenChange} initialRole={initialRole} />
        </>
    );
}
