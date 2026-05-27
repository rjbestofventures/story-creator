<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Users, BookOpen, Activity, TrendingUp,
    Search, UserPlus, CircleUser, KeyRound, Trash2, Mail,
    ChevronDown, ChevronRight, BookMarked, Coins, Layers, Check,
} from '@lucide/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    users: Array,
    plans: Array,
    stats: Object,
});

// ── State ─────────────────────────────────────────────────────────────────────

const search       = ref('');
const expandedUser = ref(null);
const savedId      = ref(null);

const flash = (id) => { savedId.value = id; setTimeout(() => savedId.value = null, 1800); };

// ── Derived ───────────────────────────────────────────────────────────────────

const filtered = computed(() =>
    props.users.filter(u =>
        u.name.toLowerCase().includes(search.value.toLowerCase()) ||
        u.email.toLowerCase().includes(search.value.toLowerCase()) ||
        u.tier.toLowerCase().includes(search.value.toLowerCase())
    )
);

const kpis = computed(() => [
    { label: 'Total Users', value: props.stats.users,                                                     icon: Users,      color: '#F5A000' },
    { label: 'Active',      value: props.users.filter(u => u.subscription?.status === 'active').length,   icon: Activity,   color: '#22C55E' },
    { label: 'Trialing',    value: props.users.filter(u => u.subscription?.status === 'trialing').length, icon: TrendingUp, color: '#F59E0B' },
    { label: 'Stories',     value: props.stats.stories,                                                   icon: BookOpen,   color: '#6366F1' },
]);

// ── Helpers ───────────────────────────────────────────────────────────────────

const statusStyle = (status) => ({
    active:    { bg: '#DCFCE7', color: '#15803D', label: 'Active'    },
    trialing:  { bg: '#FEF9C3', color: '#A16207', label: 'Trialing'  },
    cancelled: { bg: '#FEE2E2', color: '#DC2626', label: 'Cancelled' },
    expired:   { bg: '#F3F4F6', color: '#6B7280', label: 'Expired'   },
})[status] ?? { bg: '#F3F4F6', color: '#6B7280', label: 'No Plan' };

const tierLabel = (tier) => ({
    admin:      'Admin',
    partner:    'Partner',
    subscriber: 'Subscriber',
    viewer:     'Viewer',
})[tier] ?? tier;

const initials    = (name) => name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
const avatarColor = (name) => {
    const colors = ['#F5A000', '#6366F1', '#22C55E', '#EF4444', '#8B5CF6', '#EC4899'];
    return colors[name.charCodeAt(0) % colors.length];
};

const planPrice = (planId, interval) => {
    const plan = props.plans.find(p => p.id === planId);
    if (!plan) return null;
    if (interval === 'yearly') return plan.price_yearly  > 0 ? `$${plan.price_yearly}/yr`  : 'Free';
    return plan.price_monthly > 0 ? `$${plan.price_monthly}/mo` : 'Free';
};

// Returns inline style string for the stories chip based on credits remaining
const storyChipStyle = (sub) => {
    if (sub.story_credits === 0)                              return 'background:#FEE2E2; color:#DC2626;';
    if (sub.story_credits * 2 <= sub.stories_per_month)      return 'background:#FEF9C3; color:#A16207;';
    return 'background:#DCFCE7; color:#15803D;';
};

// Width % for the stories progress bar (amount used, not remaining)
const storyBarWidth = (sub) => {
    if (!sub.stories_per_month) return '0%';
    const used = sub.stories_per_month - sub.story_credits;
    return (Math.min(used / sub.stories_per_month, 1) * 100) + '%';
};

// ── Per-user forms ────────────────────────────────────────────────────────────

const planForms   = ref({});
const statusForms = ref({});
const tierForms   = ref({});

const getPlanForm = (user) => {
    if (!planForms.value[user.id]) {
        planForms.value[user.id] = useForm({
            plan_id:          user.subscription?.plan_id ?? '',
            billing_interval: user.subscription?.billing_interval ?? 'monthly',
        });
    }
    return planForms.value[user.id];
};

const getStatusForm = (user) => {
    if (!statusForms.value[user.id]) {
        statusForms.value[user.id] = useForm({ status: user.subscription?.status ?? 'active' });
    }
    return statusForms.value[user.id];
};

const getTierForm = (user) => {
    if (!tierForms.value[user.id]) {
        tierForms.value[user.id] = useForm({ tier: user.tier });
    }
    return tierForms.value[user.id];
};

const savePlan = (user) => {
    getPlanForm(user).post(route('admin.users.assign-plan', user.id), {
        onSuccess: () => flash(user.id),
    });
};

const saveStatus = (user) => {
    getStatusForm(user).patch(route('admin.users.subscription', user.id), {
        onSuccess: () => flash(user.id),
    });
};

const saveTier = (user) => {
    getTierForm(user).patch(route('admin.users.update', user.id), {
        onSuccess: () => flash(user.id),
    });
};

const destroyUser = (user) => {
    if (confirm(`Delete ${user.name}? This cannot be undone.`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
};

const toggleExpand = (id) => {
    expandedUser.value = expandedUser.value === id ? null : id;
};
</script>

<template>
    <AdminLayout>
        <Head title="Users — Admin" />

        <!-- KPI cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <div
                v-for="kpi in kpis" :key="kpi.label"
                class="bg-white rounded-xl px-4 py-3 flex items-center gap-3"
                style="border: 1px solid #DDDDDD;"
            >
                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :style="{ backgroundColor: kpi.color + '18' }">
                    <component :is="kpi.icon" class="w-4 h-4" :style="{ color: kpi.color }" />
                </div>
                <div>
                    <p class="text-xs font-medium" style="color: #555555;">{{ kpi.label }}</p>
                    <p class="text-xl font-black" style="color: #1A1A1A;">{{ kpi.value }}</p>
                </div>
            </div>
        </div>

        <!-- Search + Add -->
        <div class="flex items-center gap-3 mb-4">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color: #AAAAAA;" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by name, email or tier…"
                    class="w-full pl-9 pr-4 py-2.5 rounded-lg text-sm outline-none"
                    style="border: 1px solid #DDDDDD; color: #1A1A1A; background: #FFF;"
                />
            </div>
            <button
                class="shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold cursor-pointer transition hover:opacity-90"
                style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
            >
                <UserPlus class="w-4 h-4" />
                <span class="hidden sm:inline">Add User</span>
            </button>
        </div>

        <!-- User list -->
        <div class="flex flex-col gap-2">
            <div
                v-for="user in filtered" :key="user.id"
                class="rounded-xl overflow-hidden bg-white transition-all duration-200"
                :style="savedId === user.id ? 'border: 1.5px solid #F5A000;' : 'border: 1px solid #DDDDDD;'"
            >
                <!-- Row -->
                <div
                    class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors"
                    @click.stop="toggleExpand(user.id)"
                >
                    <div
                        class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-xs font-black"
                        :style="{ backgroundColor: avatarColor(user.name) + '20', color: avatarColor(user.name) }"
                    >
                        {{ initials(user.name) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-bold text-sm truncate" style="color: #1A1A1A;">{{ user.name }}</span>
                            <span
                                class="shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold"
                                :style="{ backgroundColor: statusStyle(user.subscription?.status).bg, color: statusStyle(user.subscription?.status).color }"
                            >{{ statusStyle(user.subscription?.status).label }}</span>
                            <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold" style="background: #F5F5F5; color: #555555;">
                                {{ tierLabel(user.tier) }}
                            </span>
                            <span v-if="user.subscription" class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium" style="background: #FFF8E7; color: #B45309;">
                                {{ user.subscription.plan_label }}
                                <span v-if="user.subscription.billing_interval === 'yearly'" class="opacity-60">· yr</span>
                            </span>
                        </div>
                        <div class="flex items-center gap-1 mt-0.5">
                            <Mail class="w-3 h-3 shrink-0" style="color: #AAAAAA;" />
                            <span class="text-xs truncate" style="color: #555555;">{{ user.email }}</span>
                        </div>
                    </div>
                    <div class="shrink-0 flex items-center gap-1" @click.stop>
                        <span class="hidden md:inline text-xs mr-2" style="color: #AAAAAA;">joined {{ user.created_at }}</span>

                        <!-- Usage chips — desktop only -->
                        <template v-if="user.subscription">
                            <span
                                class="hidden md:flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold mr-0.5"
                                :style="storyChipStyle(user.subscription)"
                            >
                                <BookOpen class="w-3 h-3 shrink-0" />
                                {{ user.subscription.story_credits }}/{{ user.subscription.stories_per_month }}
                            </span>
                            <span class="hidden md:flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold mr-1" style="background:#F5F5F5; color:#555555;">
                                <Coins class="w-3 h-3 shrink-0" />
                                {{ user.subscription.refine_credits }}
                            </span>
                        </template>

                        <button class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 transition cursor-pointer" style="color: #AAAAAA;"><CircleUser class="w-4 h-4" /></button>
                        <button class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 transition cursor-pointer" style="color: #AAAAAA;"><KeyRound class="w-4 h-4" /></button>
                        <button class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-red-50 transition cursor-pointer" style="color: #AAAAAA;" @click="destroyUser(user)"><Trash2 class="w-4 h-4" /></button>
                        <ChevronRight class="w-4 h-4 ml-1 transition-transform duration-200" :class="{ 'rotate-90': expandedUser === user.id }" style="color: #AAAAAA;" />
                    </div>
                </div>

                <!-- Expanded panel -->
                <div
                    v-show="expandedUser === user.id"
                    class="border-t px-4 py-4 space-y-4"
                    style="border-color: #F0F0F0; background: #FAFAF8;"
                >
                    <!-- Assign plan -->
                    <div>
                        <p class="text-xs font-bold tracking-widest uppercase mb-2" style="color: #AAAAAA;">Assign Plan</p>
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="flex-1 min-w-40 relative">
                                <select
                                    v-model="getPlanForm(user).plan_id"
                                    class="w-full appearance-none pl-3 pr-7 py-2 rounded-lg text-sm cursor-pointer outline-none"
                                    style="border: 1px solid #DDDDDD; background: #FFF; color: #1A1A1A;"
                                >
                                    <option value="" disabled>— Select plan —</option>
                                    <option v-for="plan in plans.filter(p => p.is_active)" :key="plan.id" :value="plan.id">
                                        {{ plan.label }}
                                    </option>
                                </select>
                                <ChevronDown class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 pointer-events-none" style="color: #AAAAAA;" />
                            </div>

                            <div class="flex rounded-lg p-0.5 shrink-0" style="background: #F0F0F0;">
                                <button
                                    class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer"
                                    :style="getPlanForm(user).billing_interval === 'monthly'
                                        ? 'background:#FFF; color:#1A1A1A; box-shadow:0 1px 3px rgba(0,0,0,.1);'
                                        : 'color:#888;'"
                                    @click="getPlanForm(user).billing_interval = 'monthly'"
                                >Monthly</button>
                                <button
                                    class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer"
                                    :style="getPlanForm(user).billing_interval === 'yearly'
                                        ? 'background:#FFF; color:#1A1A1A; box-shadow:0 1px 3px rgba(0,0,0,.1);'
                                        : 'color:#888;'"
                                    @click="getPlanForm(user).billing_interval = 'yearly'"
                                >Yearly</button>
                            </div>

                            <span v-if="getPlanForm(user).plan_id" class="text-sm font-bold shrink-0" style="color: #F5A000;">
                                {{ planPrice(getPlanForm(user).plan_id, getPlanForm(user).billing_interval) }}
                            </span>

                            <button
                                :disabled="!getPlanForm(user).plan_id || getPlanForm(user).processing"
                                class="shrink-0 flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                                @click="savePlan(user)"
                            >
                                <Check class="w-3.5 h-3.5" /> Assign
                            </button>
                        </div>
                    </div>

                    <!-- Status + Role -->
                    <div class="flex flex-wrap gap-2 pt-3" style="border-top: 1px solid #EBEBEB;">
                        <div class="w-36">
                            <p class="text-xs font-bold tracking-widest uppercase mb-1.5" style="color: #AAAAAA;">Status</p>
                            <div class="relative">
                                <select
                                    v-model="getStatusForm(user).status"
                                    class="w-full appearance-none pl-3 pr-7 py-2 rounded-lg text-sm cursor-pointer outline-none"
                                    style="border: 1px solid #DDDDDD; background: #FFF; color: #1A1A1A;"
                                    @change="saveStatus(user)"
                                >
                                    <option value="active">Active</option>
                                    <option value="trialing">Trialing</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="expired">Expired</option>
                                </select>
                                <ChevronDown class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 pointer-events-none" style="color: #AAAAAA;" />
                            </div>
                        </div>
                        <div class="w-44">
                            <p class="text-xs font-bold tracking-widest uppercase mb-1.5" style="color: #AAAAAA;">Role</p>
                            <div class="relative">
                                <select
                                    v-model="getTierForm(user).tier"
                                    class="w-full appearance-none pl-3 pr-7 py-2 rounded-lg text-sm cursor-pointer outline-none"
                                    style="border: 1px solid #DDDDDD; background: #FFF; color: #1A1A1A;"
                                    @change="saveTier(user)"
                                >
                                    <option value="admin">Admin</option>
                                    <option value="partner">Verified Business Partner</option>
                                    <option value="subscriber">Subscriber</option>
                                    <option value="viewer">Viewer</option>
                                </select>
                                <ChevronDown class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 pointer-events-none" style="color: #AAAAAA;" />
                            </div>
                        </div>
                    </div>

                    <!-- Usage summary -->
                    <template v-if="user.subscription">
                        <div class="pt-3 space-y-2.5" style="border-top: 1px solid #EBEBEB;">
                            <p class="text-xs font-bold tracking-widest uppercase" style="color: #CCCCCC;">Usage This Period</p>

                            <!-- Stories progress bar -->
                            <div class="px-3 py-2.5 rounded-lg" style="background:#FFF; border:1px solid #EBEBEB;">
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <BookMarked class="w-3.5 h-3.5 shrink-0" style="color:#AAAAAA;" />
                                        <p class="text-xs font-semibold" style="color:#555555;">Stories this period</p>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs">
                                        <span style="color:#AAAAAA;">
                                            {{ user.subscription.stories_per_month - user.subscription.story_credits }} of {{ user.subscription.stories_per_month }} used
                                        </span>
                                        <span
                                            class="font-bold"
                                            :style="user.subscription.story_credits === 0 ? 'color:#DC2626;' : 'color:#F5A000;'"
                                        >{{ user.subscription.story_credits }} left</span>
                                    </div>
                                </div>
                                <div class="h-1.5 rounded-full overflow-hidden" style="background:#F0F0F0;">
                                    <div
                                        class="h-full rounded-full transition-all duration-300"
                                        :style="{
                                            width: storyBarWidth(user.subscription),
                                            background: user.subscription.story_credits === 0
                                                ? '#EF4444'
                                                : 'linear-gradient(to right, #FFC837, #F5A000)',
                                        }"
                                    />
                                </div>
                            </div>

                            <!-- Refine credits balance -->
                            <div class="flex items-center justify-between px-3 py-2.5 rounded-lg" style="background:#FFF; border:1px solid #EBEBEB;">
                                <div class="flex items-center gap-1.5">
                                    <Coins class="w-3.5 h-3.5 shrink-0" style="color:#AAAAAA;" />
                                    <p class="text-xs font-semibold" style="color:#555555;">Refine credits</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold leading-none" style="color:#1A1A1A;">{{ user.subscription.refine_credits }}</p>
                                    <p class="text-xs mt-0.5" style="color:#AAAAAA;">+{{ user.subscription.refine_monthly }} added each month</p>
                                </div>
                            </div>

                            <!-- Episode limit (static cap) -->
                            <div class="flex items-center justify-between px-3 py-2.5 rounded-lg" style="background:#FFF; border:1px solid #EBEBEB;">
                                <div class="flex items-center gap-1.5">
                                    <Layers class="w-3.5 h-3.5 shrink-0" style="color:#AAAAAA;" />
                                    <p class="text-xs font-semibold" style="color:#555555;">Episodes per story</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold leading-none" style="color:#1A1A1A;">Up to {{ user.subscription.effective_episode_limit }}</p>
                                    <p class="text-xs mt-0.5" style="color:#AAAAAA;">usage tracked when stories launch</p>
                                </div>
                            </div>

                            <p v-if="user.subscription.expires_at" class="text-xs" style="color:#AAAAAA;">
                                Expires {{ user.subscription.expires_at }}
                            </p>
                        </div>
                    </template>
                    <template v-else>
                        <p class="text-xs pt-3" style="border-top:1px solid #EBEBEB; color:#AAAAAA;">No active subscription — assign a plan above.</p>
                    </template>
                </div>
            </div>

            <div v-if="filtered.length === 0" class="py-16 text-center">
                <Users class="w-8 h-8 mx-auto mb-3" style="color: #DDDDDD;" />
                <p class="text-sm font-semibold" style="color: #1A1A1A;">No users found</p>
                <p class="text-xs mt-1" style="color: #AAAAAA;">Try a different search term</p>
            </div>
        </div>
    </AdminLayout>
</template>
