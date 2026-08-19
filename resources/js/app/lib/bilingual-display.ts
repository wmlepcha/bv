import type { CvBilingualEntry } from '@app/types/cv';

export const LANGUAGE_TYPE_ORDER = [
    'Regional',
    'Regional/Foreign',
    'Foreign Language',
] as const;

export type LanguageType = (typeof LANGUAGE_TYPE_ORDER)[number];

export type BilingualDisplayLabels = {
    languagesKnown: string;
    languageTypeRegional: string;
    languageTypeRegionalForeign: string;
    languageTypeForeign: string;
};

export type BilingualDisplayGroup = {
    type: LanguageType | 'untyped';
    label: string;
    text: string;
};

function formatLanguageName(entry: CvBilingualEntry): string {
    const language = String(entry.language ?? '').trim();
    if (!language) {
        return '';
    }

    const level = String(entry.certification_level ?? '').trim();
    if (level) {
        return `${language} (${level})`;
    }

    return language;
}

function typeLabel(
    type: LanguageType,
    labels: BilingualDisplayLabels,
): string {
    switch (type) {
        case 'Regional':
            return labels.languageTypeRegional;
        case 'Regional/Foreign':
            return labels.languageTypeRegionalForeign;
        case 'Foreign Language':
            return labels.languageTypeForeign;
    }
}

/**
 * Groups filled bilingual entries for CV display under "Languages Known".
 * Typed entries appear as Regional / Regional/Foreign / Foreign Language lines.
 * Legacy entries without language_type become a single untyped comma list.
 */
export function groupBilingualForDisplay(
    entries: CvBilingualEntry[],
    labels: BilingualDisplayLabels,
): BilingualDisplayGroup[] {
    const filled = entries.filter((entry) =>
        Boolean(String(entry.language ?? '').trim()),
    );

    if (filled.length === 0) {
        return [];
    }

    const byType = new Map<LanguageType, string[]>();
    const untyped: string[] = [];

    for (const entry of filled) {
        const formatted = formatLanguageName(entry);
        if (!formatted) {
            continue;
        }

        const rawType = String(entry.language_type ?? '').trim();
        if ((LANGUAGE_TYPE_ORDER as readonly string[]).includes(rawType)) {
            const type = rawType as LanguageType;
            const list = byType.get(type) ?? [];
            list.push(formatted);
            byType.set(type, list);
        } else {
            untyped.push(formatted);
        }
    }

    const groups: BilingualDisplayGroup[] = [];

    for (const type of LANGUAGE_TYPE_ORDER) {
        const names = byType.get(type);
        if (!names || names.length === 0) {
            continue;
        }

        groups.push({
            type,
            label: typeLabel(type, labels),
            text: names.join(', '),
        });
    }

    if (untyped.length > 0) {
        groups.push({
            type: 'untyped',
            label: '',
            text: untyped.join(', '),
        });
    }

    return groups;
}
