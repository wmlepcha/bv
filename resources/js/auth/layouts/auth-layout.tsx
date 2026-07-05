import AuthLayoutTemplate from '@auth/layouts/auth/auth-simple-layout';
import { ScrollConfigProvider } from '@marketing/providers/scroll-provider';

export default function AuthLayout({
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
            <AuthLayoutTemplate title={title} description={description}>
                {children}
            </AuthLayoutTemplate>
        </ScrollConfigProvider>
    );
}
