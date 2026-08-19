import AuthLayoutTemplate from '@auth/layouts/auth/auth-simple-layout';
import { ScrollConfigProvider } from '@marketing/providers/scroll-provider';

export default function AuthLayout({
    title = '',
    description = '',
    containerClassName,
    children,
}: {
    title?: string;
    description?: string;
    containerClassName?: string;
    children: React.ReactNode;
}) {
    return (
        <ScrollConfigProvider enabled={false} transitions={false}>
            <AuthLayoutTemplate
                title={title}
                description={description}
                containerClassName={containerClassName}
            >
                {children}
            </AuthLayoutTemplate>
        </ScrollConfigProvider>
    );
}
