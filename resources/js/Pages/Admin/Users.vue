<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    Users, BookOpen, Activity, TrendingUp,
    Search, UserPlus, CircleUser, KeyRound, Trash2, Mail,
    ChevronDown, BookMarked, Coins, Layers, Check, Shield, LogIn,
    Receipt, ExternalLink,
} from '@lucide/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/Components/ui/dialog';
import {
    Tooltip, TooltipContent, TooltipTrigger,
} from '@/Components/ui/tooltip';

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

// ── Invoices ──────────────────────────────────────────────────────────────────
const invoicesCache    = ref({});
const invoicesLoading  = ref({});
const invoicesExpanded = ref({});

const fetchInvoices = async (userId) => {
    if (invoicesCache.value[userId] !== undefined) return;
    invoicesLoading.value[userId] = true;
    try {
        const res = await fetch(route('admin.users.invoices', userId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        invoicesCache.value[userId] = data;
    } catch {
        invoicesCache.value[userId] = { invoices: [], has_stripe: false };
    } finally {
        invoicesLoading.value[userId] = false;
    }
};

watch(expandedUser, (userId) => { if (userId) fetchInvoices(userId); });

// ── Delete dialog ─────────────────────────────────────────────────────────────

const deletingUser     = ref(null);
const deleteDialogOpen = computed({
    get: () => deletingUser.value !== null,
    set: (val) => { if (!val) deletingUser.value = null; },
});

const openDeleteDialog = (user) => { deletingUser.value = user; };
const confirmDelete    = () => {
    router.delete(route('admin.users.destroy', deletingUser.value.id), {
        onSuccess: () => { deletingUser.value = null; },
    });
};

// ── Derived ───────────────────────────────────────────────────────────────────

const filtered = computed(() =>
    props.users.filter(u =>
        u.name.toLowerCase().includes(search.value.toLowerCase()) ||
        u.email.toLowerCase().includes(search.value.toLowerCase()) ||
        u.tier.toLowerCase().includes(search.value.toLowerCase())
    )
);

const kpis = computed(() => [
    { label: 'Total Users', value: props.stats.users,                                                     icon: Users,      color: '#F5A000', bg: 'bg-amber-50',   text: 'text-amber-600',  tooltip: 'Total registered accounts'                          },
    { label: 'Active',      value: props.users.filter(u => u.subscription?.status === 'active').length,   icon: Activity,   color: '#22C55E', bg: 'bg-green-50',   text: 'text-green-600',  tooltip: 'Users with an active paid subscription'              },
    { label: 'Trialing',    value: props.users.filter(u => u.subscription?.status === 'trialing').length, icon: TrendingUp, color: '#F59E0B', bg: 'bg-yellow-50',  text: 'text-yellow-600', tooltip: 'Users currently in a free trial'                     },
    { label: 'Stories',     value: props.stats.stories,                                                   icon: BookOpen,   color: '#6366F1', bg: 'bg-indigo-50',  text: 'text-indigo-600', tooltip: 'Total stories generated across all users'            },
]);

// ── Helpers ───────────────────────────────────────────────────────────────────

const statusMeta = (status) => ({
    active:    { class: 'bg-green-100 text-green-700 border-green-200',    label: 'Active'    },
    trialing:  { class: 'bg-yellow-100 text-yellow-700 border-yellow-200', label: 'Trialing'  },
    cancelled: { class: 'bg-red-100 text-red-700 border-red-200',          label: 'Cancelled' },
    expired:   { class: 'bg-gray-100 text-gray-500 border-gray-200',       label: 'Expired'   },
})[status] ?? { class: 'bg-gray-100 text-gray-500 border-gray-200', label: 'No Plan' };

const tierMeta = (tier) => ({
    super_admin: { label: 'Super Admin', class: 'bg-red-100 text-red-700 border-red-200'       },
    admin:       { label: 'Admin',       class: 'bg-purple-100 text-purple-700 border-purple-200' },
    user:        { label: 'User',        class: 'bg-gray-100 text-gray-600 border-gray-200'       },
})[tier] ?? { label: tier, class: 'bg-gray-100 text-gray-500 border-gray-200' };

const toggleStatus = (user) => {
    router.post(route('admin.users.toggle-status', user.id), {}, { preserveScroll: true });
};

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

const storyBarWidth = (sub) => {
    if (!sub.stories_per_month) return '0%';
    const used = sub.stories_per_month - sub.story_credits;
    return (Math.min(used / sub.stories_per_month, 1) * 100) + '%';
};

const storyBarColor = (sub) => {
    if (sub.story_credits === 0) return '#EF4444';
    if (sub.story_credits * 2 <= sub.stories_per_month) return '#F59E0B';
    return 'url(#gold)';
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

const saveAll = (user) => {
    const hasPlan = !!getPlanForm(user).plan_id;
    let remaining = hasPlan ? 3 : 2;
    const done = () => { if (--remaining === 0) flash(user.id); };

    getStatusForm(user).patch(route('admin.users.subscription', user.id), { onSuccess: done, onError: done });
    getTierForm(user).patch(route('admin.users.update', user.id), { onSuccess: done, onError: done });
    if (hasPlan) {
        getPlanForm(user).post(route('admin.users.assign-plan', user.id), { onSuccess: done, onError: done });
    }
};

const toggleExpand = (id) => {
    expandedUser.value = expandedUser.value === id ? null : id;
};

// ── Modals ────────────────────────────────────────────────────────────────────

const userModalOpen     = ref(false);
const userModalMode     = ref('create');
const userModalUser     = ref(null);
const passwordModalOpen = ref(false);
const passwordModalUser = ref(null);

const userForm     = useForm({ name: '', email: '', tier: 'user' });
const passwordForm = useForm({});

const openCreate = () => {
    userForm.reset();
    userModalMode.value = 'create';
    userModalUser.value = null;
    userModalOpen.value = true;
};

const openEdit = (user) => {
    userForm.name  = user.name;
    userForm.email = user.email;
    userForm.tier  = user.tier;
    userForm.clearErrors();
    userModalMode.value = 'edit';
    userModalUser.value = user;
    userModalOpen.value = true;
};

const openPassword = (user) => {
    passwordForm.reset();
    passwordModalUser.value = user;
    passwordModalOpen.value = true;
};

const submitUser = () => {
    if (userModalMode.value === 'create') {
        userForm.post(route('admin.users.store'), { onSuccess: () => { userModalOpen.value = false; } });
    } else {
        userForm.patch(route('admin.users.profile', userModalUser.value.id), { onSuccess: () => { userModalOpen.value = false; } });
    }
};

const passwordResetSent = ref(false);

const submitPassword = () => {
    passwordForm.post(route('admin.users.password', passwordModalUser.value.id), {
        onSuccess: () => {
            passwordModalOpen.value = false;
            passwordResetSent.value = true;
            setTimeout(() => { passwordResetSent.value = false; }, 4000);
        },
    });
};

const impersonate = (userId) => {
    router.post(route('admin.users.impersonate', userId), {}, { replace: true });
};
</script>

<template>
    <AdminLayout>
        <Head title="Users — Admin" />

        <!-- Page header -->
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-lg font-black text-[#1A1A1A]">Users & Plans</h1>
                <p class="text-xs mt-0.5 text-muted-foreground">Manage accounts, roles, and subscriptions.</p>
            </div>
            <Button
                class="shrink-0 gap-2 font-semibold bg-gradient-to-r hover:bg-gradient-to-br from-[#FFC837] to-[#F5A000] text-[#1A1A1A] border-0 transition-all duration-300"
                @click="openCreate"
            >
                <UserPlus class="w-4 h-4" />
                <span class="hidden sm:inline">Add User</span>
            </Button>
        </div>

        <!-- KPI cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <Tooltip v-for="kpi in kpis" :key="kpi.label">
                <TooltipTrigger as-child>
                    <div class="bg-white rounded-2xl px-5 py-4 flex items-center gap-4 ring-1 ring-[#DDDDDD] cursor-default">
                        <div class="shrink-0 w-11 h-11 rounded-xl flex items-center justify-center" :class="kpi.bg">
                            <component :is="kpi.icon" class="w-5 h-5" :class="kpi.text" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-[#555555]">{{ kpi.label }}</p>
                            <p class="text-2xl font-black text-[#1A1A1A] leading-tight">{{ kpi.value }}</p>
                        </div>
                    </div>
                </TooltipTrigger>
                <TooltipContent>{{ kpi.tooltip }}</TooltipContent>
            </Tooltip>
        </div>

        <!-- Search bar -->
        <div class="relative mb-4">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
            <Input
                v-model="search"
                type="text"
                placeholder="Search by name, email or tier…"
                class="pl-9 bg-white"
            />
        </div>

        <!-- User cards -->
        <div class="flex flex-col gap-3">
            <div
                v-for="user in filtered" :key="user.id"
                class="bg-white rounded-2xl overflow-hidden transition-all duration-200"
                :class="savedId === user.id ? 'ring-2 ring-[#F5A000]' : 'ring-1 ring-[#DDDDDD]'"
            >
                <!-- Card row -->
                <div
                    class="flex items-center gap-4 px-5 py-4 cursor-pointer hover:bg-[#FAFAF8] transition-colors duration-150"
                    @click.stop="toggleExpand(user.id)"
                >
                    <!-- Avatar -->
                    <div
                        class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-xs font-black"
                        :style="{ backgroundColor: avatarColor(user.name) + '20', color: avatarColor(user.name) }"
                    >
                        {{ initials(user.name) }}
                    </div>

                    <!-- Identity -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-sm text-[#1A1A1A]">{{ user.name }}</span>
                            <Badge variant="outline" :class="statusMeta(user.subscription?.status).class" class="text-[10px] px-1.5 py-0">
                                {{ statusMeta(user.subscription?.status).label }}
                            </Badge>
                            <Badge variant="outline" :class="tierMeta(user.tier).class" class="text-[10px] px-1.5 py-0">
                                {{ tierMeta(user.tier).label }}
                            </Badge>
                            <Badge v-if="user.subscription" variant="outline" class="bg-amber-50 text-amber-700 border-amber-200 text-[10px] px-1.5 py-0">
                                {{ user.subscription.plan_label }}
                                <span v-if="user.subscription.billing_interval === 'yearly'" class="opacity-60 ml-0.5">· yr</span>
                            </Badge>
                        </div>
                        <div class="flex items-center gap-1 mt-1">
                            <Mail class="w-3 h-3 shrink-0 text-muted-foreground" />
                            <span class="text-xs text-muted-foreground truncate">{{ user.email }}</span>
                            <span class="hidden md:inline text-xs text-muted-foreground ml-2">· joined {{ user.created_at }}</span>
                        </div>
                    </div>

                    <!-- Usage chips + actions -->
                    <div class="shrink-0 flex items-center gap-1.5" @click.stop>
                        <template v-if="user.subscription">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <div class="hidden lg:flex items-center gap-1 px-2 py-1 rounded-lg bg-[#F8F8F8] text-xs font-semibold text-[#555555] cursor-default">
                                        <BookOpen class="w-3 h-3 shrink-0" />
                                        <span>{{ user.subscription.story_credits }}/{{ user.subscription.stories_per_month }}</span>
                                    </div>
                                </TooltipTrigger>
                                <TooltipContent>Story credits remaining this period</TooltipContent>
                            </Tooltip>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <div class="hidden lg:flex items-center gap-1 px-2 py-1 rounded-lg bg-[#F8F8F8] text-xs font-semibold text-[#555555] cursor-default">
                                        <Coins class="w-3 h-3 shrink-0" />
                                        <span>{{ user.subscription.refine_credits }}</span>
                                    </div>
                                </TooltipTrigger>
                                <TooltipContent>Refine credits remaining</TooltipContent>
                            </Tooltip>
                        </template>

                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    variant="ghost" size="sm"
                                    class="h-8 w-8 p-0 text-muted-foreground hover:text-[#1A1A1A] hover:bg-[#F0F0F0] cursor-pointer"
                                    @click.stop="openEdit(user)"
                                >
                                    <CircleUser class="w-4 h-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Edit profile</TooltipContent>
                        </Tooltip>

                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    variant="ghost" size="sm"
                                    class="h-8 w-8 p-0 text-muted-foreground hover:text-[#1A1A1A] hover:bg-[#F0F0F0] cursor-pointer"
                                    @click.stop="openPassword(user)"
                                >
                                    <KeyRound class="w-4 h-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Reset password</TooltipContent>
                        </Tooltip>

                        <template v-if="user.id !== $page.props.auth.user.id">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <button
                                        class="h-8 w-8 p-0 inline-flex items-center justify-center rounded-md text-muted-foreground hover:text-[#F5A000] hover:bg-amber-50 transition-colors cursor-pointer"
                                        @click.stop="impersonate(user.id)"
                                    >
                                        <LogIn class="w-4 h-4" />
                                    </button>
                                </TooltipTrigger>
                                <TooltipContent>Log in as this user</TooltipContent>
                            </Tooltip>
                        </template>

                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    variant="ghost" size="sm"
                                    class="h-8 w-8 p-0 text-muted-foreground hover:text-red-600 hover:bg-red-50 cursor-pointer"
                                    @click.stop="openDeleteDialog(user)"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Delete user</TooltipContent>
                        </Tooltip>

                        <div class="w-px h-5 bg-[#EBEBEB] mx-1" />

                        <button
                            class="h-8 w-8 p-0 inline-flex items-center justify-center rounded-md text-muted-foreground hover:text-[#1A1A1A] hover:bg-[#F0F0F0] transition-colors cursor-pointer"
                            @click.stop="toggleExpand(user.id)"
                        >
                            <ChevronDown
                                class="w-4 h-4 transition-transform duration-200"
                                :class="{ 'rotate-180': expandedUser === user.id }"
                            />
                        </button>
                    </div>
                </div>

                <!-- Expanded management panel -->
                <div
                    v-show="expandedUser === user.id"
                    class="border-t border-[#F0F0F0] bg-[#FAFAF8]"
                >
                    <!-- Unified form -->
                    <div class="px-5 py-5 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <!-- Plan -->
                            <div class="space-y-1.5">
                                <Label class="text-xs text-[#555555]">Plan</Label>
                                <Select
                                    :model-value="String(getPlanForm(user).plan_id)"
                                    @update:model-value="val => getPlanForm(user).plan_id = val"
                                >
                                    <SelectTrigger class="w-full bg-white">
                                        <SelectValue placeholder="— Select —" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="plan in plans.filter(p => p.is_active)"
                                            :key="plan.id"
                                            :value="String(plan.id)"
                                        >{{ plan.label }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Billing interval -->
                            <div class="space-y-1.5">
                                <Label class="text-xs text-[#555555]">
                                    Billing
                                    <span v-if="getPlanForm(user).plan_id" class="ml-1 font-bold text-[#F5A000]">
                                        · {{ planPrice(Number(getPlanForm(user).plan_id), getPlanForm(user).billing_interval) }}
                                    </span>
                                </Label>
                                <div class="flex rounded-lg p-0.5 h-9" style="background: #EBEBEB;">
                                    <button
                                        class="flex-1 rounded-md text-xs font-semibold transition-all cursor-pointer"
                                        :class="getPlanForm(user).billing_interval === 'monthly' ? 'bg-white text-[#1A1A1A] shadow-sm' : 'text-[#888888]'"
                                        @click="getPlanForm(user).billing_interval = 'monthly'"
                                    >Monthly</button>
                                    <button
                                        class="flex-1 rounded-md text-xs font-semibold transition-all cursor-pointer"
                                        :class="getPlanForm(user).billing_interval === 'yearly' ? 'bg-white text-[#1A1A1A] shadow-sm' : 'text-[#888888]'"
                                        @click="getPlanForm(user).billing_interval = 'yearly'"
                                    >Yearly</button>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="space-y-1.5">
                                <Label class="text-xs text-[#555555]">Subscription Status</Label>
                                <Select
                                    :model-value="getStatusForm(user).status"
                                    @update:model-value="val => getStatusForm(user).status = val"
                                >
                                    <SelectTrigger class="w-full bg-white">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="trialing">Trialing</SelectItem>
                                        <SelectItem value="cancelled">Cancelled</SelectItem>
                                        <SelectItem value="expired">Expired</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Role -->
                            <div class="space-y-1.5">
                                <Label class="text-xs text-[#555555]">Role</Label>
                                <Select
                                    :model-value="getTierForm(user).tier"
                                    @update:model-value="val => getTierForm(user).tier = val"
                                >
                                    <SelectTrigger class="w-full bg-white">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="super_admin">Super Admin</SelectItem>
                                        <SelectItem value="admin">Admin</SelectItem>
                                        <SelectItem value="user">User</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <!-- Save + Status toggle -->
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 text-xs font-semibold px-3 py-1.5 rounded-lg border transition-all cursor-pointer"
                                :class="user.is_active
                                    ? 'text-green-700 border-green-200 bg-green-50 hover:bg-red-50 hover:text-red-600 hover:border-red-200'
                                    : 'text-red-600 border-red-200 bg-red-50 hover:bg-green-50 hover:text-green-700 hover:border-green-200'"
                                @click.stop="toggleStatus(user)"
                            >
                                <span class="w-2 h-2 rounded-full" :class="user.is_active ? 'bg-green-500' : 'bg-red-400'" />
                                {{ user.is_active ? 'Active' : 'Inactive' }}
                            </button>
                            <Button
                                :disabled="getPlanForm(user).processing || getStatusForm(user).processing || getTierForm(user).processing"
                                class="gap-1.5 font-semibold bg-gradient-to-r hover:bg-gradient-to-br from-[#FFC837] to-[#F5A000] text-[#1A1A1A] border-0 transition-all duration-300 disabled:opacity-40"
                                @click="saveAll(user)"
                            >
                                <Check class="w-3.5 h-3.5" /> Save Changes
                            </Button>
                        </div>
                    </div>

                    <!-- Usage section -->
                    <template v-if="user.subscription">
                        <div class="border-t border-[#EBEBEB] px-5 py-4 space-y-3">
                            <p class="text-[10px] font-bold tracking-widest uppercase text-muted-foreground">Usage This Period</p>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <!-- Stories progress -->
                                <div class="col-span-1 bg-white rounded-xl p-3.5 ring-1 ring-[#EBEBEB]">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-1.5">
                                            <BookMarked class="w-3.5 h-3.5 text-muted-foreground" />
                                            <span class="text-xs font-semibold text-[#555555]">Stories</span>
                                        </div>
                                        <span v-if="user.tier === 'admin' || user.tier === 'super_admin'" class="text-xs font-bold text-purple-600">Unlimited</span>
                                        <span v-else class="text-xs font-bold" :class="user.subscription.story_credits === 0 ? 'text-red-500' : 'text-[#F5A000]'">
                                            {{ user.subscription.story_credits }} left
                                        </span>
                                    </div>
                                    <template v-if="user.tier !== 'admin' && user.tier !== 'super_admin'">
                                        <div class="h-1.5 rounded-full overflow-hidden bg-gray-100">
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
                                        <p class="text-[10px] text-muted-foreground mt-1.5">
                                            {{ user.subscription.stories_per_month - user.subscription.story_credits }} of {{ user.subscription.stories_per_month }} used
                                        </p>
                                    </template>
                                </div>

                                <!-- Refine credits -->
                                <div class="bg-white rounded-xl p-3.5 ring-1 ring-[#EBEBEB] flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                                        <Coins class="w-4 h-4 text-indigo-500" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-[#555555] font-semibold">Refine Credits</p>
                                        <p class="text-lg font-black text-[#1A1A1A] leading-tight">
                                            <span v-if="user.tier === 'admin' || user.tier === 'super_admin'" class="text-purple-600">∞</span>
                                            <span v-else>{{ user.subscription.refine_credits }}</span>
                                        </p>
                                        <p class="text-[10px] text-muted-foreground">+{{ user.subscription.refine_monthly }}/mo</p>
                                    </div>
                                </div>

                                <!-- Episode limit -->
                                <div class="bg-white rounded-xl p-3.5 ring-1 ring-[#EBEBEB] flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                                        <Layers class="w-4 h-4 text-amber-500" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-[#555555] font-semibold">Episodes / Story</p>
                                        <p class="text-lg font-black text-[#1A1A1A] leading-tight">
                                            <span v-if="user.tier === 'admin' || user.tier === 'super_admin'" class="text-purple-600">∞</span>
                                            <span v-else>{{ user.subscription.effective_episode_limit }}</span>
                                        </p>
                                        <p class="text-[10px] text-muted-foreground">max per story</p>
                                    </div>
                                </div>

                                <!-- Total stories generated -->
                                <Link
                                    :href="route('admin.stories.index', { user_id: user.id })"
                                    class="bg-white rounded-xl p-3.5 ring-1 ring-[#EBEBEB] flex items-center gap-3 transition hover:ring-[#F5A000] hover:bg-[#FFFBF0]"
                                >
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                                        <BookOpen class="w-4 h-4 text-emerald-500" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-[#555555] font-semibold">Stories Made</p>
                                        <p class="text-lg font-black text-[#1A1A1A] leading-tight">{{ user.stories_total }}</p>
                                        <p class="text-[10px] text-muted-foreground">all time</p>
                                    </div>
                                </Link>
                            </div>

                            <div class="flex items-center gap-4 text-xs text-muted-foreground flex-wrap">
                                <span v-if="user.subscription.starts_at">Started {{ user.subscription.starts_at }}</span>
                                <span v-if="user.subscription.expires_at">Expires {{ user.subscription.expires_at }}</span>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div class="border-t border-[#EBEBEB] px-5 py-3">
                            <p class="text-xs text-muted-foreground">No active subscription — assign a plan above.</p>
                        </div>
                    </template>

                    <!-- Billing / Invoices -->
                    <div class="border-t border-[#EBEBEB] px-5 py-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-[10px] font-bold tracking-widest uppercase text-muted-foreground">Billing / Invoices</p>
                        </div>

                        <!-- Loading -->
                        <div v-if="invoicesLoading[user.id]" class="flex items-center gap-2 py-2">
                            <div class="w-3 h-3 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:0ms" />
                            <div class="w-3 h-3 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:150ms" />
                            <div class="w-3 h-3 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:300ms" />
                        </div>

                        <!-- No Stripe -->
                        <template v-else-if="invoicesCache[user.id] && !invoicesCache[user.id].has_stripe">
                            <p class="text-xs text-muted-foreground">No Stripe account — this user was assigned a plan manually.</p>
                        </template>

                        <!-- Invoices -->
                        <template v-else-if="invoicesCache[user.id]">
                            <template v-if="invoicesCache[user.id].invoices.length === 0">
                                <p class="text-xs text-muted-foreground">No invoices found.</p>
                            </template>
                            <template v-else>
                                <div class="space-y-1.5">
                                    <div
                                        v-for="inv in (invoicesExpanded[user.id] ? invoicesCache[user.id].invoices : invoicesCache[user.id].invoices.slice(0, 3))"
                                        :key="inv.id"
                                        class="flex items-center justify-between px-3 py-2.5 rounded-lg"
                                        style="background-color: #F8F8F8;"
                                    >
                                        <div class="flex items-center gap-2.5">
                                            <Receipt class="w-3.5 h-3.5 text-muted-foreground shrink-0" />
                                            <div>
                                                <p class="text-xs font-semibold text-[#1A1A1A]">{{ inv.number }}</p>
                                                <p class="text-[10px] text-muted-foreground">{{ inv.date }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="text-[10px] font-bold px-1.5 py-0.5 rounded capitalize"
                                                :class="inv.status === 'paid' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'"
                                            >{{ inv.status }}</span>
                                            <span class="text-xs font-bold text-[#1A1A1A]">{{ inv.total }}</span>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    v-if="invoicesCache[user.id].invoices.length > 3"
                                    class="mt-2.5 text-xs font-semibold transition hover:opacity-70 flex items-center gap-1"
                                    style="color: #F5A000;"
                                    @click="invoicesExpanded[user.id] = !invoicesExpanded[user.id]"
                                >
                                    <ExternalLink class="w-3 h-3" />
                                    {{ invoicesExpanded[user.id] ? 'Show less' : `View all ${invoicesCache[user.id].invoices.length} invoices` }}
                                </button>
                            </template>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="filtered.length === 0" class="py-20 text-center bg-white rounded-2xl ring-1 ring-[#DDDDDD]">
                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                    <Users class="w-6 h-6 text-muted-foreground" />
                </div>
                <p class="text-sm font-semibold text-[#1A1A1A]">No users found</p>
                <p class="text-xs mt-1 text-muted-foreground">Try a different search term</p>
            </div>
        </div>

        <!-- Delete user dialog -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="max-w-sm" v-if="deletingUser">
                <DialogHeader>
                    <DialogTitle>Delete User</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to permanently delete
                        <span class="font-semibold text-foreground">{{ deletingUser.name }}</span>?
                        This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deletingUser = null">Cancel</Button>
                    <Button variant="destructive" @click="confirmDelete">Delete User</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Create / Edit user dialog -->
        <Dialog v-model:open="userModalOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ userModalMode === 'create' ? 'Add User' : 'Edit Profile' }}</DialogTitle>
                    <DialogDescription>
                        {{ userModalMode === 'create' ? 'Create a new user account.' : `Editing ${userModalUser?.name}.` }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="space-y-1.5">
                        <Label for="user-name">Name</Label>
                        <Input id="user-name" v-model="userForm.name" placeholder="Full name" />
                        <p v-if="userForm.errors.name" class="text-xs text-destructive">{{ userForm.errors.name }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="user-email">Email</Label>
                        <Input id="user-email" v-model="userForm.email" type="email" placeholder="email@example.com" />
                        <p v-if="userForm.errors.email" class="text-xs text-destructive">{{ userForm.errors.email }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Role</Label>
                        <Select v-model="userForm.tier">
                            <SelectTrigger class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="super_admin">Super Admin</SelectItem>
                                <SelectItem value="admin">Admin</SelectItem>
                                <SelectItem value="user">User</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="userForm.errors.tier" class="text-xs text-destructive">{{ userForm.errors.tier }}</p>
                    </div>

                    <p v-if="userModalMode === 'create'" class="text-xs" style="color: #888888;">A password reset link will be emailed to the user automatically.</p>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="userModalOpen = false">Cancel</Button>
                    <Button
                        :disabled="userForm.processing"
                        class="bg-gradient-to-r hover:bg-gradient-to-br from-[#FFC837] to-[#F5A000] text-[#1A1A1A] border-0 font-semibold transition-all duration-300"
                        @click="submitUser"
                    >
                        {{ userModalMode === 'create' ? 'Create User' : 'Save Changes' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Reset password dialog -->
        <Dialog v-model:open="passwordModalOpen">
            <DialogContent class="max-w-sm">
                <DialogHeader>
                    <DialogTitle>Send Reset Link</DialogTitle>
                    <DialogDescription>
                        A password reset link will be sent to
                        <span class="font-semibold text-foreground">{{ passwordModalUser?.email }}</span>.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button variant="outline" @click="passwordModalOpen = false">Cancel</Button>
                    <Button
                        :disabled="passwordForm.processing"
                        class="bg-gradient-to-r hover:bg-gradient-to-br from-[#FFC837] to-[#F5A000] text-[#1A1A1A] border-0 font-semibold transition-all duration-300"
                        @click="submitPassword"
                    >
                        Send Reset Link
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Success alert -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-4 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-4 opacity-0"
        >
            <div
                v-if="passwordResetSent"
                class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2.5 px-5 py-3 rounded-xl bg-[#1A1A1A] text-white text-sm font-semibold shadow-xl"
            >
                <Check class="w-4 h-4 text-[#F5A623] shrink-0" />
                Reset link sent successfully.
            </div>
        </Transition>
    </AdminLayout>
</template>
