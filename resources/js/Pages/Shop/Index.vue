<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Sparkles, Zap, ArrowRight, Check, ShoppingBag, Coins, Lock } from 'lucide-vue-next';

const props = defineProps({
    packs:       Array,
    addon:       Object,
    canBuyAddon: Boolean,
    credits:     { type: Number, default: null },
    notice:      String,
});

const loadingPackId = ref(null);
const error         = ref('');

// Middle pack (by price) is highlighted as most popular.
const popularId = computed(() => {
    if (!props.packs?.length) return null;
    const sorted = [...props.packs].sort((a, b) => a.price - b.price);
    return sorted[Math.floor(sorted.length / 2)]?.id ?? null;
});

const buy = async (pack) => {
    if (loadingPackId.value || !pack) return;
    error.value         = '';
    loadingPackId.value = pack.id;

    try {
        const res = await fetch(route('shop.checkout'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ pack_id: pack.id }),
        });

        const data = await res.json();

        if (!res.ok) {
            error.value = data.message ?? 'Could not start checkout. Please try again.';
            return;
        }

        window.location.href = data.url;
    } catch {
        error.value = 'Something went wrong. Please try again.';
    } finally {
        loadingPackId.value = null;
    }
};

const formatPrice = (cents) => '$' + (cents / 100).toFixed(0);
</script>

<template>
    <Head title="Buy Credits" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#FAFAF8]">

            <!-- Header -->
            <div class="bg-white border-b border-[#DDDDDD] px-4 md:px-8 py-5">
                <div class="max-w-5xl mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                            <ShoppingBag class="w-5 h-5 text-[#F5A000]" />
                        </div>
                        <div>
                            <h1 class="text-lg font-black text-[#1A1A1A]">Buy Credits</h1>
                            <p class="text-xs text-[#555555]">One-time purchase · Credits never expire</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div v-if="credits !== null" class="hidden sm:flex items-center gap-2 px-3 h-9 rounded-lg bg-[#F8F8F8] text-sm font-semibold text-[#555555]">
                            <Coins class="w-4 h-4 text-[#F5A000]" />
                            {{ credits }} credits
                        </div>
                        <Link :href="route('stories.index')" class="text-sm text-[#555555] hover:text-[#1A1A1A] transition-colors">
                            ← Back to stories
                        </Link>
                    </div>
                </div>
            </div>

            <div class="max-w-5xl mx-auto px-4 md:px-8 py-10">

                <div v-if="notice" class="mb-6 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-[#1A1A1A]">
                    {{ notice }}
                </div>
                <div v-if="error" class="mb-6 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-600">
                    {{ error }}
                </div>

                <!-- Intro -->
                <div class="text-center mb-10">
                    <h2 class="text-3xl font-black text-[#1A1A1A] mb-3">Choose your credit pack</h2>
                    <p class="text-[#555555] max-w-lg mx-auto">
                        Credits power everything: <strong class="text-[#1A1A1A]">1 credit generates or refines 1 episode</strong>.
                        Choose 12, 18, or 24 episodes per story. Credits never expire.
                    </p>
                </div>

                <!-- Pack cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="pack in packs"
                        :key="pack.id"
                        class="relative bg-white rounded-2xl border-2 border-[#F5A000] flex flex-col overflow-hidden transition-all duration-200"
                        :class="pack.id === popularId ? 'shadow-lg shadow-amber-100' : 'hover:shadow-sm'"
                    >
                        <div v-if="pack.id === popularId" class="absolute top-3 right-3 text-xs font-bold px-2.5 py-1 rounded-full bg-amber-100 text-[#F5A000]">
                            Most Popular
                        </div>

                        <div class="p-6 pb-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#FFC837] to-[#F5A000] flex items-center justify-center mb-4">
                                <Sparkles class="w-6 h-6 text-white" />
                            </div>

                            <h3 class="text-xl font-black text-[#1A1A1A] mb-1">{{ pack.label }}</h3>

                            <div class="flex items-end gap-1 mb-4">
                                <span class="text-4xl font-black text-[#1A1A1A]">{{ formatPrice(pack.price) }}</span>
                                <span class="text-sm text-[#555555] mb-1">one-time</span>
                            </div>

                            <ul class="space-y-2.5">
                                <li class="flex items-center gap-2.5 text-sm text-[#555555]">
                                    <div class="w-4 h-4 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                        <Check class="w-2.5 h-2.5 text-[#F5A000]" />
                                    </div>
                                    <span><strong class="text-[#1A1A1A]">{{ pack.credits }} StoryBot credits</strong></span>
                                </li>
                                <li class="flex items-center gap-2.5 text-sm text-[#555555]">
                                    <div class="w-4 h-4 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                        <Check class="w-2.5 h-2.5 text-[#F5A000]" />
                                    </div>
                                    <span>Choose 12, 18 or 24 episodes per story</span>
                                </li>
                                <li class="flex items-center gap-2.5 text-sm text-[#555555]">
                                    <div class="w-4 h-4 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                        <Check class="w-2.5 h-2.5 text-[#F5A000]" />
                                    </div>
                                    <span>1 credit = generate or refine 1 episode</span>
                                </li>
                                <li class="flex items-center gap-2.5 text-sm text-[#555555]">
                                    <div class="w-4 h-4 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                        <Check class="w-2.5 h-2.5 text-[#F5A000]" />
                                    </div>
                                    <span>Credits never expire</span>
                                </li>
                            </ul>
                        </div>

                        <div class="p-6 pt-0 mt-auto">
                            <button
                                type="button"
                                :disabled="!!loadingPackId"
                                @click="buy(pack)"
                                class="w-full flex items-center justify-center gap-2 py-3 rounded-xl font-bold text-sm transition-all duration-200 cursor-pointer bg-gradient-to-r from-[#FFC837] to-[#F5A000] text-[#1A1A1A] hover:opacity-90"
                                :class="loadingPackId ? 'opacity-60 cursor-not-allowed' : ''"
                            >
                                <template v-if="loadingPackId === pack.id">
                                    <span class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin" />
                                    Redirecting…
                                </template>
                                <template v-else>
                                    Buy {{ pack.label }}
                                    <ArrowRight class="w-4 h-4" />
                                </template>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Credit Boost add-on -->
                <div v-if="addon" class="mt-8">
                    <div
                        class="bg-white rounded-2xl border-2 flex flex-col md:flex-row md:items-center gap-6 p-6"
                        :class="canBuyAddon ? 'border-[#DDDDDD]' : 'border-[#DDDDDD] opacity-75'"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-700 flex items-center justify-center shrink-0">
                            <Zap class="w-6 h-6 text-white" />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-lg font-black text-[#1A1A1A]">{{ addon.label }}</h3>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600">Add-on</span>
                            </div>
                            <p class="text-sm text-[#555555] mt-0.5">
                                Running low? Top up with <strong class="text-[#1A1A1A]">{{ addon.credits }} credits</strong> for {{ formatPrice(addon.price) }}. Credits never expire.
                            </p>
                        </div>
                        <div class="shrink-0">
                            <button
                                v-if="canBuyAddon"
                                type="button"
                                :disabled="!!loadingPackId"
                                @click="buy(addon)"
                                class="flex items-center gap-2 py-2.5 px-5 rounded-xl font-bold text-sm bg-[#1A1A1A] text-white hover:bg-[#333] transition-all duration-200 cursor-pointer"
                                :class="loadingPackId ? 'opacity-60 cursor-not-allowed' : ''"
                            >
                                <template v-if="loadingPackId === addon.id">
                                    <span class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin" />
                                    Redirecting…
                                </template>
                                <template v-else>
                                    {{ formatPrice(addon.price) }} · Top Up
                                    <ArrowRight class="w-4 h-4" />
                                </template>
                            </button>
                            <div v-else class="flex items-center gap-2 text-xs font-semibold text-[#888888] px-3 py-2.5 rounded-xl bg-[#F8F8F8]">
                                <Lock class="w-3.5 h-3.5" />
                                Buy a pack first
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-center text-xs text-[#AAAAAA] mt-8">
                    Secure checkout via Stripe · No subscription · One-time payment
                </p>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
