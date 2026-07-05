import { useEffect, useState } from 'react';
import type { LearnMoreInquiryModal as LearnMoreInquiryModalComponent } from '@marketing/components/learn-more-inquiry-modal';

interface AuthRecruiterInquiryModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

/** Lazy-loads the marketing inquiry modal on the auth pages to avoid SSR issues. */
export function AuthRecruiterInquiryModal({ open, onOpenChange }: AuthRecruiterInquiryModalProps) {
    const [Modal, setModal] = useState<typeof LearnMoreInquiryModalComponent | null>(null);

    useEffect(() => {
        if (!open || Modal) {
            return;
        }

        void import('@marketing/components/learn-more-inquiry-modal').then((module) => {
            setModal(() => module.LearnMoreInquiryModal);
        });
    }, [open, Modal]);

    if (!Modal) {
        return null;
    }

    return <Modal open={open} onOpenChange={onOpenChange} initialRole="recruiter" />;
}
