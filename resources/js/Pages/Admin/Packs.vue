<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Plus, Pencil, Coins, Trash2, AlertTriangle, BookOpen } from '@lucide/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Switch } from '@/Components/ui/switch';
import { Separator } from '@/Components/ui/separator';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/Components/ui/dialog';
import {
    Tooltip, TooltipContent, TooltipTrigger,
} from '@/Components/ui/tooltip';

const props = defineProps({ packs: Array });

const savedId = ref(null);
const flash   = (id) => { savedId.value = id; setTimeout(() => savedId.value = null, 1800); };

const episodeOptionsLabel = (max) => [12, 18, 24].filter(n => n <= (max ?? 24)).join(', ');

const typeMeta = {
    partner:  { label: 'Verified Partner', class: 'bg-amber-50 text-amber-700 border-amber-200' },
    storybot: { label: 'StoryBot',         class: 'bg-purple-50 text-purple-700 border-purple-200' },
    addon:    { label: 'Add-on',           class: 'bg-indigo-50 text-indigo-700 border-indigo-200' },
};

// ── Edit existing packs ────────────────────────────────────────────────────────

const editForms   = ref({});
const editingPack = ref(null);

const getEditForm = (pack) => {
    if (!editForms.value[pack.id]) {
        editForms.value[pack.id] = useForm({
            label:           pack.label,
            type:            pack.type,
            credits:         pack.credits,
            max_episodes:    pack.max_episodes,
            price_dollars:   pack.price_dollars,
            stripe_price_id: pack.stripe_price_id ?? '',
            is_active:       pack.is_active,
        });
    }
    return editForms.value[pack.id];
};

const openEdit = (pack) => { getEditForm(pack); editingPack.value = pack; };

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
    label:           '',
    type:            'storybot',
    credits:         48,
    max_episodes:    24,
    price_dollars:   180,
    stripe_price_id: '',
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
                <p class="text-xs mt-0.5 text-muted-foreground">Each pack grants StoryBot credits. 1 credit = generate or refine 1 episode. Changes apply to new purchases only.</p>
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
                    savedId === 'pack-' + pack.id ? 'ring-2 ring-[#F5A000] ring-offset-1' : 'ring-1 ring-[#DDDDDD]',
                    !getEditForm(pack).is_active && 'opacity-60',
                ]"
            >
                <div class="px-5 pt-5 pb-4">
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div>
                            <p class="text-base font-black leading-tight" style="color: #1A1A1A;">{{ getEditForm(pack).label }}</p>
                            <p class="text-[11px] font-mono text-muted-foreground leading-tight">{{ pack.slug }}</p>
                            <Badge variant="outline" :class="typeMeta[pack.type]?.class" class="mt-1 text-[10px]">
                                {{ typeMeta[pack.type]?.label ?? pack.type }}
                            </Badge>
                        </div>
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
                    <div class="mb-4">
                        <p class="text-2xl font-black" style="color: #1A1A1A;">
                            ${{ pack.price_dollars }}
                            <span class="text-sm font-medium text-muted-foreground">one-time</span>
                        </p>
                    </div>

                    <!-- Stripe warning -->
                    <div
                        v-if="needsStripe(getEditForm(pack))"
                        class="flex items-start gap-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 mb-4"
                    >
                        <AlertTriangle class="w-3.5 h-3.5 text-amber-600 shrink-0 mt-0.5" />
                        <p class="text-[11px] leading-tight font-medium text-amber-700">No Stripe price — purchases will fail.</p>
                    </div>

                    <!-- Credits + episodes stats -->
                    <div class="grid gap-3" :class="pack.type === 'addon' ? 'grid-cols-1' : 'grid-cols-2'">
                        <div class="flex items-center gap-3 rounded-xl py-3 px-4" style="background: #F8F8F8;">
                            <Coins class="w-5 h-5 text-[#F5A000] shrink-0" />
                            <div>
                                <p class="text-xl font-black leading-none" style="color: #1A1A1A;">{{ pack.credits }}</p>
                                <p class="text-[10px] font-medium text-muted-foreground mt-0.5">StoryBot credits</p>
                            </div>
                        </div>
                        <div v-if="pack.type !== 'addon'" class="flex items-center gap-3 rounded-xl py-3 px-4" style="background: #F8F8F8;">
                            <BookOpen class="w-5 h-5 text-[#F5A000] shrink-0" />
                            <div>
                                <p class="text-sm font-black leading-tight" style="color: #1A1A1A;">{{ episodeOptionsLabel(pack.max_episodes) }}</p>
                                <p class="text-[10px] font-medium text-muted-foreground mt-0.5">episodes / story</p>
                            </div>
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
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Pack Name</Label>
                            <Input v-model="getEditForm(editingPack).label" placeholder="e.g. Basic" />
                            <p v-if="getEditForm(editingPack).errors.label" class="text-xs text-destructive">{{ getEditForm(editingPack).errors.label }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Audience</Label>
                            <Select :model-value="getEditForm(editingPack).type" @update:model-value="val => getEditForm(editingPack).type = val">
                                <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="partner">Verified Partner</SelectItem>
                                    <SelectItem value="storybot">StoryBot (public)</SelectItem>
                                    <SelectItem value="addon">Add-on</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <Separator />

                    <div class="grid grid-cols-3 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs">StoryBot Credits</Label>
                            <Input v-model.number="getEditForm(editingPack).credits" type="number" min="1" class="text-center" />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs">Price ($, one-time)</Label>
                            <Input v-model.number="getEditForm(editingPack).price_dollars" type="number" min="0" step="0.01" class="text-center" />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs">Episodes / story</Label>
                            <Select :model-value="String(getEditForm(editingPack).max_episodes)" @update:model-value="val => getEditForm(editingPack).max_episodes = Number(val)">
                                <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="12">12</SelectItem>
                                    <SelectItem value="18">12, 18</SelectItem>
                                    <SelectItem value="24">12, 18, 24</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <Separator />

                    <div class="space-y-1.5">
                        <p class="text-sm font-semibold" style="color: #1A1A1A;">Stripe Price ID</p>
                        <Input v-model="getEditForm(editingPack).stripe_price_id" placeholder="price_xxx" class="font-mono text-xs" />
                        <div v-if="needsStripe(getEditForm(editingPack))" class="flex items-center gap-1.5 text-xs text-amber-600">
                            <AlertTriangle class="w-3.5 h-3.5" /> Active without a Stripe price — purchases will fail.
                        </div>
                    </div>

                    <Separator />

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
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Pack Name</Label>
                            <Input v-model="newForm.label" placeholder="e.g. Basic" />
                            <p v-if="newForm.errors.label" class="text-xs text-destructive">{{ newForm.errors.label }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Audience</Label>
                            <Select :model-value="newForm.type" @update:model-value="val => newForm.type = val">
                                <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="partner">Verified Partner</SelectItem>
                                    <SelectItem value="storybot">StoryBot (public)</SelectItem>
                                    <SelectItem value="addon">Add-on</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <Separator />

                    <div class="grid grid-cols-3 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs">StoryBot Credits</Label>
                            <Input v-model.number="newForm.credits" type="number" min="1" class="text-center" />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs">Price ($, one-time)</Label>
                            <Input v-model.number="newForm.price_dollars" type="number" min="0" step="0.01" class="text-center" />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs">Episodes / story</Label>
                            <Select :model-value="String(newForm.max_episodes)" @update:model-value="val => newForm.max_episodes = Number(val)">
                                <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="12">12</SelectItem>
                                    <SelectItem value="18">12, 18</SelectItem>
                                    <SelectItem value="24">12, 18, 24</SelectItem>
                                </SelectContent>
                            </Select>
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
