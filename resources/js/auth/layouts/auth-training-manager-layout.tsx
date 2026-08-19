import AuthTrainingManagerLayoutTemplate from '@auth/layouts/auth/auth-training-manager-layout';
import { ScrollConfigProvider } from '@marketing/providers/scroll-provider';

export default function AuthTrainingManagerLayout({
    title = '',
    description = '',
    children,
}: {
    title?: string;
    description?: string;
    children: React.ReactNode;
}) {
    return (
        <ScrollConfigProvider enabled={false} transitions={false}>
            <AuthTrainingManagerLayoutTemplate
                title={title}
                description={description}
            >
                {children}
            </AuthTrainingManagerLayoutTemplate>
        </ScrollConfigProvider>
    );
}
