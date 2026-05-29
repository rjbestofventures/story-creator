<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Plus, Pencil, Layers, BookOpen, Coins, Tag, Trash2 } from '@lucide/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Switch } from '@/Components/ui/switch';
import { Separator } from '@/Components/ui/separator';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/Components/ui/dialog';
import {
    Tooltip, TooltipContent, TooltipTrigger,
} from '@/Components/ui/tooltip';

const props = defineProps({ plans: Array });

const savedId = ref(null);
const flash   = (id) => { savedId.value = id; setTimeout(() => savedId.value = null, 1800); };

// ── Edit existing plans ────────────────────────────────────────────────────────

const editForms   = ref({});
const editingPlan = ref(null);

const getEditForm = (plan) => {
    if (!editForms.value[plan.id]) {
        editForms.value[plan.id] = useForm({
            label:                 plan.label,
            episode_limit:         plan.episode_limit,
            stories_per_month:     plan.stories_per_month,
            refine_monthly:        plan.refine_monthly,
            price_monthly:         plan.price_monthly,
            price_yearly:          plan.price_yearly,
            trial_months:          plan.trial_months,
            is_active:             plan.is_active,
            stripe_price_monthly:  plan.stripe_price_monthly ?? '',
            stripe_price_yearly:   plan.stripe_price_yearly  ?? '',
        });
    }
    return editForms.value[plan.id];
};

const openEdit = (plan) => {
    getEditForm(plan);
    editingPlan.value = plan;
};

const savePlan = () => {
    const plan = editingPlan.value;
    getEditForm(plan).patch(route('admin.plans.update', plan.id), {
        onSuccess: () => { flash('plan-' + plan.id); editingPlan.value = null; },
    });
};

const quickToggle = (plan) => {
    const form = getEditForm(plan);
    form.is_active = !form.is_active;
    form.patch(route('admin.plans.update', plan.id), {
        onSuccess: () => flash('plan-' + plan.id),
    });
};

// ── Create new plan ────────────────────────────────────────────────────────────

const createDialogOpen = ref(false);

const newForm = useForm({
    label:             '',
    episode_limit:     12,
    stories_per_month: 2,
    refine_monthly:    12,
    price_monthly:     0,
    price_yearly:      0,
    trial_months:      0,
});

const createPlan = () => {
    newForm.post(route('admin.plans.store'), {
        onSuccess: () => { createDialogOpen.value = false; newForm.reset(); },
    });
};

// ── Delete plan ───────────────────────────────────────────────────────────────

const deletingPlan  = ref(null);
const deleteError   = ref(null);
const deleteDialogOpen = computed({
    get: () => deletingPlan.value !== null,
    set: (val) => { if (!val) { deletingPlan.value = null; deleteError.value = null; } },
});

const openDeleteDialog = (plan) => { deletingPlan.value = plan; deleteError.value = null; };

const confirmDelete = () => {
    router.delete(route('admin.plans.destroy', deletingPlan.value.id), {
        onError: (errors) => { deleteError.value = errors.plan; },
        onSuccess: () => { deletingPlan.value = null; editingPlan.value = null; },
    });
};

// ── Display helpers ────────────────────────────────────────────────────────────

const isFree    = (plan) => plan.price_monthly === 0 && plan.price_yearly === 0;
const hasTrial  = (plan) => plan.trial_months > 0;

const editDialogOpen = computed({
    get: () => editingPlan.value !== null,
    set: (val) => { if (!val) editingPlan.value = null; },
});
</script>

<template>
    <AdminLayout>
        <Head title="Plans — Admin" />

        <!-- Page header -->
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-lg font-black" style="color: #1A1A1A;">Subscription Plans</h1>
                <p class="text-xs mt-0.5 text-muted-foreground">Changes apply to new subscriptions only. Deactivating hides a plan from sign-up.</p>
            </div>
            <Button
                class="shrink-0 gap-2 font-semibold bg-gradient-to-r from-[#FFC837] to-[#F5A000] text-[#1A1A1A] border-0 hover:opacity-90 hover:bg-none"
                @click="createDialogOpen = true"
            >
                <Plus class="w-4 h-4" /> New Plan
            </Button>
        </div>

        <!-- Plan cards grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="plan in plans" :key="plan.id"
                class="bg-white rounded-2xl flex flex-col transition-all duration-300"
                :class="[
                    savedId === 'plan-' + plan.id
                        ? 'ring-2 ring-[#F5A000] ring-offset-1'
                        : 'ring-1 ring-[#DDDDDD]',
                    !getEditForm(plan).is_active && 'opacity-60',
                ]"
            >
                <!-- Card top: name + status -->
                <div class="px-5 pt-5 pb-4">
                    <div class="flex items-start justify-between gap-2 mb-4">
                        <div>
                            <p class="text-base font-black leading-tight" style="color: #1A1A1A;">
                                {{ getEditForm(plan).label }}
                            </p>
                            <p v-if="hasTrial(plan)" class="text-xs mt-0.5 font-semibold text-amber-600">
                                {{ plan.trial_months }}-month trial
                            </p>
                        </div>
                        <Badge
                            variant="outline"
                            :class="getEditForm(plan).is_active
                                ? 'bg-green-50 text-green-700 border-green-200 shrink-0'
                                : 'bg-gray-100 text-gray-400 border-gray-200 shrink-0'"
                        >
                            {{ getEditForm(plan).is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>

                    <!-- Pricing -->
                    <div class="mb-5">
                        <template v-if="isFree(plan)">
                            <p class="text-2xl font-black" style="color: #1A1A1A;">Free</p>
                            <p class="text-xs text-muted-foreground mt-0.5">No charge</p>
                        </template>
                        <template v-else>
                            <p class="text-2xl font-black" style="color: #1A1A1A;">
                                ${{ plan.price_monthly }}
                                <span class="text-sm font-medium text-muted-foreground">/ mo</span>
                            </p>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                ${{ plan.price_yearly }} / yr
                                <span v-if="plan.price_yearly > 0" class="ml-1 text-green-600 font-semibold">
                                    (save {{ Math.round((1 - plan.price_yearly / (plan.price_monthly * 12)) * 100) }}%)
                                </span>
                            </p>
                        </template>
                    </div>

                    <!-- Stats row -->
                    <div class="grid grid-cols-3 gap-2">
                        <div class="flex flex-col items-center gap-1 rounded-xl py-2.5 px-1" style="background: #F8F8F8;">
                            <Layers class="w-3.5 h-3.5 text-muted-foreground" />
                            <p class="text-base font-black leading-none" style="color: #1A1A1A;">{{ plan.episode_limit }}</p>
                            <p class="text-[10px] font-medium text-muted-foreground leading-none">Episodes</p>
                        </div>
                        <div class="flex flex-col items-center gap-1 rounded-xl py-2.5 px-1" style="background: #F8F8F8;">
                            <BookOpen class="w-3.5 h-3.5 text-muted-foreground" />
                            <p class="text-base font-black leading-none" style="color: #1A1A1A;">{{ plan.stories_per_month }}</p>
                            <p class="text-[10px] font-medium text-muted-foreground leading-none">Stories/mo</p>
                        </div>
                        <div class="flex flex-col items-center gap-1 rounded-xl py-2.5 px-1" style="background: #F8F8F8;">
                            <Coins class="w-3.5 h-3.5 text-muted-foreground" />
                            <p class="text-base font-black leading-none" style="color: #1A1A1A;">{{ plan.refine_monthly }}</p>
                            <p class="text-[10px] font-medium text-muted-foreground leading-none">Refine/mo</p>
                        </div>
                    </div>
                </div>

                <!-- Card footer -->
                <div class="mt-auto px-5 py-3 flex items-center justify-between border-t border-[#F0F0F0]">
                    <div class="flex items-center gap-2">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Switch
                                    :model-value="getEditForm(plan).is_active"
                                    size="sm"
                                    :class="getEditForm(plan).is_active ? '!bg-green-500' : '!bg-gray-300'"
                                    @update:model-value="quickToggle(plan)"
                                />
                            </TooltipTrigger>
                            <TooltipContent>{{ getEditForm(plan).is_active ? 'Deactivate plan' : 'Activate plan' }}</TooltipContent>
                        </Tooltip>
                        <span class="text-xs font-medium text-muted-foreground">
                            {{ getEditForm(plan).is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button variant="ghost" size="sm" class="gap-1 text-destructive hover:text-destructive hover:bg-destructive/10 cursor-pointer" @click="openDeleteDialog(plan)">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Delete plan</TooltipContent>
                        </Tooltip>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button variant="ghost" size="sm" class="cursor-pointer" @click="openEdit(plan)">
                                    <Pencil class="w-3.5 h-3.5" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Edit plan</TooltipContent>
                        </Tooltip>
                    </div>
                </div>
            </div>

            <!-- "Add Plan" phantom card -->
            <button
                class="hidden lg:flex flex-col items-center justify-center gap-2 bg-white rounded-2xl ring-1 ring-dashed ring-[#DDDDDD] min-h-48 cursor-pointer transition hover:ring-[#F5A000] hover:bg-[#FFFDF5] group"
                @click="createDialogOpen = true"
            >
                <div class="w-9 h-9 rounded-full flex items-center justify-center transition group-hover:bg-[#F5A000]" style="background: #F0F0F0;">
                    <Plus class="w-4 h-4 text-muted-foreground group-hover:text-[#1A1A1A] transition" />
                </div>
                <p class="text-sm font-semibold text-muted-foreground group-hover:text-[#1A1A1A] transition">New Plan</p>
            </button>
        </div>

        <!-- Edit plan dialog -->
        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="max-w-lg" v-if="editingPlan">
                <DialogHeader>
                    <DialogTitle>Edit Plan</DialogTitle>
                    <DialogDescription>Changes apply to new subscriptions only.</DialogDescription>
                </DialogHeader>

                <div class="space-y-5">
                    <!-- Name -->
                    <div class="space-y-1.5">
                        <Label>Plan Name</Label>
                        <Input v-model="getEditForm(editingPlan).label" placeholder="e.g. Basic" />
                        <p v-if="getEditForm(editingPlan).errors.label" class="text-xs text-destructive">{{ getEditForm(editingPlan).errors.label }}</p>
                    </div>

                    <Separator />

                    <!-- Limits -->
                    <div>
                        <p class="text-xs font-bold tracking-widest uppercase text-muted-foreground mb-3">Limits</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="space-y-1.5">
                                <Label class="text-xs">Episodes / story</Label>
                                <Input v-model.number="getEditForm(editingPlan).episode_limit" type="number" min="1" class="text-center" />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Stories / mo</Label>
                                <Input v-model.number="getEditForm(editingPlan).stories_per_month" type="number" min="0" class="text-center" />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Refine / mo</Label>
                                <Input v-model.number="getEditForm(editingPlan).refine_monthly" type="number" min="0" class="text-center" />
                            </div>
                        </div>
                    </div>

                    <Separator />

                    <!-- Pricing -->
                    <div>
                        <p class="text-xs font-bold tracking-widest uppercase text-muted-foreground mb-3">Pricing</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="space-y-1.5">
                                <Label class="text-xs">Monthly ($)</Label>
                                <Input v-model.number="getEditForm(editingPlan).price_monthly" type="number" min="0" class="text-center" />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Yearly ($)</Label>
                                <Input v-model.number="getEditForm(editingPlan).price_yearly" type="number" min="0" class="text-center" />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Trial months</Label>
                                <Input v-model.number="getEditForm(editingPlan).trial_months" type="number" min="0" class="text-center" />
                            </div>
                        </div>
                    </div>

                    <Separator />

                    <!-- Stripe Price IDs -->
                    <div class="space-y-3">
                        <p class="text-sm font-semibold" style="color: #1A1A1A;">Stripe Price IDs</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-muted-foreground mb-1">Monthly (price_...)</p>
                                <Input v-model="getEditForm(editingPlan).stripe_price_monthly" placeholder="price_xxx" class="font-mono text-xs" />
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground mb-1">Yearly (price_...)</p>
                                <Input v-model="getEditForm(editingPlan).stripe_price_yearly" placeholder="price_xxx" class="font-mono text-xs" />
                            </div>
                        </div>
                    </div>

                    <Separator />

                    <!-- Active toggle -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold" style="color: #1A1A1A;">Active</p>
                            <p class="text-xs text-muted-foreground">Visible on the sign-up page</p>
                        </div>
                        <Switch v-model="getEditForm(editingPlan).is_active" :class="getEditForm(editingPlan).is_active ? '!bg-green-500' : '!bg-gray-300'" />
                    </div>
                </div>

                <DialogFooter class="flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Button variant="ghost" size="sm" class="gap-1.5 text-destructive hover:text-destructive hover:bg-destructive/10 order-last sm:order-first" @click="openDeleteDialog(editingPlan)">
                        <Trash2 class="w-3.5 h-3.5" /> Delete
                    </Button>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="editingPlan = null">Cancel</Button>
                        <Button
                            :disabled="!getEditForm(editingPlan).isDirty || getEditForm(editingPlan).processing"
                            class="bg-gradient-to-r from-[#FFC837] to-[#F5A000] text-[#1A1A1A] border-0 hover:opacity-90 hover:bg-none font-semibold disabled:opacity-40"
                            @click="savePlan"
                        >Save Changes</Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Create plan dialog -->
        <Dialog v-model:open="createDialogOpen">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>New Plan</DialogTitle>
                    <DialogDescription>The new plan will be active and visible on sign-up immediately.</DialogDescription>
                </DialogHeader>

                <div class="space-y-5">
                    <div class="space-y-1.5">
                        <Label>Plan Name</Label>
                        <Input v-model="newForm.label" placeholder="e.g. Enterprise" />
                        <p v-if="newForm.errors.label" class="text-xs text-destructive">{{ newForm.errors.label }}</p>
                    </div>

                    <Separator />

                    <div>
                        <p class="text-xs font-bold tracking-widest uppercase text-muted-foreground mb-3">Limits</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="space-y-1.5">
                                <Label class="text-xs">Episodes / story</Label>
                                <Input v-model.number="newForm.episode_limit" type="number" min="1" class="text-center" />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Stories / mo</Label>
                                <Input v-model.number="newForm.stories_per_month" type="number" min="0" class="text-center" />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Refine / mo</Label>
                                <Input v-model.number="newForm.refine_monthly" type="number" min="0" class="text-center" />
                            </div>
                        </div>
                    </div>

                    <Separator />

                    <div>
                        <p class="text-xs font-bold tracking-widest uppercase text-muted-foreground mb-3">Pricing</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="space-y-1.5">
                                <Label class="text-xs">Monthly ($)</Label>
                                <Input v-model.number="newForm.price_monthly" type="number" min="0" class="text-center" />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Yearly ($)</Label>
                                <Input v-model.number="newForm.price_yearly" type="number" min="0" class="text-center" />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Trial months</Label>
                                <Input v-model.number="newForm.trial_months" type="number" min="0" class="text-center" />
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="createDialogOpen = false">Cancel</Button>
                    <Button
                        :disabled="!newForm.label || newForm.processing"
                        class="bg-gradient-to-r from-[#FFC837] to-[#F5A000] text-[#1A1A1A] border-0 hover:opacity-90 hover:bg-none font-semibold disabled:opacity-40"
                        @click="createPlan"
                    >Create Plan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="max-w-sm" v-if="deletingPlan">
                <DialogHeader>
                    <DialogTitle>Delete Plan</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to permanently delete <span class="font-semibold text-foreground">{{ deletingPlan.label }}</span>? This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <p v-if="deleteError" class="text-sm text-destructive">{{ deleteError }}</p>
                <DialogFooter>
                    <Button variant="outline" @click="deletingPlan = null">Cancel</Button>
                    <Button variant="destructive" @click="confirmDelete">Delete Plan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
