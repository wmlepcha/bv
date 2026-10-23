import AuthTrainingManagerLayoutTemplate from '@auth/layouts/auth/auth-training-manager-layout';
import { ScrollConfigProvider } from '@marketing/providers/scroll-provider';

export default function AuthTrainingManagerLayout({
    title = '',
    description = '',
    tagline,
    blurb,
    children,
}: {
    title?: string;
    description?: string;
    tagline?: string;
    blurb?: string;
    children: React.ReactNode;
}) {
    return (
        <ScrollConfigProvider enabled={false} transitions={false}>
            <AuthTrainingManagerLayoutTemplate
                title={title}
                description={description}
                tagline={tagline}
                blurb={blurb}
            >
                {children}
            </AuthTrainingManagerLayoutTemplate>
        </ScrollConfigProvider>
    );
}
