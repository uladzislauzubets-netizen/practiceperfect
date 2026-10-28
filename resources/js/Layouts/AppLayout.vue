<script setup lang="ts">
import Avatar from '@/Components/Avatar.vue';
import ContextSwitcher from '@/Components/ContextSwitcher.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import ImpersonationBanner from '@/Components/ImpersonationBanner.vue';
import { applyBrandColor } from '@/lib/branding';
import type { Role, SharedProps } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';

const page = usePage<SharedProps>();
const auth = computed(() => page.props.auth);
const branding = computed(() => page.props.branding);
const menuOpen = ref(false);

watchEffect(() => applyBrandColor(branding.value.color));

interface NavItem {
    label: string;
    href: string;
}

const navigation = computed<NavItem[]>(() => {
    const role: Role | undefined = auth.value?.role;
    const common: NavItem[] = [{ label: 'Dashboard', href: '/dashboard' }];

    switch (role) {
        case 'super_admin':
            return [...common, { label: 'Users', href: '/admin/users' }, { label: 'Impersonation history', href: '/admin/impersonation-history' }];
        case 'trainer':
            return [
                ...common,
                { label: 'Players', href: '/trainer/players' },
                { label: 'Coaches', href: '/trainer/coaches' },
                { label: 'ShareLink', href: '/trainer/share-link' },
                { label: 'Branding', href: '/trainer/branding' },
            ];
        case 'coach':
            return [...common, { label: 'My Times', href: '/coach/my-times' }];
        case 'player':
            return auth.value?.is_child
                ? [...common, { label: 'Availability', href: '/availability' }, { label: 'My requests', href: '/purchase-requests' }]
                : [
                      ...common,
                      { label: 'Family', href: '/family' },
                      { label: 'Availability', href: '/availability' },
                      { label: 'Approvals', href: '/family/approvals' },
                  ];
        default:
            return common;
    }
});

function isActive(href: string): boolean {
    return page.url === href || page.url.startsWith(href + '/') || page.url.startsWith(href + '?');
}

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-h-screen bg-page">
        <ImpersonationBanner v-if="page.props.impersonation" :impersonation="page.props.impersonation" />

        <div class="flex min-h-screen flex-col lg:flex-row">
            <aside class="border-b border-border-soft bg-surface lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:shrink-0 lg:border-r lg:border-b-0">
                <div class="flex items-center justify-between gap-3 px-5 py-4 lg:block">
                    <Link href="/dashboard" class="flex items-center gap-3">
                        <img v-if="branding.logo_url" :src="branding.logo_url" :alt="branding.name ?? page.props.app_name" class="size-10 rounded-sm object-contain" />
                        <span v-else class="inline-flex size-10 items-center justify-center rounded-sm bg-gradient-to-b from-brand to-brand-deep text-block font-bold text-white">P</span>
                        <span class="text-card leading-tight font-bold">{{ branding.name ?? page.props.app_name }}</span>
                    </Link>
                    <button type="button" class="rounded-xs border border-border-strong px-3 py-1.5 text-caption lg:hidden" :aria-expanded="menuOpen" @click="menuOpen = !menuOpen">
                        Menu
                    </button>
                </div>

                <div class="flex-col gap-5 px-3 pb-4 lg:flex" :class="menuOpen ? 'flex' : 'hidden'">
                    <div v-if="page.props.context" class="px-2"><ContextSwitcher :context="page.props.context" /></div>

                    <nav class="flex flex-col gap-1" aria-label="Main">
                        <Link
                            v-for="item in navigation"
                            :key="item.href"
                            :href="item.href"
                            class="rounded-sm px-3 py-2 text-body font-semibold transition"
                            :class="isActive(item.href) ? 'bg-brand/15 text-brand-soft' : 'text-ink-muted hover:bg-surface-raised hover:text-ink'"
                            :aria-current="isActive(item.href) ? 'page' : undefined"
                        >
                            {{ item.label }}
                        </Link>
                    </nav>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="flex items-center justify-end gap-4 border-b border-border-soft px-6 py-3">
                    <Link href="/notifications" class="relative rounded-sm px-3 py-1.5 text-body text-ink-muted hover:text-ink">
                        Notifications
                        <span
                            v-if="auth && auth.unread_notifications > 0"
                            class="ml-1.5 inline-flex min-w-5 items-center justify-center rounded-pill bg-brand px-1.5 text-caption font-semibold text-white"
                        >
                            {{ auth.unread_notifications }}
                        </span>
                    </Link>
                    <Link v-if="auth" href="/profile" class="flex items-center gap-2.5 rounded-sm px-2 py-1 hover:bg-surface-raised">
                        <Avatar :name="auth.name" :src="auth.avatar_url" :size="32" />
                        <span class="hidden text-left sm:block">
                            <span class="block text-body leading-tight font-semibold">{{ auth.name }}</span>
                            <span class="block text-caption leading-tight text-ink-subtle">{{ auth.role_label }}</span>
                        </span>
                    </Link>
                    <button type="button" class="rounded-sm px-3 py-1.5 text-body text-ink-muted hover:text-ink" @click="logout">Log out</button>
                </header>

                <main class="mx-auto w-full max-w-6xl flex-1 px-6 py-8">
                    <FlashMessages class="mb-6" />
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
