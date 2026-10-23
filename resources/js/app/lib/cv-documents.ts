import type { CvProfile } from '@app/types/cv';

export type DocumentItem = {
    label: string;
    href: string;
    kind: 'file' | 'image' | 'link';
};

export type PreviewMode = 'image' | 'pdf';

export type MediaPreview = {
    src: string;
    label: string;
    mode: PreviewMode;
};

const IMAGE_EXT_PATTERN = /\.(png|jpe?g|webp|gif|bmp|svg)(\?.*)?$/i;
const PDF_EXT_PATTERN = /\.pdf(\?.*)?$/i;

function pathFromHref(href: string): string {
    try {
        return new URL(href, 'http://localhost').pathname;
    } catch {
        return href;
    }
}

export function previewModeFor(doc: DocumentItem): PreviewMode | null {
    if (doc.kind === 'link') {
        return null;
    }

    const path = pathFromHref(doc.href);

    if (doc.kind === 'image' || IMAGE_EXT_PATTERN.test(path)) {
        return 'image';
    }

    if (PDF_EXT_PATTERN.test(path) || doc.kind === 'file') {
        return 'pdf';
    }

    return null;
}

function storageUrl(path: string | null | undefined): string | null {
    if (!path) {
        return null;
    }

    if (
        path.startsWith('http://') ||
        path.startsWith('https://') ||
        path.startsWith('/')
    ) {
        return path;
    }

    return `/storage/${path.replace(/^\/+/, '')}`;
}

export function collectDocuments(cv: CvProfile): DocumentItem[] {
    const items: DocumentItem[] = [];

    if (cv.passport_document_url) {
        items.push({
            label: 'Passport / ID document',
            href: cv.passport_document_url,
            kind: 'file',
        });
    }

    if (cv.drivers_license_document_url) {
        items.push({
            label: "Driver's license",
            href: cv.drivers_license_document_url,
            kind: 'file',
        });
    }

    if (cv.passport_photo_url) {
        items.push({
            label: 'Passport photo',
            href: cv.passport_photo_url,
            kind: 'image',
        });
    }

    if (cv.full_body_photo_url) {
        items.push({
            label: 'Full-body photo',
            href: cv.full_body_photo_url,
            kind: 'image',
        });
    }

    for (const experience of cv.vitae.experiences) {
        const href = storageUrl(experience.certificate_path);

        if (href) {
            items.push({
                label: experience.organization
                    ? `Experience letter — ${experience.organization}`
                    : 'Experience letter',
                href,
                kind: 'file',
            });
        }
    }

    for (const qualification of cv.vitae.qualifications) {
        const href = storageUrl(qualification.certificate_path);

        if (href) {
            items.push({
                label: qualification.title
                    ? `Education — ${qualification.title}`
                    : 'Education certificate',
                href,
                kind: 'file',
            });
        }
    }

    for (const training of cv.vitae.trainings) {
        const href = storageUrl(training.certificate_path);

        if (href) {
            items.push({
                label: training.topic
                    ? `Training — ${training.topic}`
                    : 'Training certificate',
                href,
                kind: 'file',
            });
        }
    }

    const activities = [
        ...cv.vitae.seminars,
        ...cv.vitae.presentations,
        ...cv.vitae.publications,
    ];

    for (const entry of activities) {
        const fileHref = storageUrl(entry.certificate_path);

        if (fileHref) {
            items.push({
                label: entry.topic ?? 'Activity evidence',
                href: fileHref,
                kind: 'file',
            });
        }

        if (entry.link_url) {
            items.push({
                label: entry.topic ? `Link — ${entry.topic}` : 'External link',
                href: entry.link_url,
                kind: 'link',
            });
        }
    }

    for (const cert of cv.vitae.certifications) {
        const href = storageUrl(cert.certificate_path);

        if (href) {
            items.push({
                label: cert.name ?? 'Certification',
                href,
                kind: 'file',
            });
        }
    }

    for (const license of cv.vitae.licenses) {
        const href = storageUrl(license.certificate_path);

        if (href) {
            items.push({
                label: license.title ?? 'License',
                href,
                kind: 'file',
            });
        }
    }

    for (const bilingual of cv.vitae.bilingual) {
        const href = storageUrl(bilingual.certificate_path);

        if (href) {
            items.push({
                label: bilingual.language
                    ? `Language certificate — ${bilingual.language}`
                    : 'Language certificate',
                href,
                kind: 'file',
            });
        }
    }

    return items;
}
