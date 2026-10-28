export type Role = 'super_admin' | 'trainer' | 'coach' | 'player';

export interface AuthUser {
    id: number;
    name: string;
    first_name: string;
    email: string;
    role: Role;
    role_label: string;
    is_child: boolean;
    avatar_url: string | null;
    email_verified: boolean;
    unread_notifications: number;
}

export interface Branding {
    name: string | null;
    color: string;
    logo_url: string | null;
    is_custom: boolean;
}

export interface ContextOption {
    profile_id: number;
    trainer_id: number;
    label: string;
    key: string;
}

export interface ContextData {
    current: string | null;
    current_label: string | null;
    groups: { title: string; options: ContextOption[] }[];
}

export interface Impersonation {
    target_name: string;
    target_role: string;
    expires_at: string;
}

export interface SharedProps {
    auth: AuthUser | null;
    branding: Branding;
    context: ContextData | null;
    impersonation: Impersonation | null;
    flash: { success: string | null; error: string | null };
    app_name: string;
    errors: Record<string, string>;
    [key: string]: unknown;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface Slot {
    day_of_week: number;
    start_time: string;
    end_time: string;
    is_available: boolean;
}

export interface Option {
    value: string;
    label: string;
}
