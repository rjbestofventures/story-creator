<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter
} from '@/Components/ui/dialog';
import {
    ArrowLeft, Copy, Check, RefreshCcw, Sparkles, Loader2, Plus
} from 'lucide-vue-next';

const props = defineProps({
    story: Object,
});

const episodes = props.story.episodes ?? [];
const businessName = props.story.business_profile?.business_name ?? 'Your Business';

const formatLabel = { social: 'Social Post', linkedin: 'LinkedIn', blog: 'Blog' };
const formatColor = {
    social:   'bg-blue-50 text-blue-700 border-blue-200',
    linkedin: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    blog:     'bg-emerald-50 text-emerald-700 border-emerald-200',
};

// ─── Copy ─────────────────────────────────────────────────────────────────────
const copied = ref(null);
const copyEpisode = async (ep) => {
    await navigator.clipboard.writeText(ep.content);
    copied.value = ep.id;
    setTimeout(() => { copied.value = null; }, 2000);
};

// ─── Regenerate ───────────────────────────────────────────────────────────────
const regenOpen    = ref(false);
const regenTarget  = ref(null);
const regenForm    = useForm({ episode_number: null });

const openRegen = (ep) => {
    regenTarget.value = ep;
    regenForm.episode_number = ep.episode_number;
    regenOpen.value = true;
};

const confirmRegen = () => {
    regenForm.post(route('stories.regenerate', props.story.id), {
        onSuccess: () => { regenOpen.value = false; regenTarget.value = null; },
        onFinish:  () => { regenOpen.value = false; },
    });
};
</script>

<template>
    <Head :title="`The Story of ${businessName}`" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#FAFAF8]">

            <!-- Top bar -->
            <div class="bg-white border-b border-[#DDDDDD] px-4 md:px-8 py-4">
                <div class="max-w-3xl mx-auto flex items-center justify-between">
                    <Link
                        :href="route('stories.index')"
                        class="flex items-center gap-2 text-sm text-[#555555] hover:text-[#1A1A1A] transition-colors cursor-pointer"
                    >
                        <ArrowLeft class="w-4 h-4" />
                        My Stories
                    </Link>
                    <Link :href="route('stories.create')">
                        <Button
                            class="flex items-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-9 px-4 rounded-xl text-sm transition-all duration-300 cursor-pointer"
                        >
                            <Plus class="w-3.5 h-3.5" />
                            New Story
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="max-w-3xl mx-auto px-4 md:px-8 py-10">

                <!-- Story header -->
                <div class="mb-10 text-center">
                    <div class="inline-flex items-center gap-2 text-xs font-semibold text-[#F5A000] uppercase tracking-widest mb-3">
                        <Sparkles class="w-3.5 h-3.5" />
                        AI Generated Story
                    </div>
                    <h1 class="text-3xl md:text-4xl font-black text-[#1A1A1A] mb-3">
                        The Story of {{ businessName }}
                    </h1>
                    <p class="text-[#555555] text-lg">
                        Here's what StoryCreator generated from your interview.
                        <span class="text-[#F5A000] font-semibold">{{ episodes.length }} episodes</span> ready to publish.
                    </p>
                </div>

                <!-- Episodes — stacked as full articles -->
                <div class="space-y-6">
                    <article
                        v-for="ep in episodes"
                        :key="ep.id"
                        class="bg-white rounded-2xl border border-[#DDDDDD] hover:border-[#F5A000]/30 hover:shadow-sm transition-all duration-200 overflow-hidden"
                    >
                        <!-- Episode header -->
                        <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-[#F5F5F5]">
                            <div class="flex items-center gap-3">
                                <span class="flex-shrink-0 text-xs font-black bg-[#F5A000] text-white px-2.5 py-1 rounded-lg">
                                    Episode {{ ep.episode_number }}
                                </span>
                                <Badge
                                    :class="formatColor[ep.format]"
                                    class="text-xs font-semibold border"
                                >
                                    {{ formatLabel[ep.format] ?? ep.format }}
                                </Badge>
                            </div>
                            <!-- Actions -->
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button
                                    type="button"
                                    @click="openRegen(ep)"
                                    class="flex items-center gap-1.5 text-xs font-semibold text-[#555555] hover:text-[#F5A000] px-3 h-8 rounded-lg border border-[#DDDDDD] hover:border-[#F5A000]/40 hover:bg-amber-50 transition-all duration-150 cursor-pointer"
                                >
                                    <RefreshCcw class="w-3.5 h-3.5" />
                                    <span class="hidden sm:inline">Regenerate</span>
                                </button>
                                <button
                                    type="button"
                                    @click="copyEpisode(ep)"
                                    class="flex items-center gap-1.5 text-xs font-semibold text-white px-3 h-8 rounded-lg bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br transition-all duration-300 cursor-pointer"
                                >
                                    <Check v-if="copied === ep.id" class="w-3.5 h-3.5" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    {{ copied === ep.id ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                        </div>

                        <!-- Episode title + content -->
                        <div class="px-6 py-5">
                            <h2 class="text-xl font-black text-[#1A1A1A] mb-4">
                                {{ ep.title }}
                            </h2>
                            <div class="text-[#333333] text-[15px] leading-[1.8] whitespace-pre-wrap">
                                {{ ep.content }}
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Bottom CTA -->
                <div class="mt-12 bg-white rounded-2xl border border-[#DDDDDD] p-8 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-amber-50 mb-4">
                        <Sparkles class="w-6 h-6 text-[#F5A000]" />
                    </div>
                    <h3 class="text-xl font-black text-[#1A1A1A] mb-2">Ready to tell your next story?</h3>
                    <p class="text-[#555555] mb-6 max-w-md mx-auto">
                        Each story builds your brand's narrative. Start a new interview to explore a different angle of your business.
                    </p>
                    <Link :href="route('stories.create')">
                        <Button
                            class="inline-flex items-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-11 px-8 rounded-xl transition-all duration-300 cursor-pointer"
                        >
                            <Plus class="w-4 h-4" />
                            Create Another Story
                        </Button>
                    </Link>
                </div>

            </div>
        </div>

        <!-- Regenerate dialog -->
        <Dialog v-model:open="regenOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="text-[#1A1A1A]">Regenerate episode?</DialogTitle>
                    <DialogDescription class="text-[#555555]">
                        Episode {{ regenTarget?.episode_number }} —
                        "<span class="font-semibold text-[#1A1A1A]">{{ regenTarget?.title }}</span>"
                        will be replaced with a new AI-generated version. This uses 1 refine credit.
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
                        {{ regenForm.processing ? 'Regenerating…' : 'Yes, Regenerate' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AuthenticatedLayout>
</template>
