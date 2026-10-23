import getStroke from 'perfect-freehand';

export function getSvgPathFromStroke(stroke: number[][]): string {
    if (!stroke.length) {
        return '';
    }

    const d = stroke.reduce(
        (acc, [x0, y0], i, arr) => {
            const [x1, y1] = arr[(i + 1) % arr.length];
            acc.push(x0, y0, (x0 + x1) / 2, (y0 + y1) / 2);

            return acc;
        },
        ['M', ...stroke[0], 'Q'] as (string | number)[],
    );

    d.push('Z');

    return d.join(' ');
}

export function pointsToPenPath(
    points: number[][],
    strokeWidth: number,
): string {
    const stroke = getStroke(points, {
        size: strokeWidth * 2,
        thinning: 0.6,
        smoothing: 0.5,
    });

    return getSvgPathFromStroke(stroke);
}

export function penPathsFromPayload(payload: Record<string, unknown>): string[] {
    if (typeof payload.path === 'string') {
        return [payload.path];
    }

    const paths = payload.paths;

    if (Array.isArray(paths)) {
        return paths.filter((path): path is string => typeof path === 'string');
    }

    return [];
}
