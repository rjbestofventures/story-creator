<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { ArrowRight, Check, MailCheck, Loader2 } from '@lucide/vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { csrfHeaders } from '@/lib/csrf';
import { retailPlans, gradBg } from '@/lib/retailPlans';

const props = defineProps({
    plans: Array,
    selected: { type: String, default: null },
    justVerified: Boolean,
});

const picked = ref(props.selected);
const packIdFor = (key) => props.plans.find((p) => p.key === key)?.pack_id ?? null;

const buying = ref(null);
const error = ref('');

const buy = async (key) => {
    const packId = packIdFor(key);
    if (buying.value || !packId) return;
    picked.value = key;
    buying.value = key;
    error.value = '';

    try {
        const res = await fetch(route('shop.checkout'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...csrfHeaders() },
            body: JSON.stringify({ pack_id: packId, from: 'plan' }),
        });
        const data = await res.json().catch(() => ({}));

        if (!res.ok || !data.url) {
            error.value = data.message ?? 'Could not start checkout. Please try again.';
            return;
        }

        window.location.href = data.url;
    } catch {
        error.value = 'Something went wrong. Please try again.';
    } finally {
        buying.value = null;
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Choose Your Plan" />

        <div class="max-w-4xl mx-auto px-4 md:px-8 py-10">
            <div v-if="justVerified" role="status" class="flex items-center gap-3 mb-8 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-sm text-[#1A1A1A]">
                <MailCheck class="w-5 h-5 text-green-700 shrink-0" />
                <span><b>Your email is verified.</b> Choose your plan to get started.</span>
            </div>

            <h1 class="text-3xl font-extrabold tracking-[-0.01em] text-[#1A1A1A]">Choose Your Plan</h1>
            <p class="mt-2 text-base text-[#555555]">One-time payment. Your credits never expire.</p>

            <p v-if="error" role="alert" class="mt-6 text-sm font-semibold text-[#B42318]">{{ error }}</p>

            <div class="mt-8 flex flex-wrap gap-6 items-stretch">
                <div
                    v-for="tier in retailPlans"
                    :key="tier.key"
                    class="relative flex-[1_1_280px] min-w-0 bg-white rounded-2xl p-7 flex flex-col gap-3 transition-shadow"
                    :class="picked === tier.key ? 'border-[3px] border-[#F59E0B] shadow-[0_8px_30px_rgba(245,158,11,0.25)]' : 'border-2 border-[#F59E0B]/50'"
                >
                    <div v-if="picked === tier.key" class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-[#1A1A1A] text-white text-xs font-bold px-3.5 py-1 rounded-full whitespace-nowrap">Your pick</div>
                    <div v-else-if="tier.popular" :class="gradBg" class="absolute -top-3.5 left-1/2 -translate-x-1/2 text-[#1A1A1A] text-xs font-bold px-3.5 py-1 rounded-full whitespace-nowrap">Most Popular</div>
                    <div class="text-base font-bold text-[#1A1A1A]">{{ tier.name }}</div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <b class="text-5xl font-extrabold tracking-[-0.02em] text-[#1A1A1A]">{{ tier.price }}</b>
                        <span class="text-sm text-[#555555]">one-time</span>
                    </div>
                    <div class="text-[13px] italic leading-normal text-[#555555]">{{ tier.desc }}</div>
                    <ul class="mt-1 mb-2 flex flex-col gap-2 flex-1 text-[15px] font-semibold text-[#1A1A1A]">
                        <li v-for="f in tier.features" :key="f" class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-[#F59E0B]" :stroke-width="3" />{{ f }}
                        </li>
                    </ul>
                    <button
                        type="button"
                        :disabled="buying !== null || !packIdFor(tier.key)"
                        class="flex items-center justify-center gap-2 h-12 px-[22px] rounded-[10px] text-[15px] font-bold text-[#1A1A1A] cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                        :class="picked === tier.key ? gradBg : 'bg-white border-2 border-[#F59E0B]'"
                        @click="buy(tier.key)"
                    >
                        <Loader2 v-if="buying === tier.key" class="w-4 h-4 animate-spin" />
                        <template v-if="!packIdFor(tier.key)">Not available right now</template>
                        <template v-else>Buy {{ tier.name }} <ArrowRight class="w-4 h-4" :stroke-width="2.2" /></template>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
