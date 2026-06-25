<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Plus, Pencil, Layers, RefreshCcw, Trash2, AlertTriangle } from '@lucide/vue';
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

const props = defineProps({ packs: Array });

const savedId = ref(null);
const flash   = (id) => { savedId.value = id; setTimeout(() => savedId.value = null, 1800); };

// ── Edit existing packs ────────────────────────────────────────────────────────

const editForms   = ref({});
const editingPack = ref(null);

const getEditForm = (pack) => {
    if (!editForms.value[pack.id]) {
        editForms.value[pack.id] = useForm({
            label:            pack.label,
            episode_limit:    pack.episode_limit,
            revision_credits: pack.revision_credits,
            price_dollars:    pack.price_dollars,
            stripe_price_id:  pack.stripe_price_id ?? '',
            is_active:        pack.is_active,
        });
    }
    return editForms.value[pack.id];
};

const openEdit = (pack) => {
    getEditForm(pack);
    editingPack.value = pack;
};

const savePack = () => {
    const pack = editingPack.value;
    getEditForm(pack).patch(route('admin.packs.update', pack.id), {
        onSuccess: () => { flash('pack-' + pack.id); editingPack.value = null; },
    });
};

const quickToggle = (pack) => {
    const form = getEditForm(pack);
    form.is_active = !form.is_active;
    form.patch(route('admin.packs.update', pack.id), {
        onSuccess: () => flash('pack-' + pack.id),
    });
};

// ── Create new pack ────────────────────────────────────────────────────────────

const createDialogOpen = ref(false);

const newForm = useForm({
    label:            '',
    episode_limit:    12,
    revision_credits: 36,
    price_dollars:    9,
    stripe_price_id:  '',
});

const createPack = () => {
    newForm.post(route('admin.packs.store'), {
        onSuccess: () => { createDialogOpen.value = false; newForm.reset(); },
    });
};

// ── Delete pack ───────────────────────────────────────────────────────────────

const deletingPack     = ref(null);
const deleteError      = ref(null);
const deleteDialogOpen = computed({
    get: () => deletingPack.value !== null,
    set: (val) => { if (!val) { deletingPack.value = null; deleteError.value = null; } },
});

const openDeleteDialog = (pack) => { deletingPack.value = pack; deleteError.value = null; };

const confirmDelete = () => {
    router.delete(route('admin.packs.destroy', deletingPack.value.id), {
        onError: (errors) => { deleteError.value = errors.pack; },
        onSuccess: () => { deletingPack.value = null; editingPack.value = null; },
    });
};

// ── Display helpers ────────────────────────────────────────────────────────────

const needsStripe = (form) => form.is_active && !form.stripe_price_id;

const editDialogOpen = computed({
    get: () => editingPack.value !== null,
    set: (val) => { if (!val) editingPack.value = null; },
});
</script>

<template>
    <AdminLayout>
        <Head title="Credit Packs — Admin" />

        <!-- Page header -->
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-lg font-black" style="color: #1A1A1A;">Credit Packs</h1>
                <p class="text-xs mt-0.5 text-muted-foreground">One-time story packs. Changes apply to new purchases only. Deactivating hides a pack from the shop.</p>
            </div>
            <Button
                class="shrink-0 gap-2 font-semibold bg-gradient-to-r from-[#FFC837] to-[#F5A000] text-[#1A1A1A] border-0 hover:opacity-90 hover:bg-none"
                @click="createDialogOpen = true"
            >
                <Plus class="w-4 h-4" /> New Pack
            </Button>
        </div>

        <!-- Pack cards grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="pack in packs" :key="pack.id"
                class="bg-white rounded-2xl flex flex-col transition-all duration-300"
                :class="[
                    savedId === 'pack-' + pack.id
                        ? 'ring-2 ring-[#F5A000] ring-offset-1'
                        : 'ring-1 ring-[#DDDDDD]',
                    !getEditForm(pack).is_active && 'opacity-60',
                ]"
            >
                <!-- Card top: name + status -->
                <div class="px-5 pt-5 pb-4">
                    <div class="flex items-start justify-between gap-2 mb-4">
                        <p class="text-base font-black leading-tight" style="color: #1A1A1A;">
                            {{ getEditForm(pack).label }}
                        </p>
                        <Badge
                            variant="outline"
                            :class="getEditForm(pack).is_active
                                ? 'bg-green-50 text-green-700 border-green-200 shrink-0'
                                : 'bg-gray-100 text-gray-400 border-gray-200 shrink-0'"
                        >
                            {{ getEditForm(pack).is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>

                    <!-- Pricing -->
                    <div class="mb-5">
                        <p class="text-2xl font-black" style="color: #1A1A1A;">
                            ${{ pack.price_dollars }}
                            <span class="text-sm font-medium text-muted-foreground">one-time</span>
                        </p>
                        <p class="text-xs text-muted-foreground mt-0.5">1 story · never expires</p>
                    </div>

                    <!-- Stripe warning -->
                    <div
                        v-if="needsStripe(getEditForm(pack))"
                        class="flex items-start gap-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 mb-4"
                    >
                        <AlertTriangle class="w-3.5 h-3.5 text-amber-600 shrink-0 mt-0.5" />
                        <p class="text-[11px] leading-tight font-medium text-amber-700">No Stripe price — purchases will fail.</p>
                    </div>

                    <!-- Stats row -->
                    <div class="grid grid-cols-2 gap-2">
                        <div class="flex flex-col items-center gap-1 rounded-xl py-2.5 px-1" style="background: #F8F8F8;">
                            <Layers class="w-3.5 h-3.5 text-muted-foreground" />
                            <p class="text-base font-black leading-none" style="color: #1A1A1A;">{{ pack.episode_limit }}</p>
                            <p class="text-[10px] font-medium text-muted-foreground leading-none">Episodes</p>
                        </div>
                        <div class="flex flex-col items-center gap-1 rounded-xl py-2.5 px-1" style="background: #F8F8F8;">
                            <RefreshCcw class="w-3.5 h-3.5 text-muted-foreground" />
                            <p class="text-base font-black leading-none" style="color: #1A1A1A;">{{ pack.revision_credits }}</p>
                            <p class="text-[10px] font-medium text-muted-foreground leading-none">Revisions</p>
                        </div>
                    </div>
                </div>

                <!-- Card footer -->
                <div class="mt-auto px-5 py-3 flex items-center justify-between border-t border-[#F0F0F0]">
                    <div class="flex items-center gap-2">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Switch
                                    :model-value="getEditForm(pack).is_active"
                                    size="sm"
                                    :class="getEditForm(pack).is_active ? '!bg-green-500' : '!bg-gray-300'"
                                    @update:model-value="quickToggle(pack)"
                                />
                            </TooltipTrigger>
                            <TooltipContent>{{ getEditForm(pack).is_active ? 'Deactivate pack' : 'Activate pack' }}</TooltipContent>
                        </Tooltip>
                        <span class="text-xs font-medium text-muted-foreground">
                            {{ getEditForm(pack).is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button variant="ghost" size="sm" class="gap-1 text-destructive hover:text-destructive hover:bg-destructive/10 cursor-pointer" @click="openDeleteDialog(pack)">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Delete pack</TooltipContent>
                        </Tooltip>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button variant="ghost" size="sm" class="cursor-pointer" @click="openEdit(pack)">
                                    <Pencil class="w-3.5 h-3.5" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Edit pack</TooltipContent>
                        </Tooltip>
                    </div>
                </div>
            </div>

            <!-- "Add Pack" phantom card -->
            <button
                class="hidden lg:flex flex-col items-center justify-center gap-2 bg-white rounded-2xl ring-1 ring-dashed ring-[#DDDDDD] min-h-48 cursor-pointer transition hover:ring-[#F5A000] hover:bg-[#FFFDF5] group"
                @click="createDialogOpen = true"
            >
                <div class="w-9 h-9 rounded-full flex items-center justify-center transition group-hover:bg-[#F5A000]" style="background: #F0F0F0;">
                    <Plus class="w-4 h-4 text-muted-foreground group-hover:text-[#1A1A1A] transition" />
                </div>
                <p class="text-sm font-semibold text-muted-foreground group-hover:text-[#1A1A1A] transition">New Pack</p>
            </button>
        </div>

        <!-- Edit pack dialog -->
        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="max-w-lg" v-if="editingPack">
                <DialogHeader>
                    <DialogTitle>Edit Pack</DialogTitle>
                    <DialogDescription>Changes apply to new purchases only.</DialogDescription>
                </DialogHeader>

                <div class="space-y-5">
                    <!-- Name -->
                    <div class="space-y-1.5">
                        <Label>Pack Name</Label>
                        <Input v-model="getEditForm(editingPack).label" placeholder="e.g. Basic" />
                        <p v-if="getEditForm(editingPack).errors.label" class="text-xs text-destructive">{{ getEditForm(editingPack).errors.label }}</p>
                    </div>

                    <Separator />

                    <!-- Limits -->
                    <div>
                        <p class="text-xs font-bold tracking-widest uppercase text-muted-foreground mb-3">Limits</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <Label class="text-xs">Episodes / story</Label>
                                <Input v-model.number="getEditForm(editingPack).episode_limit" type="number" min="1" class="text-center" />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Revision credits</Label>
                                <Input v-model.number="getEditForm(editingPack).revision_credits" type="number" min="0" class="text-center" />
                            </div>
                        </div>
                    </div>

                    <Separator />

                    <!-- Pricing -->
                    <div>
                        <p class="text-xs font-bold tracking-widest uppercase text-muted-foreground mb-3">Pricing</p>
                        <div class="space-y-1.5 max-w-[12rem]">
                            <Label class="text-xs">Price ($, one-time)</Label>
                            <Input v-model.number="getEditForm(editingPack).price_dollars" type="number" min="0" step="0.01" class="text-center" />
                        </div>
                    </div>

                    <Separator />

                    <!-- Stripe Price ID -->
                    <div class="space-y-1.5">
                        <p class="text-sm font-semibold" style="color: #1A1A1A;">Stripe Price ID</p>
                        <Input v-model="getEditForm(editingPack).stripe_price_id" placeholder="price_xxx" class="font-mono text-xs" />
                        <div v-if="needsStripe(getEditForm(editingPack))" class="flex items-center gap-1.5 text-xs text-amber-600">
                            <AlertTriangle class="w-3.5 h-3.5" /> Active without a Stripe price — purchases will fail.
                        </div>
                    </div>

                    <Separator />

                    <!-- Active toggle -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold" style="color: #1A1A1A;">Active</p>
                            <p class="text-xs text-muted-foreground">Visible in the shop</p>
                        </div>
                        <Switch v-model="getEditForm(editingPack).is_active" :class="getEditForm(editingPack).is_active ? '!bg-green-500' : '!bg-gray-300'" />
                    </div>
                </div>

                <DialogFooter class="flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <Button variant="ghost" size="sm" class="gap-1.5 text-destructive hover:text-destructive hover:bg-destructive/10 order-last sm:order-first" @click="openDeleteDialog(editingPack)">
                        <Trash2 class="w-3.5 h-3.5" /> Delete
                    </Button>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="editingPack = null">Cancel</Button>
                        <Button
                            :disabled="!getEditForm(editingPack).isDirty || getEditForm(editingPack).processing"
                            class="bg-gradient-to-r from-[#FFC837] to-[#F5A000] text-[#1A1A1A] border-0 hover:opacity-90 hover:bg-none font-semibold disabled:opacity-40"
                            @click="savePack"
                        >Save Changes</Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Create pack dialog -->
        <Dialog v-model:open="createDialogOpen">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>New Pack</DialogTitle>
                    <DialogDescription>The new pack will be active and visible in the shop immediately.</DialogDescription>
                </DialogHeader>

                <div class="space-y-5">
                    <div class="space-y-1.5">
                        <Label>Pack Name</Label>
                        <Input v-model="newForm.label" placeholder="e.g. Enterprise" />
                        <p v-if="newForm.errors.label" class="text-xs text-destructive">{{ newForm.errors.label }}</p>
                    </div>

                    <Separator />

                    <div>
                        <p class="text-xs font-bold tracking-widest uppercase text-muted-foreground mb-3">Limits</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <Label class="text-xs">Episodes / story</Label>
                                <Input v-model.number="newForm.episode_limit" type="number" min="1" class="text-center" />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Revision credits</Label>
                                <Input v-model.number="newForm.revision_credits" type="number" min="0" class="text-center" />
                            </div>
                        </div>
                    </div>

                    <Separator />

                    <div>
                        <p class="text-xs font-bold tracking-widest uppercase text-muted-foreground mb-3">Pricing</p>
                        <div class="space-y-1.5 max-w-[12rem]">
                            <Label class="text-xs">Price ($, one-time)</Label>
                            <Input v-model.number="newForm.price_dollars" type="number" min="0" step="0.01" class="text-center" />
                        </div>
                    </div>

                    <Separator />

                    <div class="space-y-1.5">
                        <p class="text-sm font-semibold" style="color: #1A1A1A;">Stripe Price ID</p>
                        <Input v-model="newForm.stripe_price_id" placeholder="price_xxx" class="font-mono text-xs" />
                        <p class="text-xs text-muted-foreground">Leave blank to stage the pack — add it before buyers can check out.</p>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="createDialogOpen = false">Cancel</Button>
                    <Button
                        :disabled="!newForm.label || newForm.processing"
                        class="bg-gradient-to-r from-[#FFC837] to-[#F5A000] text-[#1A1A1A] border-0 hover:opacity-90 hover:bg-none font-semibold disabled:opacity-40"
                        @click="createPack"
                    >Create Pack</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="max-w-sm" v-if="deletingPack">
                <DialogHeader>
                    <DialogTitle>Delete Pack</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to permanently delete <span class="font-semibold text-foreground">{{ deletingPack.label }}</span>? This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <p v-if="deleteError" class="text-sm text-destructive">{{ deleteError }}</p>
                <DialogFooter>
                    <Button variant="outline" @click="deletingPack = null">Cancel</Button>
                    <Button variant="destructive" @click="confirmDelete">Delete Pack</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
