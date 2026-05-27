<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, ToggleLeft, ToggleRight } from '@lucide/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    plans: Array,
});

const savedId = ref(null);
const flash   = (id) => { savedId.value = id; setTimeout(() => savedId.value = null, 1800); };

// ── Edit existing plans ───────────────────────────────────────────────────────

const editForms = ref({});

const getEditForm = (plan) => {
    if (!editForms.value[plan.id]) {
        editForms.value[plan.id] = useForm({
            label:             plan.label,
            episode_limit:     plan.episode_limit,
            stories_per_month: plan.stories_per_month,
            refine_monthly:    plan.refine_monthly,
            price_monthly:     plan.price_monthly,
            price_yearly:      plan.price_yearly,
            trial_months:      plan.trial_months,
            is_active:         plan.is_active,
        });
    }
    return editForms.value[plan.id];
};

const savePlan = (plan) => {
    getEditForm(plan).patch(route('admin.plans.update', plan.id), {
        onSuccess: () => flash('plan-' + plan.id),
    });
};

const toggleActive = (plan) => {
    const form = getEditForm(plan);
    form.is_active = !form.is_active;
    savePlan(plan);
};

// ── Create new plan ───────────────────────────────────────────────────────────

const showNew = ref(false);

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
        onSuccess: () => { showNew.value = false; newForm.reset(); },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Plans — Admin" />

        <div class="bg-white rounded-2xl overflow-hidden" style="border: 1px solid #DDDDDD;">

            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color: #EBEBEB;">
                <div>
                    <p class="font-bold" style="color: #1A1A1A;">Subscription Plans</p>
                    <p class="text-xs mt-0.5" style="color: #AAAAAA;">Changes apply to new subscriptions only. Deactivating hides a plan from sign-up.</p>
                </div>
                <button
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold cursor-pointer transition hover:opacity-90"
                    style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                    @click="showNew = !showNew"
                >
                    <Plus class="w-4 h-4" /> New Plan
                </button>
            </div>

            <!-- Column headers (desktop) -->
            <div class="hidden md:grid px-5 py-2" style="grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr auto; border-bottom: 1px solid #F5F5F5;">
                <p v-for="h in ['Plan Name','Episodes','Stories/Mo','Refine/Mo','$/Mo','$/Yr','Trial Mo','Active','']"
                    :key="h" class="text-xs font-bold tracking-widest uppercase" style="color: #CCCCCC;">{{ h }}</p>
            </div>

            <!-- Plan rows -->
            <div class="divide-y" style="border-color: #F5F5F5;">
                <div
                    v-for="plan in plans" :key="plan.id"
                    class="px-5 py-3 transition-colors"
                    :style="savedId === 'plan-' + plan.id
                        ? 'background: #FFFDF5;'
                        : !getEditForm(plan).is_active ? 'background: #FAFAF8; opacity: 0.65;' : ''"
                >
                    <!-- Desktop row -->
                    <div class="hidden md:grid items-center gap-3" style="grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr auto;">
                        <input
                            v-model="getEditForm(plan).label"
                            type="text"
                            class="px-2.5 py-1.5 rounded-lg text-sm outline-none"
                            style="border: 1px solid #EBEBEB; background: #FAFAF8; color: #1A1A1A;"
                        />
                        <input v-model.number="getEditForm(plan).episode_limit"     type="number" min="1" class="px-2 py-1.5 rounded-lg text-sm outline-none text-center" style="border:1px solid #EBEBEB; background:#FAFAF8; color:#1A1A1A;" />
                        <input v-model.number="getEditForm(plan).stories_per_month" type="number" min="0" class="px-2 py-1.5 rounded-lg text-sm outline-none text-center" style="border:1px solid #EBEBEB; background:#FAFAF8; color:#1A1A1A;" />
                        <input v-model.number="getEditForm(plan).refine_monthly"    type="number" min="0" class="px-2 py-1.5 rounded-lg text-sm outline-none text-center" style="border:1px solid #EBEBEB; background:#FAFAF8; color:#1A1A1A;" />
                        <div class="relative">
                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs pointer-events-none" style="color:#AAAAAA;">$</span>
                            <input v-model.number="getEditForm(plan).price_monthly" type="number" min="0" class="w-full pl-4 pr-2 py-1.5 rounded-lg text-sm outline-none" style="border:1px solid #EBEBEB; background:#FAFAF8; color:#1A1A1A;" />
                        </div>
                        <div class="relative">
                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs pointer-events-none" style="color:#AAAAAA;">$</span>
                            <input v-model.number="getEditForm(plan).price_yearly"  type="number" min="0" class="w-full pl-4 pr-2 py-1.5 rounded-lg text-sm outline-none" style="border:1px solid #EBEBEB; background:#FAFAF8; color:#1A1A1A;" />
                        </div>
                        <input v-model.number="getEditForm(plan).trial_months"      type="number" min="0" class="px-2 py-1.5 rounded-lg text-sm outline-none text-center" style="border:1px solid #EBEBEB; background:#FAFAF8; color:#1A1A1A;" />

                        <button class="flex justify-center cursor-pointer" @click="toggleActive(plan)">
                            <ToggleRight v-if="getEditForm(plan).is_active" class="w-6 h-6" style="color: #22C55E;" />
                            <ToggleLeft  v-else                             class="w-6 h-6" style="color: #DDDDDD;" />
                        </button>

                        <button
                            :disabled="!getEditForm(plan).isDirty || getEditForm(plan).processing"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer disabled:opacity-30 disabled:cursor-default"
                            :style="getEditForm(plan).isDirty
                                ? 'background: linear-gradient(to right,#FFC837,#F5A000); color:#1A1A1A;'
                                : 'background:#F5F5F5; color:#AAAAAA;'"
                            @click="savePlan(plan)"
                        >Save</button>
                    </div>

                    <!-- Mobile card -->
                    <div class="md:hidden space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <input
                                v-model="getEditForm(plan).label"
                                type="text"
                                class="flex-1 px-2.5 py-1.5 rounded-lg text-sm font-semibold outline-none"
                                style="border:1px solid #EBEBEB; background:#FAFAF8; color:#1A1A1A;"
                            />
                            <button class="cursor-pointer shrink-0" @click="toggleActive(plan)">
                                <ToggleRight v-if="getEditForm(plan).is_active" class="w-6 h-6" style="color:#22C55E;" />
                                <ToggleLeft  v-else                             class="w-6 h-6" style="color:#DDDDDD;" />
                            </button>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div v-for="[key, lbl] in [['episode_limit','Episodes'],['stories_per_month','Stories/Mo'],['refine_monthly','Refine/Mo'],['price_monthly','$/Month'],['price_yearly','$/Year'],['trial_months','Trial Mo']]" :key="key">
                                <p class="text-xs mb-1" style="color:#AAAAAA;">{{ lbl }}</p>
                                <input v-model.number="getEditForm(plan)[key]" type="number" min="0" class="w-full px-2 py-1.5 rounded-lg text-sm outline-none text-center" style="border:1px solid #EBEBEB; background:#FAFAF8; color:#1A1A1A;" />
                            </div>
                        </div>
                        <button
                            :disabled="!getEditForm(plan).isDirty || getEditForm(plan).processing"
                            class="w-full py-2 rounded-lg text-sm font-semibold cursor-pointer transition disabled:opacity-30 disabled:cursor-default"
                            :style="getEditForm(plan).isDirty ? 'background:linear-gradient(to right,#FFC837,#F5A000);color:#1A1A1A;' : 'background:#F5F5F5;color:#AAAAAA;'"
                            @click="savePlan(plan)"
                        >Save Changes</button>
                    </div>
                </div>

                <!-- New plan row -->
                <div v-if="showNew" class="px-5 py-4" style="background: #FFFDF5;">
                    <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color: #F5A000;">New Plan</p>

                    <!-- Desktop -->
                    <div class="hidden md:grid items-center gap-3 mb-0" style="grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr auto;">
                        <input v-model="newForm.label" type="text" placeholder="Plan name"
                            class="px-2.5 py-1.5 rounded-lg text-sm outline-none"
                            style="border:1px solid #FDEAB0; background:#FFFDF5; color:#1A1A1A;" />
                        <input v-model.number="newForm.episode_limit"     type="number" min="1" class="px-2 py-1.5 rounded-lg text-sm outline-none text-center" style="border:1px solid #FDEAB0; background:#FFFDF5; color:#1A1A1A;" />
                        <input v-model.number="newForm.stories_per_month" type="number" min="0" class="px-2 py-1.5 rounded-lg text-sm outline-none text-center" style="border:1px solid #FDEAB0; background:#FFFDF5; color:#1A1A1A;" />
                        <input v-model.number="newForm.refine_monthly"    type="number" min="0" class="px-2 py-1.5 rounded-lg text-sm outline-none text-center" style="border:1px solid #FDEAB0; background:#FFFDF5; color:#1A1A1A;" />
                        <div class="relative">
                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs pointer-events-none" style="color:#AAAAAA;">$</span>
                            <input v-model.number="newForm.price_monthly" type="number" min="0" class="w-full pl-4 pr-2 py-1.5 rounded-lg text-sm outline-none" style="border:1px solid #FDEAB0; background:#FFFDF5; color:#1A1A1A;" />
                        </div>
                        <div class="relative">
                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs pointer-events-none" style="color:#AAAAAA;">$</span>
                            <input v-model.number="newForm.price_yearly"  type="number" min="0" class="w-full pl-4 pr-2 py-1.5 rounded-lg text-sm outline-none" style="border:1px solid #FDEAB0; background:#FFFDF5; color:#1A1A1A;" />
                        </div>
                        <input v-model.number="newForm.trial_months"      type="number" min="0" class="px-2 py-1.5 rounded-lg text-sm outline-none text-center" style="border:1px solid #FDEAB0; background:#FFFDF5; color:#1A1A1A;" />
                        <span />
                        <button
                            :disabled="!newForm.label || newForm.processing"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold cursor-pointer transition hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
                            style="background: linear-gradient(to right,#FFC837,#F5A000); color:#1A1A1A;"
                            @click="createPlan"
                        >Create</button>
                    </div>

                    <!-- Mobile -->
                    <div class="md:hidden space-y-2">
                        <input v-model="newForm.label" type="text" placeholder="Plan name"
                            class="w-full px-2.5 py-1.5 rounded-lg text-sm outline-none"
                            style="border:1px solid #FDEAB0; background:#FFFDF5; color:#1A1A1A;" />
                        <div class="grid grid-cols-3 gap-2">
                            <div v-for="[key, lbl] in [['episode_limit','Episodes'],['stories_per_month','Stories/Mo'],['refine_monthly','Refine/Mo'],['price_monthly','$/Month'],['price_yearly','$/Year'],['trial_months','Trial Mo']]" :key="key">
                                <p class="text-xs mb-1" style="color:#AAAAAA;">{{ lbl }}</p>
                                <input v-model.number="newForm[key]" type="number" min="0" class="w-full px-2 py-1.5 rounded-lg text-sm outline-none text-center" style="border:1px solid #FDEAB0; background:#FFFDF5; color:#1A1A1A;" />
                            </div>
                        </div>
                        <button
                            :disabled="!newForm.label || newForm.processing"
                            class="w-full py-2 rounded-lg text-sm font-semibold cursor-pointer transition hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
                            style="background:linear-gradient(to right,#FFC837,#F5A000);color:#1A1A1A;"
                            @click="createPlan"
                        >Create Plan</button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
