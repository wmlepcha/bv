type ArticleJsonLdParams = {
    appUrl: string;
    headline: string;
    description: string;
    url: string;
    image?: string;
    datePublished?: string;
    authorName: string;
};

export function buildArticleJsonLd({
    headline,
    description,
    url,
    image,
    datePublished,
    authorName,
}: ArticleJsonLdParams): Record<string, unknown> {
    return {
        '@context': 'https://schema.org',
        '@type': 'Article',
        headline,
        description,
        url,
        ...(image ? { image } : {}),
        ...(datePublished ? { datePublished } : {}),
        author: {
            '@type': 'Person',
            name: authorName,
        },
    };
}
