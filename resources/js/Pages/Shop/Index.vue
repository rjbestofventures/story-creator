<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Sparkles, BookOpen, RefreshCcw, Zap, ArrowRight, Check, ShoppingBag } from 'lucide-vue-next';

const props = defineProps({
    packs:  Array,
    notice: String,
});

const loadingPackId = ref(null);
const error         = ref('');

const packMeta = {
    basic:        { color: 'from-blue-400 to-blue-600',    badge: null,        popular: false },
    premium:      { color: 'from-[#FFC837] to-[#F5A000]', badge: 'Most Popular', popular: true  },
    professional: { color: 'from-purple-500 to-purple-700', badge: 'Best Value', popular: false },
};

const buy = async (pack) => {
    if (loadingPackId.value) return;
    error.value       = '';
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
    <Head title="Buy Story Credits" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#FAFAF8]">

            <!-- Header -->
            <div class="bg-white border-b border-[#DDDDDD] px-4 md:px-8 py-5">
                <div class="max-w-4xl mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                            <ShoppingBag class="w-5 h-5 text-[#F5A000]" />
                        </div>
                        <div>
                            <h1 class="text-lg font-black text-[#1A1A1A]">Buy Story Credits</h1>
                            <p class="text-xs text-[#555555]">One-time purchase · Never expires</p>
                        </div>
                    </div>
                    <Link :href="route('stories.index')" class="text-sm text-[#555555] hover:text-[#1A1A1A] transition-colors">
                        ← Back to stories
                    </Link>
                </div>
            </div>

            <div class="max-w-4xl mx-auto px-4 md:px-8 py-10">

                <!-- Notice from redirect -->
                <div v-if="notice" class="mb-6 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-[#1A1A1A]">
                    {{ notice }}
                </div>

                <!-- Error -->
                <div v-if="error" class="mb-6 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-600">
                    {{ error }}
                </div>

                <!-- Intro -->
                <div class="text-center mb-10">
                    <h2 class="text-3xl font-black text-[#1A1A1A] mb-3">Choose your story pack</h2>
                    <p class="text-[#555555] max-w-md mx-auto">Each pack gives you one story with a set of episodes and revision credits. Credits never expire — use them whenever you're ready.</p>
                </div>

                <!-- Pack cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="pack in packs"
                        :key="pack.id"
                        class="relative bg-white rounded-2xl border-2 flex flex-col overflow-hidden transition-all duration-200"
                        :class="packMeta[pack.slug]?.popular
                            ? 'border-[#F5A000] shadow-lg shadow-amber-100'
                            : 'border-[#DDDDDD] hover:border-[#F5A000]/50 hover:shadow-sm'"
                    >
                        <!-- Popular badge -->
                        <div
                            v-if="packMeta[pack.slug]?.badge"
                            class="absolute top-3 right-3 text-xs font-bold px-2.5 py-1 rounded-full"
                            :class="packMeta[pack.slug]?.popular
                                ? 'bg-amber-100 text-[#F5A000]'
                                : 'bg-purple-100 text-purple-700'"
                        >
                            {{ packMeta[pack.slug].badge }}
                        </div>

                        <!-- Card header -->
                        <div class="p-6 pb-4">
                            <div
                                class="w-12 h-12 rounded-2xl bg-gradient-to-br flex items-center justify-center mb-4"
                                :class="packMeta[pack.slug]?.color ?? 'from-gray-400 to-gray-600'"
                            >
                                <Sparkles class="w-6 h-6 text-white" />
                            </div>

                            <h3 class="text-xl font-black text-[#1A1A1A] mb-1">{{ pack.label }}</h3>

                            <div class="flex items-end gap-1 mb-4">
                                <span class="text-4xl font-black text-[#1A1A1A]">{{ formatPrice(pack.price) }}</span>
                                <span class="text-sm text-[#555555] mb-1">one-time</span>
                            </div>

                            <!-- Features -->
                            <ul class="space-y-2.5">
                                <li class="flex items-center gap-2.5 text-sm text-[#555555]">
                                    <div class="w-4 h-4 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                        <Check class="w-2.5 h-2.5 text-[#F5A000]" />
                                    </div>
                                    <span><strong class="text-[#1A1A1A]">{{ pack.stories_count }} {{ pack.stories_count === 1 ? 'story' : 'stories' }}</strong></span>
                                </li>
                                <li class="flex items-center gap-2.5 text-sm text-[#555555]">
                                    <div class="w-4 h-4 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                        <Check class="w-2.5 h-2.5 text-[#F5A000]" />
                                    </div>
                                    <span><strong class="text-[#1A1A1A]">{{ pack.episode_limit }} episodes</strong> per story</span>
                                </li>
                                <li class="flex items-center gap-2.5 text-sm text-[#555555]">
                                    <div class="w-4 h-4 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                        <Check class="w-2.5 h-2.5 text-[#F5A000]" />
                                    </div>
                                    <span><strong class="text-[#1A1A1A]">{{ pack.revision_credits }} revision</strong> credits</span>
                                </li>
                                <li class="flex items-center gap-2.5 text-sm text-[#555555]">
                                    <div class="w-4 h-4 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                        <Check class="w-2.5 h-2.5 text-[#F5A000]" />
                                    </div>
                                    <span>Never expires</span>
                                </li>
                            </ul>
                        </div>

                        <!-- CTA -->
                        <div class="p-6 pt-0 mt-auto">
                            <button
                                type="button"
                                :disabled="!!loadingPackId"
                                @click="buy(pack)"
                                class="w-full flex items-center justify-center gap-2 py-3 rounded-xl font-bold text-sm transition-all duration-200 cursor-pointer"
                                :class="[
                                    packMeta[pack.slug]?.popular
                                        ? 'bg-gradient-to-r from-[#FFC837] to-[#F5A000] text-[#1A1A1A]'
                                        : 'bg-[#1A1A1A] text-white hover:bg-[#333]',
                                    loadingPackId ? 'opacity-60 cursor-not-allowed' : ''
                                ]"
                            >
                                <template v-if="loadingPackId === pack.id">
                                    <span class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin" />
                                    Redirecting…
                                </template>
                                <template v-else>
                                    Buy {{ pack.label }} Pack
                                    <ArrowRight class="w-4 h-4" />
                                </template>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Trust note -->
                <p class="text-center text-xs text-[#AAAAAA] mt-8">
                    Secure checkout via Stripe · No subscription · Cancel anytime
                </p>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
