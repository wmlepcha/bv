import { Head, usePage } from '@inertiajs/react';

type JsonLd = Record<string, unknown> | Record<string, unknown>[];

type SeoHeadProps = {
    title: string;
    description: string;
    canonical?: string;
    ogImage?: string;
    ogType?: 'website' | 'article';
    keywords?: string;
    noindex?: boolean;
    jsonLd?: JsonLd;
};

export function SeoHead({
    title,
    description,
    canonical,
    ogImage,
    ogType = 'website',
    keywords,
    noindex = false,
    jsonLd,
}: SeoHeadProps) {
    const { appUrl, seo } = usePage().props;
    const canonicalUrl = canonical ? `${appUrl}${canonical}` : undefined;
    const imageUrl = ogImage
        ? ogImage.startsWith('http')
            ? ogImage
            : `${appUrl}${ogImage}`
        : `${appUrl}${seo.defaultOgImage}`;
    const fullTitle = `${title} - ${seo.siteName}`;

    return (
        <Head title={title}>
            <meta head-key="description" name="description" content={description} />

            {keywords && (
                <meta head-key="keywords" name="keywords" content={keywords} />
            )}

            {canonicalUrl && (
                <link head-key="canonical" rel="canonical" href={canonicalUrl} />
            )}

            <meta head-key="og:title" property="og:title" content={fullTitle} />
            <meta head-key="og:description" property="og:description" content={description} />
            <meta head-key="og:type" property="og:type" content={ogType} />
            <meta head-key="og:site_name" property="og:site_name" content={seo.siteName} />
            <meta head-key="og:image" property="og:image" content={imageUrl} />

            {canonicalUrl && (
                <meta head-key="og:url" property="og:url" content={canonicalUrl} />
            )}

            <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
            <meta head-key="twitter:title" name="twitter:title" content={fullTitle} />
            <meta head-key="twitter:description" name="twitter:description" content={description} />
            <meta head-key="twitter:image" name="twitter:image" content={imageUrl} />

            {seo.twitterHandle && (
                <meta
                    head-key="twitter:site"
                    name="twitter:site"
                    content={seo.twitterHandle}
                />
            )}

            {noindex && (
                <meta head-key="robots" name="robots" content="noindex, nofollow" />
            )}

            {jsonLd && (
                <script
                    head-key="json-ld"
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
                />
            )}
        </Head>
    );
}

export function PrivateSeoHead({ title }: { title: string }) {
    return <SeoHead title={title} description="Private page." noindex />;
}
