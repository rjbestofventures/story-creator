<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter
} from '@/Components/ui/dialog';
import {
    ArrowLeft, Copy, Check, Sparkles, Loader2, Plus,
    Wand2, ChevronLeft, ChevronRight, RotateCcw, ArrowRight
} from 'lucide-vue-next';

const props = defineProps({
    story: Object,
});

const isDemo       = props.story.is_demo ?? false;
const episodes     = ref(props.story.episodes ?? []);
const businessName = props.story.business_profile?.business_name ?? 'Your Business';

// ─── Generating state + polling ───────────────────────────────────────────────
const isGenerating = computed(() => props.story.status === 'generating');
let pollTimer = null;

const startPolling = () => {
    pollTimer = setInterval(async () => {
        try {
            const res  = await fetch(route('stories.status', props.story.id), {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            if (data.status !== 'generating') {
                clearInterval(pollTimer);
                router.reload({ only: ['story'] });
            }
        } catch {
            // network blip — keep polling
        }
    }, 3000);
};

onMounted(() => { if (props.story.status === 'generating') startPolling(); });
onUnmounted(() => { if (pollTimer) clearInterval(pollTimer); });

const formatLabel = { social: 'Social Post', linkedin: 'LinkedIn', blog: 'Blog' };
const formatColor = {
    social:   'bg-blue-50 text-blue-700 border-blue-200',
    linkedin: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    blog:     'bg-emerald-50 text-emerald-700 border-emerald-200',
};

// ─── Copy ─────────────────────────────────────────────────────────────────────
const copied = ref(null);
const copyEpisode = async (content) => {
    await navigator.clipboard.writeText(content);
    copied.value = content;
    setTimeout(() => { copied.value = null; }, 2000);
};

// ─── Revision state per episode ───────────────────────────────────────────────
// { [episodeId]: { position, versions, loading } }
// position counts from 1. total = versions_count + 1 (current is always last).
const revState = ref({});

const total    = (ep) => (ep.versions_count ?? 0) + 1;
const position = (ep) => revState.value[ep.id]?.position ?? total(ep);

const displayed = (ep) => {
    const state = revState.value[ep.id];
    const pos   = state?.position ?? total(ep);
    if (pos === total(ep) || !state?.versions) {
        return { title: ep.title, content: ep.content };
    }
    // history is newest-first from API, so index = (versions.length - pos)
    const v = state.versions[state.versions.length - pos];
    return v ? { title: v.title, content: v.content } : { title: ep.title, content: ep.content };
};

const isAtCurrent = (ep) => position(ep) === total(ep);

const loadVersions = async (ep) => {
    if (revState.value[ep.id]?.versions) return;
    revState.value[ep.id] = { ...revState.value[ep.id], loading: true };
    const res  = await fetch(route('stories.episode.versions', { story: props.story.id, episode: ep.id }), {
        headers: { Accept: 'application/json' },
    });
    const data = await res.json();
    revState.value[ep.id] = { ...revState.value[ep.id], versions: data.versions, loading: false };
};

const prevRevision = async (ep) => {
    const pos = position(ep);
    if (pos <= 1) return;
    await loadVersions(ep);
    revState.value[ep.id] = { ...revState.value[ep.id], position: pos - 1 };
};

const nextRevision = (ep) => {
    const pos = position(ep);
    if (pos >= total(ep)) return;
    revState.value[ep.id] = { ...revState.value[ep.id], position: pos + 1 };
};

// ─── Refine ───────────────────────────────────────────────────────────────────
const refineOpen   = ref(false);
const refineTarget = ref(null);
const refining     = ref(null); // stores episode ID being refined

const openRefine = (ep) => {
    refineTarget.value = ep;
    refineOpen.value   = true;
};

const confirmRefine = async () => {
    if (!refineTarget.value) return;
    refining.value = refineTarget.value.id;
    try {
        const res  = await fetch(route('stories.regenerate', props.story.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ episode_number: refineTarget.value.episode_number }),
        });
        const data = await res.json();
        const idx  = episodes.value.findIndex(e => e.id === data.episode.id);
        if (idx !== -1) {
            episodes.value[idx] = {
                ...episodes.value[idx],
                title:          data.episode.title,
                content:        data.episode.content,
                versions_count: (episodes.value[idx].versions_count ?? 0) + 1,
            };
        }
        // Reset to current revision and clear version cache
        revState.value[refineTarget.value.id] = { position: total(episodes.value[idx]), versions: null };
    } finally {
        refining.value     = null;
        refineOpen.value   = false;
        refineTarget.value = null;
    }
};

// ─── Restore ──────────────────────────────────────────────────────────────────
const restoring = ref(null);

const restoreRevision = async (ep) => {
    const state = revState.value[ep.id];
    const v     = state?.versions?.[state.versions.length - position(ep)];
    if (!v) return;

    restoring.value = ep.id;
    try {
        const res  = await fetch(route('stories.episode.restore', { story: props.story.id, episode: ep.id, version: v.id }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
        });
        const data = await res.json();
        const idx  = episodes.value.findIndex(e => e.id === ep.id);
        if (idx !== -1) {
            episodes.value[idx] = {
                ...episodes.value[idx],
                title:          data.episode.title,
                content:        data.episode.content,
                versions_count: (episodes.value[idx].versions_count ?? 0) + 1,
            };
        }
        // Go to current (latest)
        revState.value[ep.id] = { position: total(episodes.value[idx]), versions: null };
    } finally {
        restoring.value = null;
    }
};
</script>

<template>
    <Head :title="`The Story of ${businessName}`" />
    <AuthenticatedLayout>
        <!-- Generating overlay -->
        <div v-if="isGenerating" class="fixed inset-0 z-50 flex flex-col items-center justify-center gap-6" style="background: rgba(250,250,248,0.97);">
            <div class="flex flex-col items-center gap-4 text-center px-6">
                <Loader2 class="w-12 h-12 animate-spin" style="color: #F5A000;" />
                <div>
                    <p class="text-xl font-black mb-1" style="color: #1A1A1A;">Generating your story…</p>
                    <p class="text-sm" style="color: #555555;">This takes 15–30 seconds. You can wait here or come back later.</p>
                </div>
                <Link :href="route('stories.index')" class="text-sm underline mt-2" style="color: #555555;">
                    Go to My Stories
                </Link>
            </div>
        </div>

        <div class="min-h-screen bg-[#FAFAF8]">

            <!-- Top bar -->
            <div class="bg-white border-b border-[#DDDDDD] px-4 md:px-8 py-4">
                <div class="max-w-3xl mx-auto flex items-center justify-between">
                    <Link :href="route('stories.index')" class="flex items-center gap-2 text-sm text-[#555555] hover:text-[#1A1A1A] transition-colors cursor-pointer">
                        <ArrowLeft class="w-4 h-4" />
                        My Stories
                    </Link>
                    <Link :href="isDemo ? route('billing.plans') : route('stories.create')">
                        <Button class="flex items-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-9 px-4 rounded-xl text-sm transition-all duration-300 cursor-pointer">
                            <Sparkles v-if="isDemo" class="w-3.5 h-3.5" />
                            <Plus v-else class="w-3.5 h-3.5" />
                            {{ isDemo ? 'Create My Own Story' : 'New Story' }}
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Demo banner -->
            <div v-if="isDemo" class="bg-amber-50 border-b border-amber-200 px-4 md:px-8 py-3">
                <div class="max-w-3xl mx-auto flex items-center justify-between gap-4">
                    <p class="text-sm text-[#555555]">
                        <span class="font-semibold text-[#1A1A1A]">This is a demo story.</span>
                        It shows you exactly what StoryCreator.Bot generates with a Paid Subscription Story Generation.
                    </p>
                    <Link :href="route('billing.plans')" class="flex-shrink-0">
                        <Button class="flex items-center gap-1.5 text-xs font-bold h-8 px-3 rounded-lg bg-[#F5A000] hover:bg-[#e09600] text-white cursor-pointer transition-colors">
                            Create yours
                            <ArrowRight class="w-3 h-3" />
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="max-w-3xl mx-auto px-4 md:px-8 py-6 md:py-10">

                <!-- Story header -->
                <div class="mb-8 md:mb-10 text-center">
                    <div class="inline-flex items-center gap-2 text-xs font-semibold text-[#F5A000] uppercase tracking-widest mb-3">
                        <Sparkles class="w-3.5 h-3.5" />
                        AI Generated Story
                    </div>
                    <h1 class="text-2xl md:text-4xl font-black text-[#1A1A1A] mb-3">The Story of {{ businessName }}</h1>
                    <p class="text-[#555555] text-base md:text-lg">
                        Here's what StoryCreator generated from your interview.
                        <span class="text-[#F5A000] font-semibold">{{ episodes.length }} episodes</span> ready to publish.
                    </p>
                </div>

                <!-- Episodes -->
                <div class="space-y-6">
                    <article
                        v-for="ep in episodes"
                        :key="ep.id"
                        class="bg-white rounded-2xl border border-[#DDDDDD] hover:border-[#F5A000]/30 hover:shadow-sm transition-all duration-200 overflow-hidden"
                    >
                        <!-- Header — two rows on mobile, one row on sm+ -->
                        <div class="px-4 sm:px-6 pt-5 pb-4 border-b border-[#F5F5F5] space-y-3">

                            <!-- Row 1: badges -->
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black bg-[#F5A000] text-white px-2.5 py-1 rounded-lg shrink-0">
                                    Episode {{ ep.episode_number }}
                                </span>
                                <Badge :class="formatColor[ep.format]" class="text-xs font-semibold border shrink-0">
                                    {{ formatLabel[ep.format] ?? ep.format }}
                                </Badge>
                            </div>

                            <!-- Row 2: actions (hidden for demo stories) -->
                            <div v-if="!isDemo" class="flex items-center gap-2">

                                <!-- Revision navigator — only when history exists -->
                                <div v-if="ep.versions_count > 0" class="flex items-center gap-1 mr-1">
                                    <button
                                        type="button"
                                        :disabled="position(ep) <= 1 || revState[ep.id]?.loading"
                                        aria-label="Previous revision"
                                        class="w-11 h-11 flex items-center justify-center rounded-xl border transition-all duration-150 cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed hover:border-[#F5A000]/40 hover:bg-amber-50 hover:text-[#F5A000]"
                                        style="border-color: #DDDDDD; color: #555555;"
                                        @click="prevRevision(ep)"
                                    >
                                        <Loader2 v-if="revState[ep.id]?.loading" class="w-4 h-4 animate-spin" />
                                        <ChevronLeft v-else class="w-4 h-4" />
                                    </button>

                                    <span
                                        class="text-xs font-bold tabular-nums px-2.5 py-1 rounded-lg select-none min-w-[44px] text-center"
                                        :class="isAtCurrent(ep) ? 'text-[#F5A000] bg-amber-50' : 'text-[#555555] bg-[#F5F5F5]'"
                                    >
                                        {{ position(ep) }}/{{ total(ep) }}
                                    </span>

                                    <button
                                        type="button"
                                        :disabled="isAtCurrent(ep)"
                                        aria-label="Next revision"
                                        class="w-11 h-11 flex items-center justify-center rounded-xl border transition-all duration-150 cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed hover:border-[#F5A000]/40 hover:bg-amber-50 hover:text-[#F5A000]"
                                        style="border-color: #DDDDDD; color: #555555;"
                                        @click="nextRevision(ep)"
                                    >
                                        <ChevronRight class="w-4 h-4" />
                                    </button>
                                </div>

                                <!-- Spacer pushes Refine + Copy to the right -->
                                <div class="flex-1" />

                                <!-- Refine — disabled while in progress -->
                                <button
                                    type="button"
                                    aria-label="Refine episode"
                                    :disabled="refining === ep.id"
                                    @click="openRefine(ep)"
                                    class="flex items-center justify-center gap-1.5 text-xs font-semibold w-11 h-11 sm:w-auto sm:px-3 rounded-xl border transition-all duration-150 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                    :class="refining === ep.id
                                        ? 'text-[#F5A000] border-[#F5A000]/40 bg-amber-50'
                                        : 'text-[#555555] border-[#DDDDDD] hover:text-[#F5A000] hover:border-[#F5A000]/40 hover:bg-amber-50'"
                                >
                                    <Loader2 v-if="refining === ep.id" class="w-4 h-4 shrink-0 animate-spin" />
                                    <Wand2 v-else class="w-4 h-4 shrink-0" />
                                    <span class="hidden sm:inline">{{ refining === ep.id ? 'Refining…' : 'Refine' }}</span>
                                </button>

                                <!-- Copy — icon only on mobile, label on sm+ -->
                                <button
                                    type="button"
                                    aria-label="Copy episode"
                                    @click="copyEpisode(displayed(ep).content)"
                                    class="flex items-center justify-center gap-1.5 text-xs font-semibold text-white w-11 h-11 sm:w-auto sm:px-3 rounded-xl bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br transition-all duration-300 cursor-pointer"
                                >
                                    <Check v-if="copied === displayed(ep).content" class="w-4 h-4 shrink-0" />
                                    <Copy v-else class="w-4 h-4 shrink-0" />
                                    <span class="hidden sm:inline">{{ copied === displayed(ep).content ? 'Copied!' : 'Copy' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="px-4 sm:px-6 py-5">
                            <!-- Past revision banner -->
                            <div
                                v-if="!isAtCurrent(ep)"
                                class="flex flex-wrap items-center justify-between gap-3 mb-5 px-4 py-3 rounded-xl"
                                style="background-color: #FEF9EC; border: 1px solid #F5E4A0;"
                            >
                                <p class="text-xs text-[#555555]">
                                    Viewing revision <span class="font-bold text-[#1A1A1A]">{{ position(ep) }} of {{ total(ep) }}</span>
                                </p>
                                <button
                                    type="button"
                                    :disabled="restoring === ep.id"
                                    class="flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg transition-all duration-150 cursor-pointer disabled:opacity-50"
                                    style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                                    @click="restoreRevision(ep)"
                                >
                                    <Loader2 v-if="restoring === ep.id" class="w-3 h-3 animate-spin" />
                                    <RotateCcw v-else class="w-3 h-3" />
                                    {{ restoring === ep.id ? 'Restoring…' : 'Restore this version' }}
                                </button>
                            </div>

                            <h2 class="text-xl font-black text-[#1A1A1A] mb-4">{{ displayed(ep).title }}</h2>
                            <div class="text-[#333333] text-[15px] leading-[1.8] whitespace-pre-wrap">{{ displayed(ep).content }}</div>
                        </div>
                    </article>
                </div>

                <!-- Bottom CTA -->
                <div class="mt-12 bg-white rounded-2xl border border-[#DDDDDD] p-8 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-amber-50 mb-4">
                        <Sparkles class="w-6 h-6 text-[#F5A000]" />
                    </div>
                    <template v-if="isDemo">
                        <h3 class="text-xl font-black text-[#1A1A1A] mb-2">Ready to tell your own story?</h3>
                        <p class="text-[#555555] mb-6 max-w-md mx-auto">
                            Pick a plan and StoryCreator.Bot will interview you, then generate a story library just like this — but yours.
                        </p>
                        <Link :href="route('billing.plans')">
                            <Button class="inline-flex items-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-11 px-8 rounded-xl transition-all duration-300 cursor-pointer">
                                <Sparkles class="w-4 h-4" />
                                Choose a Plan
                            </Button>
                        </Link>
                    </template>
                    <template v-else>
                        <h3 class="text-xl font-black text-[#1A1A1A] mb-2">Ready to tell your next story?</h3>
                        <p class="text-[#555555] mb-6 max-w-md mx-auto">
                            Each story builds your brand's narrative. Start a new interview to explore a different angle of your business.
                        </p>
                        <Link :href="route('stories.create')">
                            <Button class="inline-flex items-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-11 px-8 rounded-xl transition-all duration-300 cursor-pointer">
                                <Plus class="w-4 h-4" />
                                Create Another Story
                            </Button>
                        </Link>
                    </template>
                </div>

            </div>
        </div>

        <!-- Refine dialog -->
        <Dialog v-model:open="refineOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="text-[#1A1A1A]">Refine this episode?</DialogTitle>
                    <DialogDescription class="text-[#555555]">
                        Episode {{ refineTarget?.episode_number }} —
                        "<span class="font-semibold text-[#1A1A1A]">{{ refineTarget?.title }}</span>"
                        will be rewritten by AI. The current version is saved to history so you can restore it anytime. This uses 1 refine credit.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="outline" :disabled="refining !== null" @click="refineOpen = false" class="cursor-pointer">Cancel</Button>
                    <Button
                        :disabled="refining !== null"
                        @click="confirmRefine"
                        class="flex items-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold cursor-pointer transition-all duration-300"
                    >
                        <Loader2 v-if="refining !== null" class="w-4 h-4 animate-spin" />
                        <Wand2 v-else class="w-4 h-4" />
                        {{ refining !== null ? 'Refining…' : 'Yes, Refine' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AuthenticatedLayout>
</template>
