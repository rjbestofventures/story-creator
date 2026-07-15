<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ShieldCheck, Users, BookOpen, Layers, TrendingUp, Settings, FileText, ClipboardList } from '@lucide/vue';
import { TooltipProvider } from '@/Components/ui/tooltip';
import Footer from '@/Components/Footer.vue';

const page = usePage();
const isSuperAdmin = computed(() => page.props.auth?.user?.roles?.includes('super_admin'));

const allNav = [
    { label: 'Users & Plans',   name: 'admin.users.index',    icon: Users,      match: 'admin.users.*',    superOnly: false },
    { label: 'Credit Packs',    name: 'admin.packs.index',    icon: Layers,     match: 'admin.packs.*',    superOnly: true  },
    { label: 'All Stories',     name: 'admin.stories.index',  icon: BookOpen,   match: 'admin.stories.*',  superOnly: false },
    { label: 'Interviews',      name: 'grill.index',          icon: ClipboardList, match: 'grill.*',       superOnly: false },
    { label: 'Usage & Billing', name: 'admin.billing.index',  icon: TrendingUp, match: 'admin.billing.*',  superOnly: true  },
    { label: 'Settings',        name: 'admin.settings.index', icon: Settings,   match: 'admin.settings.*', superOnly: true  },
];

const nav = computed(() => allNav.filter(item => !item.superOnly || isSuperAdmin.value));

const isActive = (item) => route().current(item.match);
</script>

<template>
    <div class="min-h-screen" style="background-color: #FAFAF8;">

        <!-- Top bar -->
        <div class="bg-white border-b px-4 md:px-8 py-4" style="border-color: #DDDDDD;">
            <div class="max-w-6xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('dashboard')"
                        class="flex items-center justify-center w-8 h-8 rounded-lg hover:bg-gray-100 transition cursor-pointer"
                        style="color: #555555;"
                    >
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="w-5 h-5" style="color: #F5A000;" />
                        <h1 class="text-lg font-black" style="color: #1A1A1A;">
                            Admin
                            <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Panel</span>
                        </h1>
                    </div>
                </div>
                <Link :href="route('admin.manual')" class="hidden md:flex items-center gap-1.5 text-sm hover:opacity-70 transition" style="color: #555555;">
                    <FileText class="w-4 h-4" /> Admin Manual
                </Link>
            </div>
        </div>

        <!-- Module nav -->
        <div class="bg-white border-b" style="border-color: #DDDDDD;">
            <div class="max-w-6xl mx-auto px-4 md:px-8 flex overflow-x-auto">
                <Link
                    v-for="item in nav"
                    :key="item.name"
                    :href="route(item.name)"
                    class="shrink-0 flex items-center gap-2 px-4 py-3.5 text-sm font-semibold whitespace-nowrap relative transition-colors"
                    :style="isActive(item) ? 'color: #1A1A1A;' : 'color: #555555;'"
                >
                    <component :is="item.icon" class="w-4 h-4" />
                    {{ item.label }}
                    <span
                        v-if="isActive(item)"
                        class="absolute bottom-0 left-0 right-0 h-0.5"
                        style="background: linear-gradient(to right, #FFC837, #F5A000);"
                    />
                </Link>
            </div>
        </div>

        <!-- Page content -->
        <TooltipProvider :delay-duration="300">
            <div class="max-w-6xl mx-auto px-4 md:px-8 py-6">
                <slot />
            </div>
        </TooltipProvider>

        <Footer />
    </div>
</template>
