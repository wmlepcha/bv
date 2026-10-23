import AuthLayoutTemplate from '@auth/layouts/auth/auth-simple-layout';
import { ScrollConfigProvider } from '@marketing/providers/scroll-provider';

export default function AuthLayout({
    title = '',
    description = '',
    containerClassName,
    backHref,
    children,
}: {
    title?: string;
    description?: string;
    containerClassName?: string;
    backHref?: string;
    children: React.ReactNode;
}) {
    return (
        <ScrollConfigProvider enabled={false} transitions={false}>
            <AuthLayoutTemplate
                title={title}
                description={description}
                containerClassName={containerClassName}
                backHref={backHref}
            >
                {children}
            </AuthLayoutTemplate>
        </ScrollConfigProvider>
    );
}
