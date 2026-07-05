type HomeJsonLdParams = {
    appUrl: string;
    siteName: string;
    description: string;
    logoUrl: string;
};

export function buildHomeJsonLd({
    appUrl,
    siteName,
    description,
    logoUrl,
}: HomeJsonLdParams): Record<string, unknown>[] {
    return [
        {
            '@context': 'https://schema.org',
            '@type': 'Organization',
            name: siteName,
            url: appUrl,
            logo: logoUrl,
            description,
            areaServed: {
                '@type': 'AdministrativeArea',
                name: 'Sikkim, India',
            },
        },
        {
            '@context': 'https://schema.org',
            '@type': 'WebSite',
            name: siteName,
            url: appUrl,
            description,
        },
    ];
}
