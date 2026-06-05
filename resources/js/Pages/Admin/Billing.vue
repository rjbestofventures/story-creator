<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Zap, TrendingUp, BookOpen, Users } from '@lucide/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    totals:  Object,
    models:  Object,
    perUser: Array,
});

const fmt = (n) => Number(n ?? 0).toLocaleString();

const totalTokens = computed(() =>
    props.totals.gen_input + props.totals.gen_output +
    props.totals.int_input + props.totals.int_output
);

const modelLabel = (id) => {
    if (!id) return '—';
    if (id.includes('haiku'))  return 'Haiku';
    if (id.includes('sonnet')) return 'Sonnet';
    if (id.includes('opus'))   return 'Opus';
    return id;
};
</script>

<template>
    <AdminLayout>
        <Head title="Usage & Billing — Admin" />

        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-lg font-black text-[#1A1A1A]">Usage & Billing</h1>
                <p class="text-xs mt-0.5 text-muted-foreground">Claude API token consumption across all users and stories.</p>
            </div>
        </div>

        <!-- Model pills -->
        <div class="flex items-center gap-3 mb-5">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs" style="background:#F5F5F5; border:1px solid #DDDDDD;">
                <span class="font-semibold text-[#555555]">Interview model:</span>
                <span class="font-bold text-[#1A1A1A]">{{ modelLabel(models.interview) }}</span>
                <span class="text-muted-foreground">${{ models.int_price_input }} / ${{ models.int_price_output }} per 1M</span>
            </div>
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs" style="background:#F5F5F5; border:1px solid #DDDDDD;">
                <span class="font-semibold text-[#555555]">Generation model:</span>
                <span class="font-bold text-[#1A1A1A]">{{ modelLabel(models.generation) }}</span>
                <span class="text-muted-foreground">${{ models.gen_price_input }} / ${{ models.gen_price_output }} per 1M</span>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-4" style="border: 1px solid #DDDDDD;">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center">
                        <Zap class="w-3.5 h-3.5 text-[#F5A000]" />
                    </div>
                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Total Tokens</p>
                </div>
                <p class="text-2xl font-black text-[#1A1A1A]">{{ fmt(totalTokens) }}</p>
                <p class="text-[10px] text-muted-foreground mt-0.5">interview + generation</p>
            </div>

            <div class="bg-white rounded-2xl p-4" style="border: 1px solid #DDDDDD;">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center">
                        <TrendingUp class="w-3.5 h-3.5 text-blue-500" />
                    </div>
                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Interview Tokens</p>
                </div>
                <p class="text-2xl font-black text-[#1A1A1A]">{{ fmt(totals.int_input + totals.int_output) }}</p>
                <p class="text-[10px] text-muted-foreground mt-0.5">{{ modelLabel(models.interview) }}</p>
            </div>

            <div class="bg-white rounded-2xl p-4" style="border: 1px solid #DDDDDD;">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 flex items-center justify-center">
                        <TrendingUp class="w-3.5 h-3.5 text-purple-500" />
                    </div>
                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Generation Tokens</p>
                </div>
                <p class="text-2xl font-black text-[#1A1A1A]">{{ fmt(totals.gen_input + totals.gen_output) }}</p>
                <p class="text-[10px] text-muted-foreground mt-0.5">{{ modelLabel(models.generation) }}</p>
            </div>

            <div class="bg-white rounded-2xl p-4" style="border: 1px solid #DDDDDD;">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <BookOpen class="w-3.5 h-3.5 text-emerald-500" />
                    </div>
                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Est. API Cost</p>
                </div>
                <p class="text-2xl font-black text-[#1A1A1A]">${{ totals.total_cost }}</p>
                <p class="text-[10px] text-muted-foreground mt-0.5">{{ fmt(totals.total_stories) }} stories</p>
            </div>
        </div>

        <!-- Per-user breakdown -->
        <div class="bg-white rounded-2xl overflow-hidden" style="border: 1px solid #DDDDDD;">
            <div class="px-5 py-4" style="border-bottom: 1px solid #EBEBEB; background-color: #FAFAF8;">
                <div class="flex items-center gap-2">
                    <Users class="w-4 h-4 text-muted-foreground" />
                    <p class="text-sm font-bold text-[#1A1A1A]">Per-User Breakdown</p>
                </div>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr style="border-bottom: 1px solid #EBEBEB;">
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider">User</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider hidden sm:table-cell">Stories</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider hidden md:table-cell">Interview Tokens</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider hidden md:table-cell">Generation Tokens</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider">Est. Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in perUser"
                        :key="user.id"
                        class="hover:bg-[#FAFAF8] transition-colors"
                        style="border-bottom: 1px solid #F5F5F5;"
                    >
                        <td class="px-5 py-3.5">
                            <p class="font-semibold text-[#1A1A1A] text-sm">{{ user.name }}</p>
                            <p class="text-[10px] text-muted-foreground">{{ user.email }}</p>
                        </td>
                        <td class="px-5 py-3.5 hidden sm:table-cell">
                            <span class="text-xs font-semibold text-[#1A1A1A]">{{ user.stories_count }}</span>
                        </td>
                        <td class="px-5 py-3.5 hidden md:table-cell">
                            <span class="text-xs text-[#555555]">{{ fmt(user.int_input + user.int_output) }}</span>
                        </td>
                        <td class="px-5 py-3.5 hidden md:table-cell">
                            <span class="text-xs text-[#555555]">{{ fmt(user.gen_input + user.gen_output) }}</span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="text-xs font-bold text-[#1A1A1A]">${{ user.cost }}</span>
                        </td>
                    </tr>

                    <tr v-if="perUser.length === 0">
                        <td colspan="5" class="px-5 py-16 text-center">
                            <Zap class="w-8 h-8 mx-auto mb-3 text-muted-foreground opacity-40" />
                            <p class="text-sm font-semibold text-[#1A1A1A]">No usage data yet</p>
                            <p class="text-xs mt-1 text-muted-foreground">Token usage will appear here once users start generating stories.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-[10px] text-muted-foreground mt-3">
            Estimated costs use Anthropic list prices for the currently configured models. Prices shown as input/output per 1M tokens.
        </p>
    </AdminLayout>
</template>
