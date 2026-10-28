export const DAY_NAMES = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

export function dayName(day: number): string {
    return DAY_NAMES[day - 1] ?? '';
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}

export function formatDuration(seconds: number | null | undefined): string {
    if (seconds === null || seconds === undefined) {
        return 'In progress';
    }

    const minutes = Math.floor(seconds / 60);

    if (minutes < 1) {
        return `${seconds}s`;
    }

    return minutes >= 60 ? `${Math.floor(minutes / 60)}h ${minutes % 60}m` : `${minutes}m ${seconds % 60}s`;
}

export function fieldError(errors: object, key: string): string | undefined {
    return (errors as Record<string, string | undefined>)[key];
}

export function csrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export async function postJson<T>(url: string, body: Record<string, unknown>): Promise<{ ok: boolean; status: number; data: T }> {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(body),
    });

    return { ok: response.ok, status: response.status, data: (await response.json()) as T };
}
