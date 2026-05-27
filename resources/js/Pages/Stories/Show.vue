<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter
} from '@/Components/ui/dialog';
import {
    ArrowLeft, RefreshCcw, Copy, Check, Sparkles,
    BookOpen, ChevronLeft, ChevronRight, Loader2
} from 'lucide-vue-next';

const props = defineProps({
    story: Object,
});

const episodes  = computed(() => props.story.episodes ?? []);
const activeIdx = ref(0);
const active    = computed(() => episodes.value[activeIdx.value] ?? null);

const copiedIdx = ref(null);
const copyText = async (text, idx) => {
    await navigator.clipboard.writeText(text);
    copiedIdx.value = idx;
    setTimeout(() => { copiedIdx.value = null; }, 2000);
};

// Regenerate
const regenOpen    = ref(false);
const regenForm    = useForm({ episode_number: null });

const openRegen = (ep) => {
    regenForm.episode_number = ep.episode_number;
    regenOpen.value = true;
};

const confirmRegen = () => {
    regenForm.post(route('stories.regenerate', props.story.id), {
        onSuccess: () => { regenOpen.value = false; },
        onFinish:  () => { regenOpen.value = false; },
    });
};

const formatLabel = {
    social:   'Social Post',
    linkedin: 'LinkedIn',
    blog:     'Blog',
};

const formatColor = {
    social:   'bg-blue-50 text-blue-700 border-blue-200',
    linkedin: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    blog:     'bg-emerald-50 text-emerald-700 border-emerald-200',
};
</script>

<template>
    <Head :title="story.title" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#FAFAF8]">

            <!-- Top bar -->
            <div class="bg-white border-b border-[#DDDDDD] px-4 md:px-8 py-4">
                <div class="max-w-5xl mx-auto flex items-center justify-between">
                    <Link
                        :href="route('stories.index')"
                        class="flex items-center gap-2 text-sm text-[#555555] hover:text-[#1A1A1A] transition-colors cursor-pointer"
                    >
                        <ArrowLeft class="w-4 h-4" />
                        My Stories
                    </Link>
                    <div class="flex items-center gap-2">
                        <Sparkles class="w-4 h-4 text-[#F5A000]" />
                        <span class="text-sm font-semibold text-[#1A1A1A] truncate max-w-xs">{{ story.title }}</span>
                    </div>
                    <div class="w-24" />
                </div>
            </div>

            <div class="max-w-5xl mx-auto px-4 md:px-8 py-6">
                <div class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6">

                    <!-- Episode list sidebar -->
                    <div class="space-y-2">
                        <p class="text-xs font-semibold text-[#555555] uppercase tracking-wide px-1 mb-3">
                            {{ episodes.length }} Episodes
                        </p>
                        <button
                            v-for="(ep, i) in episodes"
                            :key="ep.id"
                            type="button"
                            @click="activeIdx = i"
                            class="w-full text-left p-3.5 rounded-xl border-2 transition-all duration-150 cursor-pointer"
                            :class="activeIdx === i
                                ? 'border-[#F5A000] bg-amber-50'
                                : 'border-[#DDDDDD] bg-white hover:border-[#F5A000]/40'"
                        >
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    class="text-xs font-black px-1.5 py-0.5 rounded-md"
                                    :class="activeIdx === i
                                        ? 'bg-[#F5A000] text-white'
                                        : 'bg-gray-100 text-[#555555]'"
                                >
                                    Ep {{ ep.episode_number }}
                                </span>
                            </div>
                            <p class="text-sm font-semibold text-[#1A1A1A] leading-snug line-clamp-2">
                                {{ ep.title }}
                            </p>
                        </button>
                    </div>

                    <!-- Active episode -->
                    <div v-if="active" class="bg-white rounded-2xl border border-[#DDDDDD] flex flex-col">

                        <!-- Episode header -->
                        <div class="flex items-start justify-between gap-4 p-6 border-b border-[#DDDDDD]">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-black bg-[#F5A000] text-white px-2 py-0.5 rounded-md">
                                        Episode {{ active.episode_number }}
                                    </span>
                                    <Badge
                                        :class="formatColor[active.format]"
                                        class="text-xs font-semibold border"
                                    >
                                        {{ formatLabel[active.format] ?? active.format }}
                                    </Badge>
                                </div>
                                <h2 class="text-xl font-black text-[#1A1A1A]">{{ active.title }}</h2>
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button
                                    type="button"
                                    @click="openRegen(active)"
                                    title="Regenerate this episode"
                                    class="flex items-center gap-1.5 text-xs font-semibold text-[#555555] hover:text-[#F5A000] px-3 h-8 rounded-lg hover:bg-amber-50 border border-[#DDDDDD] hover:border-[#F5A000]/40 transition-all duration-150 cursor-pointer"
                                >
                                    <RefreshCcw class="w-3.5 h-3.5" />
                                    Regenerate
                                </button>
                                <button
                                    type="button"
                                    @click="copyText(active.content, active.id)"
                                    title="Copy to clipboard"
                                    class="flex items-center gap-1.5 text-xs font-semibold text-white px-3 h-8 rounded-lg bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br transition-all duration-300 cursor-pointer"
                                >
                                    <Check v-if="copiedIdx === active.id" class="w-3.5 h-3.5" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    {{ copiedIdx === active.id ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                        </div>

                        <!-- Episode content -->
                        <div class="flex-1 p-6">
                            <div class="prose prose-sm max-w-none text-[#1A1A1A] leading-relaxed whitespace-pre-wrap font-[inherit]">
                                {{ active.content }}
                            </div>
                        </div>

                        <!-- Episode navigation footer -->
                        <div class="flex items-center justify-between px-6 py-4 border-t border-[#DDDDDD]">
                            <button
                                type="button"
                                :disabled="activeIdx === 0"
                                @click="activeIdx--"
                                class="flex items-center gap-1.5 text-sm font-semibold text-[#555555] disabled:opacity-30 hover:text-[#1A1A1A] transition-colors cursor-pointer disabled:cursor-default"
                            >
                                <ChevronLeft class="w-4 h-4" />
                                Previous
                            </button>
                            <span class="text-xs text-[#AAAAAA]">
                                {{ activeIdx + 1 }} / {{ episodes.length }}
                            </span>
                            <button
                                type="button"
                                :disabled="activeIdx === episodes.length - 1"
                                @click="activeIdx++"
                                class="flex items-center gap-1.5 text-sm font-semibold text-[#555555] disabled:opacity-30 hover:text-[#1A1A1A] transition-colors cursor-pointer disabled:cursor-default"
                            >
                                Next
                                <ChevronRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- Regenerate confirmation dialog -->
        <Dialog v-model:open="regenOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="text-[#1A1A1A]">Regenerate episode?</DialogTitle>
                    <DialogDescription class="text-[#555555]">
                        This will replace Episode {{ regenForm.episode_number }} with a new AI-generated version.
                        This uses 1 refine credit and cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="regenOpen = false" class="cursor-pointer">Cancel</Button>
                    <Button
                        :disabled="regenForm.processing"
                        @click="confirmRegen"
                        class="flex items-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold cursor-pointer transition-all duration-300"
                    >
                        <Loader2 v-if="regenForm.processing" class="w-4 h-4 animate-spin" />
                        <RefreshCcw v-else class="w-4 h-4" />
                        {{ regenForm.processing ? 'Regenerating...' : 'Yes, Regenerate' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AuthenticatedLayout>
</template>
