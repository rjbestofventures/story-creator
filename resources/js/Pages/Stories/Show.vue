<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/Components/ui/dialog';
import {
    ArrowLeft, Copy, Check, Sparkles, Loader2, Plus,
    Wand2, ChevronLeft, ChevronRight, RotateCcw, ArrowRight, Pencil, RefreshCcw,
} from 'lucide-vue-next';

const props = defineProps({
    story: Object,
    canCreateStory: Boolean,
    isAdmin: Boolean,
    credits: { type: Number, default: null },
});

const isDemo       = props.story.is_demo ?? false;
const episodes     = ref(props.story.episodes ?? []);
const businessName = props.story.business_profile?.business_name ?? 'Your Business';
const storyTitle   = computed(() => props.story.title ?? `The Story of ${businessName}`);

// Local, mutable copy of the credit balance so it updates immediately after a
// refine, without waiting for a full page reload.
const creditsBalance = ref(props.credits);
watch(() => props.credits, (val) => { creditsBalance.value = val; });

// ─── Generating / failed state + polling ─────────────────────────────────────
const isGenerating = computed(() => props.story.status === 'generating');
const isFailed     = computed(() => props.story.status === 'failed');
const pollTimedOut = ref(false);
const retrying     = ref(false);
let pollTimer      = null;
let pollCount      = 0;
const POLL_MAX     = 60;

// Sync episodes from props after Inertia partial reload
watch(() => props.story.episodes, (eps) => {
    if (eps?.length) {
        episodes.value = eps;
        initAllEditState();
    }
});

const startPolling = () => {
    pollCount = 0;
    pollTimer = setInterval(async () => {
        pollCount++;
        if (pollCount >= POLL_MAX) {
            clearInterval(pollTimer);
            pollTimedOut.value = true;
            return;
        }
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

const retryGeneration = async () => {
    retrying.value = true;
    try {
        await fetch(route('stories.retry', props.story.id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
        });
        pollTimedOut.value = false;
        router.reload({ only: ['story'] });
    } finally {
        retrying.value = false;
    }
};

onMounted(() => { if (props.story.status === 'generating') startPolling(); });
onUnmounted(() => { if (pollTimer) clearInterval(pollTimer); });

const formatLabel = { social: 'Social Media', linkedin: 'LinkedIn', blog: 'Blog' };
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
const revState = ref({});

const total    = (ep) => (ep.versions_count ?? 0) + 1;
const position = (ep) => revState.value[ep.id]?.position ?? total(ep);

const displayed = (ep) => {
    const state = revState.value[ep.id];
    const pos   = state?.position ?? total(ep);
    if (pos === total(ep) || !state?.versions) {
        return { title: ep.title, content: ep.content };
    }
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

// ─── Inline editing ───────────────────────────────────────────────────────────
const focusedId    = ref(null);
const editingId    = ref(null);
const savingId     = ref(null);
const editState    = ref({});
const contentRefs  = ref({});

const syncEditState = (epId) => {
    const ep = episodes.value.find(e => e.id === epId);
    if (ep) editState.value[epId] = { title: ep.title, content: ep.content };
};

const initAllEditState = () => {
    for (const ep of episodes.value) syncEditState(ep.id);
};

onMounted(initAllEditState);

const isEditing = (ep) => editingId.value === ep.id;

const editEpisode = async (ep) => {
    editingId.value = ep.id;
    focusedId.value = ep.id;
    await nextTick();
    contentRefs.value[ep.id]?.focus();
};

const handleCardFocusIn = (epId) => {
    focusedId.value = epId;
};

const handleCardFocusOut = async (ep, event) => {
    if (event.currentTarget.contains(event.relatedTarget)) return;
    if (confirmRefineOpen.value) return; // keep edit mode while the refine confirmation is open
    focusedId.value = null;
    if (editingId.value === ep.id) editingId.value = null;
    if (!isDemo && isAtCurrent(ep)) await saveEdit(ep);
};

const saveEdit = async (ep) => {
    const state = editState.value[ep.id];
    if (!state) return;
    const idx = episodes.value.findIndex(e => e.id === ep.id);
    if (idx === -1) return;
    const cur = episodes.value[idx];
    if (state.title === cur.title && state.content === cur.content) return;

    savingId.value = ep.id;
    try {
        await fetch(route('stories.episode.update', { story: props.story.id, episode: ep.id }), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ title: state.title, content: state.content }),
        });
        episodes.value[idx] = { ...episodes.value[idx], title: state.title, content: state.content };
    } finally {
        savingId.value = null;
    }
};

// ─── AI Refine Tone ───────────────────────────────────────────────────────────
const toningEpId = ref(null);
const toningId   = ref(null);

const toneOptions1 = [
    { key: 'friendlier',   label: 'Make it Friendlier' },
    { key: 'shorter',      label: 'Make it Shorter' },
    { key: 'humor',        label: 'Add Humor' },
    { key: 'professional', label: 'More Professional' },
];

const toneOptions2 = [
    { key: 'longer',      label: 'Make it Longer' },
    { key: 'more_cta',    label: 'More Call to Action' },
    { key: 'less_cta',    label: 'Less Call to Action' },
    { key: 'promotional', label: 'Make it Promotional' },
];

const customInstructions = ref(
    Object.fromEntries(props.story.episodes.map(ep => [ep.id, ep.custom_refine_instruction ?? '']))
);

const refineInstructionTimers = {};

const persistRefineInstruction = (ep, value) => {
    clearTimeout(refineInstructionTimers[ep.id]);
    refineInstructionTimers[ep.id] = setTimeout(() => {
        fetch(route('stories.episode.refine-instruction', { story: props.story.id, episode: ep.id }), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ custom_refine_instruction: value }),
        });
    }, 600);
};

const refineError = ref(null);

// ─── Refine confirmation (each refine costs 1 credit; restoring a version is free) ─
const confirmRefineOpen = ref(false);
const pendingRefine     = ref(null);
const pendingRefineKind = ref('refine'); // 'refine' | 'restore'

const requestRefine = (fn, kind = 'refine') => {
    if (isDemo) { fn(); return; } // demo never charges
    pendingRefine.value     = fn;
    pendingRefineKind.value = kind;
    confirmRefineOpen.value = true;
};

const confirmRefine = () => {
    confirmRefineOpen.value = false;
    const fn = pendingRefine.value;
    pendingRefine.value = null;
    if (fn) fn();
};

const applyTone = async (ep, toneKey) => {
    refineError.value = null;
    await saveEdit(ep);

    toningEpId.value = ep.id;
    toningId.value   = toneKey;
    try {
        const res  = await fetch(route('stories.episode.refine', { story: props.story.id, episode: ep.id }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ tone: toneKey }),
        });

        const data = await res.json();

        if (!res.ok) {
            refineError.value = data.message ?? 'Refine failed. Please try again.';
            return;
        }

        const idx = episodes.value.findIndex(e => e.id === data.episode.id);
        if (idx !== -1) {
            episodes.value[idx] = {
                ...episodes.value[idx],
                content:        data.episode.content,
                versions_count: (episodes.value[idx].versions_count ?? 0) + 1,
            };
            syncEditState(ep.id);
            revState.value[ep.id] = { position: total(episodes.value[idx]), versions: null };
        }
        if (typeof data.credits === 'number') creditsBalance.value = data.credits;
    } finally {
        toningEpId.value = null;
        toningId.value   = null;
    }
};

const applyCustomRefine = async (ep) => {
    const instruction = customInstructions.value[ep.id]?.trim();
    if (!instruction) return;

    refineError.value = null;
    await saveEdit(ep);

    toningEpId.value = ep.id;
    toningId.value   = 'custom';
    try {
        const res = await fetch(route('stories.episode.refine', { story: props.story.id, episode: ep.id }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ tone: 'custom', custom_instruction: instruction }),
        });

        const data = await res.json();

        if (!res.ok) {
            refineError.value = data.message ?? 'Refine failed. Please try again.';
            return;
        }

        const idx = episodes.value.findIndex(e => e.id === data.episode.id);
        if (idx !== -1) {
            episodes.value[idx] = {
                ...episodes.value[idx],
                content:        data.episode.content,
                versions_count: (episodes.value[idx].versions_count ?? 0) + 1,
            };
            syncEditState(ep.id);
            revState.value[ep.id] = { position: total(episodes.value[idx]), versions: null };
        }
        if (typeof data.credits === 'number') creditsBalance.value = data.credits;
    } finally {
        toningEpId.value = null;
        toningId.value   = null;
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
            syncEditState(ep.id);
        }
        revState.value[ep.id] = { position: total(episodes.value[idx]), versions: null };
    } finally {
        restoring.value = null;
    }
};
</script>

<template>
    <Head :title="storyTitle" />
    <AuthenticatedLayout>
        <!-- Generating overlay -->
        <div v-if="isGenerating && !pollTimedOut" class="fixed inset-0 z-50 flex flex-col items-center justify-center" style="background: rgba(250,250,248,0.97);">
            <div class="flex flex-col items-center gap-4 text-center px-6">
                <Loader2 class="w-12 h-12 animate-spin" style="color: #F5A000;" />
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide mb-2" style="color: #AAAAAA;">Processing your provided information…</p>
                    <p class="text-xl font-black mb-1" style="color: #1A1A1A;">Generating your story…</p>
                    <p class="text-sm" style="color: #555555;">This takes up to 3 minutes. You can wait here or come back later.</p>
                </div>
                <Link :href="route('stories.index')" class="text-sm underline mt-2" style="color: #555555;">Go to My Stories</Link>
            </div>
        </div>

        <!-- Failed / timed-out overlay -->
        <div v-if="isFailed || pollTimedOut" class="fixed inset-0 z-50 flex flex-col items-center justify-center" style="background: rgba(250,250,248,0.97);">
            <div class="flex flex-col items-center gap-4 text-center px-6 max-w-sm">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:#FEF2F2;">
                    <span class="text-2xl">⚠️</span>
                </div>
                <div>
                    <p class="text-xl font-black mb-1" style="color: #1A1A1A;">Generation failed</p>
                    <p class="text-sm" style="color: #555555;">Something went wrong while creating your story. You can try again — it won't use an extra credit.</p>
                </div>
                <button
                    @click="retryGeneration"
                    :disabled="retrying"
                    class="flex items-center gap-2 px-6 py-2.5 rounded-lg font-bold text-sm transition disabled:opacity-60 cursor-pointer"
                    style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                >
                    <Loader2 v-if="retrying" class="w-4 h-4 animate-spin" />
                    {{ retrying ? 'Retrying…' : 'Try Again' }}
                </button>
                <Link :href="route('stories.index')" class="text-sm underline" style="color: #555555;">Go to My Stories</Link>
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
                    <Link :href="isDemo ? route('shop.index') : route('stories.create')">
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
                    <Link :href="route('shop.index')" class="flex-shrink-0">
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
                        Story Generated by Our Intelligence
                    </div>
                    <h1 class="text-2xl md:text-4xl font-black text-[#1A1A1A] mb-3">{{ storyTitle }}</h1>
                    <p class="text-[#555555] text-base md:text-lg">
                        Here's what StoryCreator generated from your interview.
                        <span class="text-[#F5A000] font-semibold">{{ episodes.length }} chapter{{ episodes.length === 1 ? '' : 's' }}</span> ready to publish.
                    </p>
                </div>

                <!-- Episodes -->
                <div class="space-y-6">
                    <article
                        v-for="ep in episodes"
                        :key="ep.id"
                        class="bg-white rounded-2xl border transition-all duration-200 overflow-hidden relative"
                        :class="focusedId === ep.id
                            ? 'border-[#F5A000]/40 shadow-md'
                            : 'border-[#DDDDDD] hover:border-[#F5A000]/20 hover:shadow-sm'"
                        @focusin="handleCardFocusIn(ep.id)"
                        @focusout="handleCardFocusOut(ep, $event)"
                    >
                        <!-- ── Card header ─────────────────────────────────── -->
                        <div class="px-4 sm:px-6 pt-5 pb-4 border-b border-[#F5F5F5] space-y-3">

                            <!-- Edit + Copy buttons — absolute top-right (non-demo only) -->
                            <div v-if="!isDemo" class="absolute top-3 right-3 flex items-center gap-1">
                                <button
                                    v-if="isAtCurrent(ep) && !isEditing(ep)"
                                    type="button"
                                    aria-label="Edit chapter"
                                    @mousedown.prevent
                                    @click.stop="editEpisode(ep)"
                                    class="flex items-center gap-1.5 text-xs font-semibold px-2.5 h-8 rounded-lg border border-[#DDDDDD] text-[#555555] hover:text-[#F5A000] hover:border-[#F5A000]/40 hover:bg-amber-50 transition-all duration-150 cursor-pointer"
                                >
                                    <Pencil class="w-3.5 h-3.5" />
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    aria-label="Copy chapter"
                                    @click.stop="copyEpisode(editState[ep.id]?.content ?? displayed(ep).content)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg transition-all duration-150 cursor-pointer"
                                    :class="copied === (editState[ep.id]?.content ?? displayed(ep).content)
                                        ? 'text-emerald-600 bg-emerald-50'
                                        : 'text-[#AAAAAA] hover:text-[#F5A000] hover:bg-amber-50'"
                                >
                                    <Check v-if="copied === (editState[ep.id]?.content ?? displayed(ep).content)" class="w-4 h-4" />
                                    <Copy v-else class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Row 1: revision navigator (non-demo, has history) -->
                            <div v-if="!isDemo && ep.versions_count > 0" class="flex items-center gap-1">
                                <button
                                    type="button"
                                    :disabled="position(ep) <= 1 || revState[ep.id]?.loading"
                                    aria-label="Previous revision"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl border transition-all duration-150 cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed hover:border-[#F5A000]/40 hover:bg-amber-50 hover:text-[#F5A000]"
                                    style="border-color: #DDDDDD; color: #555555;"
                                    @click="prevRevision(ep)"
                                >
                                    <Loader2 v-if="revState[ep.id]?.loading" class="w-3.5 h-3.5 animate-spin" />
                                    <ChevronLeft v-else class="w-3.5 h-3.5" />
                                </button>

                                <span
                                    class="text-xs font-bold tabular-nums px-2 py-1 rounded-lg select-none min-w-[40px] text-center"
                                    :class="isAtCurrent(ep) ? 'text-[#F5A000] bg-amber-50' : 'text-[#555555] bg-[#F5F5F5]'"
                                >
                                    {{ position(ep) }}/{{ total(ep) }}
                                </span>

                                <button
                                    type="button"
                                    :disabled="isAtCurrent(ep)"
                                    aria-label="Next revision"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl border transition-all duration-150 cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed hover:border-[#F5A000]/40 hover:bg-amber-50 hover:text-[#F5A000]"
                                    style="border-color: #DDDDDD; color: #555555;"
                                    @click="nextRevision(ep)"
                                >
                                    <ChevronRight class="w-3.5 h-3.5" />
                                </button>
                            </div>

                            <!-- Row 2: badges (leave room on right for copy button) -->
                            <div class="flex items-center gap-2 pr-10">
                                <Badge :class="formatColor[ep.format]" class="text-xs font-semibold border shrink-0">
                                    {{ formatLabel[ep.format] ?? ep.format }}
                                </Badge>
                                <span class="text-xs font-black bg-[#F5A000] text-white px-2.5 py-1 rounded-lg shrink-0">
                                    Chapter {{ ep.episode_number }}
                                </span>
                                <span v-if="!isDemo" class="text-xs font-bold text-[#F5A000] border border-[#F5A000]/40 bg-amber-50 px-2 py-0.5 rounded-md shrink-0">
                                    Viewing Version {{ position(ep) }}
                                </span>
                            </div>
                        </div>

                        <!-- ── Card body ───────────────────────────────────── -->
                        <div class="px-4 sm:px-6 py-5 relative">
                            <!-- Refine loading overlay -->
                            <div
                                v-if="toningEpId === ep.id"
                                class="absolute inset-0 z-10 flex items-center justify-center rounded-b-2xl bg-white/80"
                            >
                                <div class="flex items-center gap-2 text-sm font-semibold text-[#555555]">
                                    <Loader2 class="w-4 h-4 animate-spin text-[#F5A000]" />
                                    Refining…
                                </div>
                            </div>

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
                                    @click="requestRefine(() => restoreRevision(ep), 'restore')"
                                >
                                    <Loader2 v-if="restoring === ep.id" class="w-3 h-3 animate-spin" />
                                    <RotateCcw v-else class="w-3 h-3" />
                                    {{ restoring === ep.id ? 'Restoring…' : 'Restore this version' }}
                                </button>
                            </div>

                            <!-- Title — editable input in edit mode, plain h2 otherwise -->
                            <input
                                v-if="!isDemo && isAtCurrent(ep) && isEditing(ep) && editState[ep.id]"
                                :value="editState[ep.id].title"
                                @input="editState[ep.id].title = $event.target.value"
                                class="w-full text-xl font-black text-[#1A1A1A] mb-3 bg-[#FAFAF8] border-0 outline-none rounded-lg px-2 -mx-2"
                                placeholder="Chapter title"
                            />
                            <h2 v-else class="text-xl font-black text-[#1A1A1A] mb-3">{{ displayed(ep).title }}</h2>

                            <!-- Content — editable textarea in edit mode, plain div otherwise -->
                            <textarea
                                v-if="!isDemo && isAtCurrent(ep) && isEditing(ep) && editState[ep.id]"
                                :ref="el => contentRefs[ep.id] = el"
                                :value="editState[ep.id].content"
                                @input="editState[ep.id].content = $event.target.value"
                                class="w-full text-[#333333] text-[15px] leading-[1.8] bg-[#FAFAF8] border-0 outline-none resize-none rounded-lg px-2 -mx-2 [field-sizing:content]"
                                style="min-height: 120px;"
                            />
                            <div v-else class="text-[#333333] text-[15px] leading-[1.8] whitespace-pre-wrap">{{ displayed(ep).content }}</div>

                            <!-- AI Refine toolbar — appears when card is focused, at current revision -->
                            <div
                                v-if="!isDemo && isAtCurrent(ep) && isEditing(ep)"
                                class="mt-5 pt-4 border-t border-[#F0F0F0]"
                            >
                                <p v-if="refineError && toningEpId === null" class="text-xs text-red-600 mb-2">{{ refineError }}</p>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="flex items-center gap-1 text-xs font-semibold text-[#888888] shrink-0">
                                        <Wand2 class="w-3.5 h-3.5 text-[#F5A000]" />
                                        AI Refine:
                                    </span>
                                    <button
                                        v-for="opt in toneOptions1"
                                        :key="opt.key"
                                        type="button"
                                        :disabled="toningEpId === ep.id"
                                        @click="requestRefine(() => applyTone(ep, opt.key))"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border transition-all duration-150 cursor-pointer disabled:cursor-not-allowed"
                                        :class="toningEpId === ep.id && toningId === opt.key
                                            ? 'text-[#F5A000] border-[#F5A000]/40 bg-amber-50 opacity-100'
                                            : toningEpId === ep.id
                                                ? 'text-[#AAAAAA] border-[#EEEEEE] opacity-50'
                                                : 'text-[#555555] border-[#DDDDDD] hover:text-[#F5A000] hover:border-[#F5A000]/40 hover:bg-amber-50'"
                                    >
                                        <Loader2 v-if="toningEpId === ep.id && toningId === opt.key" class="w-3 h-3 animate-spin" />
                                        {{ opt.label }}
                                    </button>
                                    <span v-if="savingId === ep.id" class="flex items-center gap-1 text-xs text-[#AAAAAA] ml-1">
                                        <Loader2 class="w-3 h-3 animate-spin" />
                                        Saving…
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 flex-wrap mt-2">
                                    <button
                                        v-for="opt in toneOptions2"
                                        :key="opt.key"
                                        type="button"
                                        :disabled="toningEpId === ep.id"
                                        @click="requestRefine(() => applyTone(ep, opt.key))"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border transition-all duration-150 cursor-pointer disabled:cursor-not-allowed"
                                        :class="toningEpId === ep.id && toningId === opt.key
                                            ? 'text-[#F5A000] border-[#F5A000]/40 bg-amber-50 opacity-100'
                                            : toningEpId === ep.id
                                                ? 'text-[#AAAAAA] border-[#EEEEEE] opacity-50'
                                                : 'text-[#555555] border-[#DDDDDD] hover:text-[#F5A000] hover:border-[#F5A000]/40 hover:bg-amber-50'"
                                    >
                                        <Loader2 v-if="toningEpId === ep.id && toningId === opt.key" class="w-3 h-3 animate-spin" />
                                        {{ opt.label }}
                                    </button>
                                </div>
                                <div class="flex gap-2 items-start mt-3">
                                    <textarea
                                        v-model="customInstructions[ep.id]"
                                        :disabled="toningEpId === ep.id"
                                        @input="persistRefineInstruction(ep, customInstructions[ep.id])"
                                        placeholder="Describe how you'd like this refined... (e.g. add more urgency, include a biblical reference)"
                                        rows="2"
                                        class="flex-1 text-sm text-[#333333] bg-white border border-[#DDDDDD] rounded-lg px-3 py-2 resize-none placeholder:text-[#AAAAAA] focus:outline-none focus:border-[#F5A000]/60 disabled:opacity-50 disabled:cursor-not-allowed"
                                    />
                                    <button
                                        type="button"
                                        :disabled="toningEpId === ep.id || !customInstructions[ep.id]?.trim()"
                                        @click="requestRefine(() => applyCustomRefine(ep))"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-lg border transition-all duration-150 cursor-pointer disabled:cursor-not-allowed shrink-0"
                                        :class="toningEpId === ep.id && toningId === 'custom'
                                            ? 'text-[#F5A000] border-[#F5A000]/40 bg-amber-50'
                                            : toningEpId === ep.id || !customInstructions[ep.id]?.trim()
                                                ? 'text-[#AAAAAA] border-[#EEEEEE] opacity-50'
                                                : 'text-[#555555] border-[#DDDDDD] hover:text-[#F5A000] hover:border-[#F5A000]/40 hover:bg-amber-50'"
                                    >
                                        <Loader2 v-if="toningEpId === ep.id && toningId === 'custom'" class="w-3 h-3 animate-spin" />
                                        Refine
                                    </button>
                                </div>
                            </div>
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
                        <Link :href="route('shop.index')">
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
                        <template v-if="canCreateStory">
                            <Link :href="route('stories.create')">
                                <Button class="inline-flex items-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-11 px-8 rounded-xl transition-all duration-300 cursor-pointer">
                                    <Plus class="w-4 h-4" />
                                    Create Another Story
                                </Button>
                            </Link>
                        </template>
                        <template v-else>
                            <p class="text-sm text-[#555555] mb-4">You're out of credits. Top up to generate or refine more.</p>
                            <Link :href="route('shop.index')">
                                <Button class="inline-flex items-center gap-2 bg-white border border-[#DDDDDD] text-[#1A1A1A] font-bold h-11 px-8 rounded-xl hover:bg-[#F5F5F5] transition-all duration-300 cursor-pointer">
                                    Buy Credits
                                </Button>
                            </Link>
                        </template>
                    </template>
                </div>

            </div>
        </div>

        <!-- Refine confirmation — non-modal so the episode you're editing stays interactive -->
        <Dialog v-model:open="confirmRefineOpen" :modal="false">
            <DialogContent class="max-w-md" @interact-outside="(e) => e.preventDefault()">
                <DialogHeader>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center mb-2">
                        <RefreshCcw class="w-5 h-5 text-[#F5A000]" />
                    </div>
                    <DialogTitle class="text-[#1A1A1A]">
                        {{ pendingRefineKind === 'restore' ? 'Restore this version?' : 'Refine this chapter?' }}
                    </DialogTitle>
                    <DialogDescription as="div" class="text-[#555555]">
                        <template v-if="pendingRefineKind === 'restore'">
                            <p>
                                Are you sure you want to restore this version? This won't cost any credits.
                                The current version is saved to history so you can come back to it.
                            </p>
                        </template>
                        <template v-else-if="isAdmin">
                            <p>This will rewrite the chapter. The current version is saved to history so you can restore it.</p>
                        </template>
                        <template v-else>
                            <p>This will rewrite the chapter. The current version is saved to history so you can restore it.</p>
                            <ul class="mt-2 space-y-1 list-disc list-inside">
                                <li>Current StoryBot Credits: <strong class="text-[#1A1A1A]">{{ creditsBalance }}</strong></li>
                                <li>Cost: <strong class="text-[#1A1A1A]">1 credit</strong></li>
                                <li>Remaining Balance After Refine: <strong class="text-[#1A1A1A]">{{ creditsBalance - 1 }} credit{{ (creditsBalance - 1) === 1 ? '' : 's' }}</strong></li>
                            </ul>
                        </template>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="confirmRefineOpen = false" class="cursor-pointer">Cancel</Button>
                    <Button
                        @click="confirmRefine"
                        class="bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-[#1A1A1A] font-bold cursor-pointer"
                    >
                        {{ pendingRefineKind === 'restore' ? 'Yes, restore it' : 'Yes, refine it' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AuthenticatedLayout>
</template>
