<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Check, Zap, ArrowRight, Loader2 } from 'lucide-vue-next';

const props = defineProps({ plans: Array });

const interval = ref('monthly');

const price = (plan) => {
    if (plan.price_monthly === 0) return 'Free';
    if (interval.value === 'yearly' && plan.price_yearly > 0) {
        return '$' + Math.round(plan.price_yearly / 12) + '/mo';
    }
    return '$' + plan.price_monthly + '/mo';
};

const yearlySavings = (plan) => {
    if (!plan.price_yearly || !plan.price_monthly) return null;
    const monthly = plan.price_monthly * 12;
    const saved = monthly - plan.price_yearly;
    return saved > 0 ? saved : null;
};

const freeForm = computed(() =>
    Object.fromEntries(props.plans.map(p => [p.id, useForm({ plan_id: p.id })]))
);

const loadingPlanId = ref(null);

const selectPlan = async (plan) => {
    if (plan.price_monthly === 0) {
        freeForm.value[plan.id].post(route('billing.free'));
        return;
    }

    loadingPlanId.value = plan.id;
    try {
        const res = await fetch(route('billing.checkout'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ plan_id: plan.id, interval: interval.value }),
        });
        const data = await res.json();
        window.location.href = data.url;
    } catch {
        loadingPlanId.value = null;
    }
};

const isFeatured = (plan) => plan.slug === 'premium';
const isProcessing = (plan) => plan.price_monthly === 0
    ? freeForm.value[plan.id]?.processing
    : loadingPlanId.value === plan.id;
</script>

<template>
    <Head title="Choose your plan" />

    <div class="min-h-screen flex flex-col" style="background-color: #FAFAF8;">

        <!-- Top bar -->
        <div class="flex items-center justify-center pt-10 pb-2">
            <Link href="/" class="flex items-center text-xl font-bold tracking-tight">
                <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
                <span style="color: #1A1A1A;">.Bot</span>
            </Link>
        </div>

        <!-- Header -->
        <div class="text-center px-4 pt-8 pb-6">
            <div
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold mb-4"
                style="background-color: #FEF9EC; color: #F5A000; border: 1px solid #FDE68A;"
            >
                <Zap class="w-3.5 h-3.5" />
                Start telling your story
            </div>
            <h1 class="text-3xl font-black mb-2" style="color: #1A1A1A;">Choose your plan</h1>
            <p class="text-base" style="color: #555555;">Start free or pick a plan — cancel anytime.</p>
        </div>

        <!-- Interval toggle -->
        <div class="flex items-center justify-center gap-3 pb-8">
            <span class="text-sm font-medium" :style="interval === 'monthly' ? 'color:#1A1A1A' : 'color:#AAAAAA'">Monthly</span>
            <button
                type="button"
                class="relative w-12 h-6 rounded-full transition-colors duration-200 cursor-pointer"
                :style="interval === 'yearly' ? 'background:#F5A000' : 'background:#DDDDDD'"
                @click="interval = interval === 'monthly' ? 'yearly' : 'monthly'"
            >
                <span
                    class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                    :class="interval === 'yearly' ? 'translate-x-6' : 'translate-x-0'"
                />
            </button>
            <span class="text-sm font-medium" :style="interval === 'yearly' ? 'color:#1A1A1A' : 'color:#AAAAAA'">
                Yearly
                <span class="ml-1 px-1.5 py-0.5 rounded text-xs font-bold" style="background:#FEF9EC; color:#F5A000;">Save ~17%</span>
            </span>
        </div>

        <!-- Plan cards -->
        <div class="flex-1 px-4 pb-16">
            <div class="max-w-5xl mx-auto grid grid-cols-1 gap-5" :class="plans.length <= 3 ? 'sm:grid-cols-2 lg:grid-cols-3' : 'sm:grid-cols-2 lg:grid-cols-4'">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="relative flex flex-col rounded-2xl p-6 transition-shadow duration-200"
                    :style="isFeatured(plan)
                        ? 'background:#FFFFFF; border: 2px solid #F5A623; box-shadow: 0 4px 24px rgba(245,166,35,0.15);'
                        : 'background:#FFFFFF; border: 1px solid #DDDDDD;'"
                >
                    <!-- Most popular badge -->
                    <div
                        v-if="isFeatured(plan)"
                        class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-xs font-bold"
                        style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                    >
                        Most Popular
                    </div>

                    <!-- Plan name -->
                    <h2 class="text-base font-black mb-1" style="color: #1A1A1A;">{{ plan.label }}</h2>

                    <!-- Price -->
                    <div class="mb-1">
                        <span class="text-3xl font-black" style="color: #1A1A1A;">{{ price(plan) }}</span>
                        <span v-if="interval === 'yearly' && yearlySavings(plan)" class="ml-2 text-xs font-semibold" style="color: #F5A000;">
                            Save ${{ yearlySavings(plan) }}/yr
                        </span>
                    </div>
                    <p v-if="interval === 'yearly' && plan.price_yearly > 0" class="text-xs mb-4" style="color: #AAAAAA;">
                        billed ${{ plan.price_yearly }}/year
                    </p>
                    <div v-else class="mb-4" />

                    <!-- Features -->
                    <ul class="space-y-2 flex-1 mb-6">
                        <li class="flex items-start gap-2 text-sm" style="color: #555555;">
                            <Check class="w-4 h-4 mt-0.5 flex-shrink-0" style="color: #F5A000;" />
                            {{ plan.episode_limit }} episodes per story
                        </li>
                        <li class="flex items-start gap-2 text-sm" :style="plan.stories_per_month === 0 ? 'color:#AAAAAA' : 'color:#555555'">
                            <Check class="w-4 h-4 mt-0.5 flex-shrink-0" :style="plan.stories_per_month === 0 ? 'color:#DDDDDD' : 'color:#F5A000'" />
                            <span v-if="plan.stories_per_month === 0">Story generation not included</span>
                            <span v-else>{{ plan.stories_per_month }} stories/month</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm" style="color: #555555;">
                            <Check class="w-4 h-4 mt-0.5 flex-shrink-0" style="color: #F5A000;" />
                            {{ plan.refine_monthly }} refinements/month
                        </li>
                        <li v-if="plan.trial_months > 0" class="flex items-start gap-2 text-sm" style="color: #555555;">
                            <Check class="w-4 h-4 mt-0.5 flex-shrink-0" style="color: #F5A000;" />
                            {{ plan.trial_months }}-month free trial
                        </li>
                    </ul>

                    <!-- CTA -->
                    <button
                        type="button"
                        :disabled="isProcessing(plan)"
                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl font-bold text-sm transition-all duration-200 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                        :style="isFeatured(plan)
                            ? 'background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;'
                            : plan.price_monthly === 0
                                ? 'background: #F5F5F5; color: #555555; border: 1px solid #DDDDDD;'
                                : 'background: #1A1A1A; color: #FFFFFF;'"
                        @click="selectPlan(plan)"
                    >
                        <Loader2 v-if="isProcessing(plan)" class="w-4 h-4 animate-spin" />
                        <template v-else>
                            <span>{{ plan.price_monthly === 0 ? 'Start Free' : 'Get Started' }}</span>
                            <ArrowRight class="w-4 h-4" />
                        </template>
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center pb-8 text-xs" style="color: #AAAAAA;">
            Secure payments via Stripe · Cancel anytime · No hidden fees
        </div>
    </div>
</template>
