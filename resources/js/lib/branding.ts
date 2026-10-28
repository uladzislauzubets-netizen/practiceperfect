export function hexToRgb(hex: string): [number, number, number] {
    const value = hex.replace('#', '');
    const number = parseInt(value, 16);

    return [(number >> 16) & 255, (number >> 8) & 255, number & 255];
}

function toHex([red, green, blue]: [number, number, number]): string {
    return '#' + [red, green, blue].map((channel) => Math.round(channel).toString(16).padStart(2, '0')).join('');
}

export function lightenColor(hex: string, percent: number): string {
    return toHex(hexToRgb(hex).map((channel) => channel + (255 - channel) * (percent / 100)) as [number, number, number]);
}

export function darkenColor(hex: string, percent: number): string {
    return toHex(hexToRgb(hex).map((channel) => channel * (1 - percent / 100)) as [number, number, number]);
}

export function isHexColor(value: string): boolean {
    return /^#[0-9a-fA-F]{6}$/.test(value);
}

export function applyBrandColor(hex: string): void {
    if (typeof document === 'undefined' || !isHexColor(hex)) {
        return;
    }

    const root = document.documentElement.style;
    root.setProperty('--brand-primary', hex);
    root.setProperty('--brand-primary-soft', lightenColor(hex, 20));
    root.setProperty('--brand-primary-deep', darkenColor(hex, 20));
    root.setProperty('--brand-primary-rgb', hexToRgb(hex).join(', '));
}
