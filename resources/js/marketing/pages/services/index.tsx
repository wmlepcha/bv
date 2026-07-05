import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { SeoHead } from '@shared/components/seo-head';
import { ServicesCategorySection } from '@marketing/components/services/services-category-section';
import { ServicesHeroSection } from '@marketing/components/services/services-hero-section';
import { ServicesTabNav } from '@marketing/components/services/services-tab-nav';
import { useLenis } from '@marketing/providers/lenis-provider';
import type { ServicesPageProps } from '@marketing/types/services-page';

function getServicesStickyOffset(): number {
    const header = document.querySelector('header');
    const tabNav = document.querySelector('nav[aria-label="Services sections"]');
    const headerBottom = header?.getBoundingClientRect().bottom ?? 80;
    const tabNavHeight = tabNav?.getBoundingClientRect().height ?? 0;

    return headerBottom + tabNavHeight + 12;
}

function scrollToCategorySection(
    categoryId: string,
    scrollTo: (target: number | string | HTMLElement, options?: object) => void,
    lenisActive: boolean,
): void {
    const section = document.getElementById(`services-panel-${categoryId}`);

    if (!section) {
        return;
    }

    const offset = getServicesStickyOffset();

    if (lenisActive) {
        scrollTo(section, { offset: -offset, duration: 0.5 });
        return;
    }

    const top = section.getBoundingClientRect().top + window.scrollY - offset;
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
}

function resolveInitialCategoryId(categories: ServicesPageProps['categories']): string {
    if (typeof window !== 'undefined') {
        const hash = window.location.hash.replace('#', '');

        if (hash && categories.some((category) => category.id === hash)) {
            return hash;
        }
    }

    return categories[0]?.id ?? '';
}

export default function Services() {
    const { servicesPage } = usePage<{ servicesPage: ServicesPageProps }>().props;
    const { lenis, scrollTo } = useLenis();
    const skipScrollOnMountRef = useRef(true);
    const lenisRef = useRef(lenis);
    const scrollToRef = useRef(scrollTo);
    lenisRef.current = lenis;
    scrollToRef.current = scrollTo;
    const [activeCategoryId, setActiveCategoryId] = useState(() =>
        resolveInitialCategoryId(servicesPage.categories),
    );

    useEffect(() => {
        const hash = window.location.hash.replace('#', '');

        if (hash && servicesPage.categories.some((category) => category.id === hash)) {
            setActiveCategoryId(hash);
        }
    }, [servicesPage.categories]);

    const activeCategory = servicesPage.categories.find(
        (category) => category.id === activeCategoryId,
    );

    const [tabNavPinned, setTabNavPinned] = useState(false);
    const [tabNavHeight, setTabNavHeight] = useState(0);

    const handleTabNavPinChange = (pinned: boolean, height: number) => {
        setTabNavPinned(pinned);
        setTabNavHeight(height);
    };

    const sidebarStickyTop = tabNavPinned ? 80 + tabNavHeight + 24 : 112;

    const handleCategoryChange = (categoryId: string) => {
        setActiveCategoryId(categoryId);
        window.history.replaceState(null, '', `#${categoryId}`);
    };

    useEffect(() => {
        if (skipScrollOnMountRef.current) {
            skipScrollOnMountRef.current = false;
            return;
        }

        const frame = requestAnimationFrame(() => {
            scrollToCategorySection(
                activeCategoryId,
                scrollToRef.current,
                Boolean(lenisRef.current),
            );
        });

        return () => cancelAnimationFrame(frame);
    }, [activeCategoryId]);

    return (
        <>
            <SeoHead
                title={servicesPage.seo.title}
                description={servicesPage.seo.description}
                canonical="/services"
            />
            <main>
                <ServicesHeroSection hero={servicesPage.hero} />
                <ServicesTabNav
                    categories={servicesPage.categories}
                    activeId={activeCategoryId}
                    onChange={handleCategoryChange}
                    onPinChange={handleTabNavPinChange}
                />
                {activeCategory && (
                    <ServicesCategorySection
                        key={activeCategory.id}
                        category={activeCategory}
                        stickyTopOffset={sidebarStickyTop}
                    />
                )}
            </main>
        </>
    );
}
