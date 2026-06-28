<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter
} from '@/Components/ui/dialog';
import {
    Tooltip, TooltipContent, TooltipProvider, TooltipTrigger,
} from '@/Components/ui/tooltip';
import {
    Sparkles, BookOpen, Plus, Trash2, ChevronRight,
    Zap, Calendar, FileText, MessageSquare, Clock, ShoppingBag, CircleHelp
} from 'lucide-vue-next';

const props = defineProps({
    stories:             Array,
    profile:   Object,
    credits:   { type: Number, default: null },
    isAdmin:   Boolean,
    adminRole: String,
});

const creditBalance  = computed(() => props.credits ?? 0);
// Smallest story is 12 episodes (1 credit each), so below 12 a new story can't be afforded.
const MIN_STORY_CREDITS = 12;
const canCreateStory = computed(() => props.isAdmin || creditBalance.value >= MIN_STORY_CREDITS);

// Format labels
const formatLabel = {
    social:   'Social Media',
    linkedin: 'LinkedIn',
    blog:     'Blog',
};

const formatColor = {
    social:   'bg-blue-50 text-blue-700 border-blue-200',
    linkedin: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    blog:     'bg-emerald-50 text-emerald-700 border-emerald-200',
};

// Delete dialog
const deletingStory = ref(null);
const deleteOpen    = ref(false);

const openDelete = (story) => {
    deletingStory.value = story;
    deleteOpen.value    = true;
};

const deleteForm = useForm({});
const confirmDelete = () => {
    deleteForm.delete(route('stories.destroy', deletingStory.value.id), {
        onFinish: () => { deleteOpen.value = false; deletingStory.value = null; },
    });
};
</script>

<template>
    <Head title="My Stories" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#FAFAF8]">

            <!-- Header -->
            <div class="bg-white border-b border-[#DDDDDD] px-4 md:px-8 py-5">
                <div class="max-w-4xl mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                            <Sparkles class="w-5 h-5 text-[#F5A000]" />
                        </div>
                        <div>
                            <h1 class="text-lg font-black text-[#1A1A1A]">My Stories</h1>
                            <p class="text-xs text-[#555555]">{{ stories.length }} {{ stories.length === 1 ? 'story' : 'stories' }} generated</p>
                        </div>
                    </div>

                    <!-- Has credits (or admin): create a story -->
                    <Link v-if="canCreateStory" :href="route('stories.create')">
                        <Button class="flex items-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-10 px-5 rounded-xl transition-all duration-300 cursor-pointer">
                            <Plus class="w-4 h-4" />
                            + New Story
                        </Button>
                    </Link>

                    <!-- Out of credits: buy more -->
                    <Link v-else :href="route('shop.index')">
                        <Button class="flex items-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-10 px-5 rounded-xl transition-all duration-300 cursor-pointer">
                            <ShoppingBag class="w-4 h-4" />
                            Buy StoryBot Credits
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="max-w-4xl mx-auto px-4 md:px-8 py-6 space-y-6">

                <!-- Stats row -->
                <div class="grid grid-cols-1 gap-4">
                    <!-- StoryBot credits -->
                    <div class="bg-white rounded-2xl border border-[#DDDDDD] p-4 flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <Zap class="w-4 h-4 text-[#F5A000]" />
                                <span class="text-xs font-semibold text-[#555555] uppercase tracking-wide">StoryBot Credits</span>
                                <TooltipProvider>
                                    <Tooltip :delay-duration="100">
                                        <TooltipTrigger as-child>
                                            <CircleHelp class="w-3.5 h-3.5 text-[#AAAAAA] hover:text-[#F5A000] cursor-help transition-colors" />
                                        </TooltipTrigger>
                                        <TooltipContent side="bottom" class="max-w-xs p-3">
                                            <p class="text-xs leading-relaxed">
                                                Credits power everything. <strong>1 credit generates 1 episode</strong>, and
                                                <strong>1 credit refines or redoes</strong> an episode. Choose 12, 18, or 24 episodes per story.
                                                Credits never expire.
                                            </p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                            <div class="text-2xl font-black text-[#1A1A1A]">{{ isAdmin ? '∞' : creditBalance }}</div>
                            <div class="text-xs text-[#555555] mt-0.5">1 credit = 1 episode (generate or refine)</div>
                        </div>
                        <Link v-if="!isAdmin" :href="route('shop.index')">
                            <Button class="flex items-center gap-2 bg-white border border-[#DDDDDD] hover:border-[#F5A000] text-[#1A1A1A] font-bold h-10 px-4 rounded-xl transition-all duration-200 cursor-pointer">
                                <ShoppingBag class="w-4 h-4 text-[#F5A000]" />
                                Buy StoryBot Credits
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Empty state: no credits → buy first -->
                <div
                    v-if="stories.length === 0 && !canCreateStory"
                    class="bg-white rounded-2xl border border-[#DDDDDD] p-12 text-center"
                >
                    <div class="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <ShoppingBag class="w-8 h-8 text-[#F5A000]" />
                    </div>
                    <h2 class="text-xl font-black text-[#1A1A1A] mb-2">Get your first story pack</h2>
                    <p class="text-[#555555] mb-6 max-w-sm mx-auto">
                        Story packs let you create professional content from a quick interview. Pick a pack to get started — credits never expire.
                    </p>
                    <Link :href="route('shop.index')">
                        <Button
                            class="inline-flex items-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-11 px-8 rounded-xl transition-all duration-300 cursor-pointer"
                        >
                            <ShoppingBag class="w-4 h-4" />
                            Buy StoryBot Credits
                        </Button>
                    </Link>
                </div>

                <!-- Empty state: has credits → create -->
                <div
                    v-else-if="stories.length === 0"
                    class="bg-white rounded-2xl border border-[#DDDDDD] p-12 text-center"
                >
                    <div class="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <BookOpen class="w-8 h-8 text-[#F5A000]" />
                    </div>
                    <h2 class="text-xl font-black text-[#1A1A1A] mb-2">No stories yet</h2>
                    <p class="text-[#555555] mb-6 max-w-sm mx-auto">
                        Answer 3 questions about your business and we'll generate your first story episodes in seconds.
                    </p>
                    <Link :href="route('stories.create')">
                        <Button
                            class="inline-flex items-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-11 px-8 rounded-xl transition-all duration-300 cursor-pointer"
                        >
                            <Sparkles class="w-4 h-4" />
                            Create My First Story
                        </Button>
                    </Link>
                </div>

                <!-- Story cards -->
                <div v-else class="space-y-4">
                    <div
                        v-for="story in stories"
                        :key="story.id"
                        class="bg-white rounded-2xl border border-[#DDDDDD] hover:border-[#F5A000]/40 hover:shadow-sm transition-all duration-200 group"
                    >
                        <div class="p-5 flex items-start gap-4">

                            <!-- Icon -->
                            <div
                                class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center"
                                :class="story.status === 'interviewing' ? 'bg-blue-50' : story.status === 'interview_complete' ? 'bg-purple-50' : 'bg-amber-50'"
                            >
                                <MessageSquare v-if="story.status === 'interviewing'" class="w-5 h-5 text-blue-500" />
                                <Clock v-else-if="story.status === 'interview_complete'" class="w-5 h-5 text-purple-500" />
                                <FileText v-else class="w-5 h-5 text-[#F5A000]" />
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-bold text-[#1A1A1A] truncate">
                                                {{ story.title || story.business_profile?.business_name || 'Untitled Story' }}
                                            </h3>
                                            <span
                                                v-if="story.is_demo"
                                                class="flex-shrink-0 text-xs font-bold px-1.5 py-0.5 rounded-md bg-amber-100 text-[#F5A000] border border-amber-200"
                                            >Demo</span>
                                        </div>
                                        <p class="text-sm text-[#555555] mt-0.5">
                                            {{ story.business_profile?.business_name }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <!-- Status badge for in-progress -->
                                        <span
                                            v-if="story.status === 'interviewing'"
                                            class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-200"
                                        >
                                            In Progress
                                        </span>
                                        <span
                                            v-else-if="story.status === 'interview_complete'"
                                            class="text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-50 text-purple-600 border border-purple-200"
                                        >
                                            Ready to Generate
                                        </span>
                                        <Badge
                                            v-else-if="story.episodes?.[0]?.format"
                                            :class="formatColor[story.episodes[0].format]"
                                            class="text-xs font-semibold border"
                                        >
                                            {{ formatLabel[story.episodes[0].format] ?? story.episodes[0].format }}
                                        </Badge>
                                    </div>
                                </div>

                                <!-- Meta row -->
                                <div class="flex items-center gap-4 mt-3">
                                    <div v-if="story.status === 'draft'" class="flex items-center gap-1.5 text-xs text-[#555555]">
                                        <BookOpen class="w-3.5 h-3.5" />
                                        {{ story.episodes_count }} {{ story.episodes_count === 1 ? 'episode' : 'episodes' }}
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-[#555555]">
                                        <Calendar class="w-3.5 h-3.5" />
                                        {{ new Date(story.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button
                                    type="button"
                                    @click="openDelete(story)"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-[#AAAAAA] hover:text-red-500 hover:bg-red-50 transition-all duration-150 cursor-pointer"
                                    title="Delete story"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                                <!-- Resume link for in-progress -->
                                <Link
                                    v-if="story.status === 'interviewing' || story.status === 'interview_complete'"
                                    :href="route('stories.resume', story.id)"
                                    class="flex items-center gap-1.5 text-xs font-semibold text-white px-3 h-8 rounded-lg bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br transition-all duration-150 cursor-pointer"
                                >
                                    Resume
                                </Link>
                                <Link
                                    v-else
                                    :href="route('stories.show', story.id)"
                                    class="flex items-center gap-1.5 text-xs font-semibold text-[#555555] hover:text-[#F5A000] px-3 h-8 rounded-lg hover:bg-amber-50 transition-all duration-150 cursor-pointer"
                                >
                                    View
                                    <ChevronRight class="w-3.5 h-3.5" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Out of credits notice (non-admin users only) -->
                <div
                    v-if="!isAdmin && creditBalance === 0 && stories.length > 0"
                    class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start justify-between gap-4"
                >
                    <div class="flex items-start gap-3">
                        <Zap class="w-5 h-5 text-[#F5A000] flex-shrink-0 mt-0.5" />
                        <div>
                            <p class="text-sm font-semibold text-[#1A1A1A]">You're out of StoryBot credits</p>
                            <p class="text-sm text-[#555555] mt-0.5">Buy StoryBot credits to generate or refine more episodes.</p>
                        </div>
                    </div>
                    <Link :href="route('shop.index')" class="shrink-0">
                        <Button class="text-xs font-bold h-9 px-4 rounded-lg bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-[#1A1A1A] border-0">
                            Buy More StoryBot Credits
                        </Button>
                    </Link>
                </div>

            </div>

        </div>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="deleteOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="text-[#1A1A1A]">Delete story?</DialogTitle>
                    <DialogDescription class="text-[#555555]">
                        "<span class="font-semibold text-[#1A1A1A]">{{ deletingStory?.title }}</span>"
                        and all its episodes will be permanently deleted. This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="deleteOpen = false" class="cursor-pointer">Cancel</Button>
                    <Button
                        :disabled="deleteForm.processing"
                        @click="confirmDelete"
                        class="bg-red-500 hover:bg-red-600 text-white cursor-pointer"
                    >
                        Delete Story
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AuthenticatedLayout>
</template>
