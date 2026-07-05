export type ProjectsPageCategory = {
    name: string;
    slug: string;
};

export type ProjectsPagePostCard = {
    slug: string;
    title: string;
    excerpt: string;
    cardLayout: 'wide' | 'tall';
    readingTimeMinutes: number;
    category: ProjectsPageCategory;
    featuredImageUrl: string | null;
    featuredImageAlt: string | null;
};

export type ProjectsPageProps = {
    hero: {
        title: string;
        subtitle: string;
    };
    seo: {
        title: string;
        description: string;
    };
    posts: ProjectsPagePostCard[];
};

export type BlogSectionType =
    | 'heading'
    | 'paragraph'
    | 'bullet_list'
    | 'faq'
    | 'quote'
    | 'image'
    | 'video';

export type BlogSection = {
    type: BlogSectionType;
    content: Record<string, unknown>;
};

export type ProjectShowPost = {
    slug: string;
    title: string;
    excerpt: string;
    readingTimeMinutes: number;
    publishedAt: string | null;
    publishedAtFormatted: string | null;
    category: ProjectsPageCategory;
    author: {
        name: string | null;
        bio: string | null;
    };
    heroImageUrl: string | null;
    heroImageAlt: string | null;
    sections: BlogSection[];
    seo: {
        title: string;
        description: string;
        keywords: string | null;
        ogImage: string | null;
        noindex: boolean;
    };
};

export type ProjectShowPageProps = {
    post: ProjectShowPost;
};
