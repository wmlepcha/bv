import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import type { InquiryRole } from '@marketing/components/learn-more-inquiry-modal';

function inquiryRoleFromUrl(url: string): InquiryRole | undefined {
    const query = url.includes('?') ? url.split('?')[1] : '';
    const param = new URLSearchParams(query).get('inquiry');

    if (param === 'recruiter' || param === 'candidate') {
        return param;
    }

    return undefined;
}

export function useInquiryModal() {
    const { url } = usePage();
    const inquiryFromUrl = useMemo(() => inquiryRoleFromUrl(url), [url]);
    const [open, setOpen] = useState(false);
    const [initialRole, setInitialRole] = useState<InquiryRole | undefined>();

    useEffect(() => {
        if (!inquiryFromUrl) {
            return;
        }

        setInitialRole(inquiryFromUrl);
        setOpen(true);
    }, [inquiryFromUrl]);

    const openModal = useCallback((role?: InquiryRole) => {
        setInitialRole(role);
        setOpen(true);
    }, []);

    const onOpenChange = useCallback(
        (nextOpen: boolean) => {
            if (!nextOpen) {
                setInitialRole(undefined);

                if (inquiryFromUrl) {
                    const pathname = url.split('?')[0] || '/';

                    router.visit(pathname, {
                        preserveState: true,
                        preserveScroll: true,
                        replace: true,
                    });
                }
            }

            setOpen(nextOpen);
        },
        [inquiryFromUrl, url],
    );

    return {
        open,
        initialRole,
        openModal,
        onOpenChange,
    };
}
