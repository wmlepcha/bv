export type MemoColorScheme = {
    id: string;
    bg: string;
    border: string;
    text: string;
};

export const PEN_COLORS = [
    '#2563EB',
    '#E53935',
    '#16A34A',
    '#9333EA',
    '#EA580C',
] as const;

export const HIGHLIGHT_COLORS = [
    '#FFE066',
    '#86EFAC',
    '#93C5FD',
    '#F9A8D4',
    '#FDBA74',
] as const;

export const MEMO_COLORS: MemoColorScheme[] = [
    { id: 'yellow', bg: '#FEF9C3', border: '#FDE047', text: '#713F12' },
    { id: 'orange', bg: '#FFEDD5', border: '#FDBA74', text: '#7C2D12' },
    { id: 'pink', bg: '#FCE7F3', border: '#F9A8D4', text: '#831843' },
    { id: 'blue', bg: '#DBEAFE', border: '#93C5FD', text: '#1E3A8A' },
    { id: 'green', bg: '#DCFCE7', border: '#86EFAC', text: '#14532D' },
    { id: 'lavender', bg: '#EDE9FE', border: '#C4B5FD', text: '#4C1D95' },
];

export const STROKE_WIDTHS = [2, 4, 6] as const;

export const DEFAULT_PEN_COLOR = PEN_COLORS[0];
export const DEFAULT_HIGHLIGHT_COLOR = HIGHLIGHT_COLORS[0];
export const DEFAULT_MEMO_COLOR = MEMO_COLORS[0].id;

export function memoColorById(id: string | undefined): MemoColorScheme {
    return MEMO_COLORS.find((color) => color.id === id) ?? MEMO_COLORS[0];
}

export function highlightBackground(color: string): string {
    if (color.startsWith('#') && color.length === 7) {
        return `${color}99`;
    }

    return color;
}
