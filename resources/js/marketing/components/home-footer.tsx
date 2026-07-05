import { usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export function HomeFooter() {
    const { siteLayout } = usePage().props;
    const { contactEmail, contactPhone, copyright, offices, socialLinks } = siteLayout.footer;

    return (
        <footer className="border-t border-border bg-muted/20 px-4 py-16 sm:px-6 sm:py-24">
            <div className="mx-auto max-w-7xl 2xl:max-w-[90rem]">
                <h2 className="changa-one-regular animate-in fade-in duration-700 text-2xl tracking-tight md:text-3xl">
                    Contact
                </h2>

                <div className="mt-8 grid gap-6 sm:grid-cols-2">
                    <div className="animate-in fade-in duration-700 delay-100 fill-mode-both">
                        <p className="text-sm font-medium text-muted-foreground">Email</p>
                        <a
                            href={`mailto:${contactEmail}`}
                            className="mt-2 block transition-colors hover:text-primary"
                        >
                            {contactEmail}
                        </a>
                    </div>
                    <div className="animate-in fade-in duration-700 delay-200 fill-mode-both">
                        <p className="text-sm font-medium text-muted-foreground">Phone</p>
                        <a
                            href={`tel:${contactPhone.replace(/\s/g, '')}`}
                            className="mt-2 block transition-colors hover:text-primary"
                        >
                            {contactPhone}
                        </a>
                    </div>
                </div>

                <div className="mt-12 grid gap-8 lg:grid-cols-3">
                    {offices.map((office, index) => (
                        <div
                            key={office.name}
                            className="animate-in fade-in duration-700 fill-mode-both"
                            style={{ animationDelay: `${300 + index * 100}ms` }}
                        >
                            <p className="text-sm font-medium text-muted-foreground">
                                {office.name}
                            </p>
                            <p className="mt-2 text-sm leading-relaxed">{office.address}</p>
                        </div>
                    ))}
                </div>

                <div className="mt-16 animate-in fade-in duration-700 delay-300 fill-mode-both">
                    <p className="text-sm font-medium text-muted-foreground">
                        Social Media Links
                    </p>
                    <div className="mt-4 flex flex-wrap gap-6">
                        {socialLinks.map((link, index) => (
                            <a
                                key={link.label}
                                href={link.url}
                                className={cn(
                                    'animate-in fade-in duration-700 fill-mode-both text-sm transition-colors hover:text-primary',
                                    'relative after:absolute after:bottom-0 after:left-0 after:h-px after:w-0 after:bg-primary after:transition-all hover:after:w-full',
                                )}
                                style={{ animationDelay: `${400 + index * 75}ms` }}
                            >
                                {link.label}
                            </a>
                        ))}
                    </div>
                </div>

                <div className="mt-16 animate-in fade-in duration-700 delay-500 fill-mode-both border-t border-border pt-8">
                    <p className="text-center text-sm text-muted-foreground">
                        &copy; {new Date().getFullYear()} {copyright}
                    </p>
                </div>
            </div>
        </footer>
    );
}
