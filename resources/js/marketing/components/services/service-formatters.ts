const deliveryModeLabels: Record<string, string> = {
    offline: 'Offline Classes',
    online_live: 'Online Live Classes',
    hybrid: 'Hybrid Classes',
};

export function formatDeliveryModes(modes: string[]): string {
    return modes
        .map((mode) => deliveryModeLabels[mode] ?? mode)
        .join(' | ');
}
