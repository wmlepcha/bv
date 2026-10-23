import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';
import RegistrationRenewalBanner from '@app/components/registration-renewal-banner';
import { Breadcrumbs } from '@shared/components/breadcrumbs';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="flex min-h-16 shrink-0 items-center gap-3 pl-6 pr-4 py-2 transition-[width,height] ease-linear sm:pl-10 sm:pr-6">
            <div className="flex min-w-0 flex-1 items-center gap-2">
                <SidebarTrigger className="-ml-1 shrink-0" />
                <div className="min-w-0 overflow-hidden">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
            </div>
            <RegistrationRenewalBanner />
        </header>
    );
}
