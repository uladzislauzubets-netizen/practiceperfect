import { createInertiaApp } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';

createInertiaApp({
    pages: './Pages',
    layout: (name: string) => (name.startsWith('Auth/') || name.startsWith('Join/') ? GuestLayout : AppLayout),
    title: (title: string) => (title ? `${title} | PracticePerfect` : 'PracticePerfect'),
});
