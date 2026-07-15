<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Receipt, ArrowLeft } from 'lucide-vue-next';

defineProps({
    purchases: Array,
});

const formatAmount = (cents) => {
    if (cents === null || cents === undefined) return '—';
    return `$${(cents / 100).toFixed(2)}`;
};

const sourceLabel = (source) => (source === 'grant' ? 'Granted by admin' : 'Purchased online');
</script>

<template>
    <Head title="Billing & Packs" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#FAFAF8]">

            <!-- Page header -->
            <div class="bg-white border-b border-[#DDDDDD] px-4 md:px-8 py-5">
                <div class="max-w-2xl mx-auto flex items-center gap-3">
                    <Link :href="route('profile.edit')" class="text-[#555555] hover:text-[#1A1A1A] transition-colors">
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <h1 class="text-lg font-black text-[#1A1A1A]">Billing & Packs</h1>
                        <p class="text-xs text-[#555555] mt-0.5">Packs you've bought and your payment history</p>
                    </div>
                </div>
            </div>

            <div class="max-w-2xl mx-auto px-4 md:px-8 py-8">
                <div class="bg-white rounded-2xl border border-[#DDDDDD] overflow-hidden">
                    <div
                        v-for="purchase in purchases"
                        :key="purchase.id"
                        class="flex items-center gap-4 px-6 py-4"
                        :class="purchase !== purchases[purchases.length - 1] ? 'border-b border-[#F0F0F0]' : ''"
                    >
                        <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                            <Receipt class="w-4 h-4 text-[#F5A000]" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-[#1A1A1A] truncate">{{ purchase.pack_label }}</p>
                            <p class="text-xs text-[#555555] mt-0.5">
                                {{ sourceLabel(purchase.source) }} · {{ purchase.credits_granted }} credits · {{ purchase.purchased_at }}
                            </p>
                        </div>
                        <p class="text-sm font-bold text-[#1A1A1A] shrink-0">{{ formatAmount(purchase.amount_paid) }}</p>
                    </div>

                    <!-- Empty -->
                    <div v-if="purchases.length === 0" class="py-16 text-center">
                        <Receipt class="w-8 h-8 mx-auto mb-3 text-[#AAAAAA]" />
                        <p class="text-sm font-semibold text-[#1A1A1A]">No purchases yet</p>
                        <p class="text-xs mt-1 text-[#555555]">Packs you buy or are granted will show up here.</p>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
