<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Lock, Cpu, CreditCard } from '@lucide/vue';

const nav = [
    {
        label: 'Access',
        desc:  'Landing page lock',
        name:  'admin.settings.access',
        icon:  Lock,
    },
    {
        label: 'AI Models',
        desc:  'Claude model selection',
        name:  'admin.settings.ai',
        icon:  Cpu,
    },
    {
        label: 'Stripe',
        desc:  'Payment keys',
        name:  'admin.settings.stripe',
        icon:  CreditCard,
    },
];
</script>

<template>
    <AdminLayout>
        <div class="flex gap-8 items-start">

            <!-- Sidebar -->
            <aside class="w-52 shrink-0 sticky top-6">
                <p class="text-xs font-black uppercase tracking-widest mb-3 px-1" style="color:#AAAAAA;">Settings</p>
                <nav class="space-y-0.5">
                    <Link
                        v-for="item in nav"
                        :key="item.name"
                        :href="route(item.name)"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 group"
                        :style="route().current(item.name)
                            ? 'background:#FFFBF0; color:#F5A000;'
                            : 'color:#555555;'"
                        :class="!route().current(item.name) && 'hover:bg-gray-50'"
                    >
                        <component
                            :is="item.icon"
                            class="w-4 h-4 shrink-0"
                            :style="route().current(item.name) ? 'color:#F5A000' : 'color:#AAAAAA'"
                        />
                        <div>
                            <p class="text-sm font-semibold leading-none" :style="route().current(item.name) ? 'color:#F5A000' : 'color:#1A1A1A'">
                                {{ item.label }}
                            </p>
                            <p class="text-xs mt-0.5" style="color:#AAAAAA;">{{ item.desc }}</p>
                        </div>
                    </Link>
                </nav>
            </aside>

            <!-- Content -->
            <div class="flex-1 max-w-xl min-w-0">
                <slot />
            </div>

        </div>
    </AdminLayout>
</template>
