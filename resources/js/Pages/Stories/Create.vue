<script setup>
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, ArrowRight, Sparkles, Send, Loader2, Check, Pencil } from 'lucide-vue-next';

const props = defineProps({ profile: Object });

// ─── Phase: 0 = basics, 1 = AI chat, 2 = generate options ───────────────────
const phase = ref(0);

// ─── Basics ──────────────────────────────────────────────────────────────────
const basics = ref({
    business_name: props.profile?.business_name ?? '',
    business_url:  props.profile?.business_url  ?? '',
    industry:      props.profile?.industry       ?? '',
});
const canStartInterview = computed(() => basics.value.business_name.trim().length > 0);

// ─── Chat ─────────────────────────────────────────────────────────────────────
const chatLog      = ref([]);    // { role: 'user'|'assistant', content: string }
const currentInput = ref('');
const isLoading    = ref(false);
const complete     = ref(false);
const chatBottom   = ref(null);
const inputRef     = ref(null);

// ─── Generate options ─────────────────────────────────────────────────────────
const episodeCount = ref(5);
const format       = ref('social');
const storeForm    = useForm({
    business_name: '',
    business_url:  '',
    industry:      '',
    messages:      [],
    episode_count: 5,
    format:        'social',
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const scrollDown = () => {
    nextTick(() => chatBottom.value?.scrollIntoView({ behavior: 'smooth' }));
};

const callInterview = async () => {
    isLoading.value = true;
    try {
        const res = await fetch(route('stories.interview'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({
                messages:      chatLog.value,
                business_name: basics.value.business_name,
                business_url:  basics.value.business_url,
                industry:      basics.value.industry,
            }),
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data = await res.json();
        chatLog.value.push({ role: 'assistant', content: data.message });
        scrollDown();

        if (data.complete) {
            complete.value = true;
            setTimeout(() => { phase.value = 2; }, 1200);
        }
    } catch (err) {
        chatLog.value.push({
            role: 'assistant',
            content: 'Sorry, something went wrong. Please try again.',
        });
        scrollDown();
    } finally {
        isLoading.value = false;
        nextTick(() => inputRef.value?.focus());
    }
};

// ─── Start interview ──────────────────────────────────────────────────────────
const startInterview = async () => {
    if (!canStartInterview.value) return;
    phase.value = 1;
    chatLog.value = [];
    complete.value = false;

    // Show a static welcome immediately so the chat is never blank
    chatLog.value.push({
        role: 'assistant',
        content: `Hi! I'm StoryBot. I'm going to ask you a few questions about ${basics.value.business_name} — just answer naturally, like you're telling a friend. Let's get started.`,
    });
    scrollDown();

    // Then load the first real question from Claude
    await callInterview();
};

// ─── Session persistence ──────────────────────────────────────────────────────
const SESSION_KEY = 'sc_interview_session';

const saveSession = () => {
    localStorage.setItem(SESSION_KEY, JSON.stringify({
        phase:        phase.value,
        basics:       basics.value,
        chatLog:      chatLog.value,
        complete:     complete.value,
        episodeCount: episodeCount.value,
        format:       format.value,
    }));
};

const clearSession = () => localStorage.removeItem(SESSION_KEY);

// Auto-save on every relevant state change
watch([phase, chatLog, basics, complete, episodeCount, format], saveSession, { deep: true });

// ─── Restore or auto-start on mount ──────────────────────────────────────────
onMounted(async () => {
    const raw = localStorage.getItem(SESSION_KEY);
    if (raw) {
        try {
            const s = JSON.parse(raw);
            basics.value       = s.basics       ?? basics.value;
            chatLog.value      = s.chatLog       ?? [];
            complete.value     = s.complete      ?? false;
            episodeCount.value = s.episodeCount  ?? 5;
            format.value       = s.format        ?? 'social';
            phase.value        = s.phase         ?? 0;

            // If we were mid-interview and the last message was from the user
            // (meaning Claude hadn't replied yet), re-fetch the next question
            if (phase.value === 1 && !complete.value) {
                const last = chatLog.value[chatLog.value.length - 1];
                if (last?.role === 'user') {
                    await callInterview();
                } else {
                    nextTick(() => inputRef.value?.focus());
                }
            }
            scrollDown();
            return;
        } catch {
            clearSession();
        }
    }

    // No saved session — auto-start if profile already on file
    if (props.profile?.business_name) {
        startInterview();
    }
});

// ─── Submit answer ────────────────────────────────────────────────────────────
const canSubmit = computed(() => currentInput.value.trim().length >= 3 && !isLoading.value && !complete.value);

const submitAnswer = async () => {
    if (!canSubmit.value) return;
    const text = currentInput.value.trim();
    chatLog.value.push({ role: 'user', content: text });
    currentInput.value = '';
    scrollDown();
    await callInterview();
};

const onKeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        submitAnswer();
    }
};

// ─── Final generate ───────────────────────────────────────────────────────────
const submit = () => {
    clearSession(); // wipe saved session so next story starts fresh
    storeForm.business_name = basics.value.business_name;
    storeForm.business_url  = basics.value.business_url;
    storeForm.industry      = basics.value.industry;
    storeForm.messages      = chatLog.value;
    storeForm.episode_count = episodeCount.value;
    storeForm.format        = format.value;
    storeForm.post(route('stories.store'));
};

// ─── Back navigation ─────────────────────────────────────────────────────────
// In chat/generate phase → back to basics form (keeps session so name is pre-filled)
// In basics phase → back to stories list
const goBack = () => {
    if (phase.value > 0) {
        phase.value = 0;
    }
};

// ─── Progress ─────────────────────────────────────────────────────────────────
const userMessageCount = computed(() => chatLog.value.filter(m => m.role === 'user').length);
const progress = computed(() => {
    if (phase.value === 0) return 0;
    if (phase.value === 2) return 100;
    return Math.min(Math.round((userMessageCount.value / 16) * 100), 95);
});

const formats = [
    { value: 'social',   label: 'Social Post',  desc: '150–200 words · Instagram / Facebook' },
    { value: 'linkedin', label: 'LinkedIn',      desc: '200–300 words · Professional tone' },
    { value: 'blog',     label: 'Blog Article',  desc: '300–400 words · Long-form narrative' },
];
const counts = [3, 5, 7, 10];
</script>

<template>
    <Head title="Create Your Story" />
    <AuthenticatedLayout>
        <!-- In chat phase: lock height to viewport so input stays visible -->
        <div
            class="bg-[#FAFAF8] flex flex-col"
            :class="phase !== 1 && 'min-h-screen'"
            :style="phase === 1 ? 'height: calc(100vh - 56px)' : ''"
        >

            <!-- Top bar -->
            <div class="bg-white border-b border-[#DDDDDD] px-4 md:px-8 py-4 flex-shrink-0">
                <div class="max-w-2xl mx-auto flex items-center justify-between gap-4">

                    <!-- Back: to basics form when in chat/generate, to stories list from basics -->
                    <component
                        :is="phase === 0 ? Link : 'button'"
                        :href="phase === 0 ? route('stories.index') : undefined"
                        type="button"
                        @click="phase > 0 ? goBack() : undefined"
                        class="flex items-center gap-2 text-sm text-[#555555] hover:text-[#1A1A1A] transition-colors shrink-0 cursor-pointer"
                    >
                        <ArrowLeft class="w-4 h-4" />
                        <span class="hidden sm:inline">Back</span>
                    </component>

                    <!-- Progress + business name pill when in chat -->
                    <div class="flex-1 max-w-sm">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-[#555555]">
                                <template v-if="phase === 0">Step 1 of 3 — Basics</template>
                                <template v-else-if="phase === 1">
                                    {{ complete ? 'Interview complete' : `Question ${userMessageCount + 1} of 16` }}
                                </template>
                                <template v-else>Step 3 of 3 — Generate</template>
                            </span>
                            <span class="text-xs text-[#AAAAAA]">{{ progress }}%</span>
                        </div>
                        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-gradient-to-r from-[#FFC837] to-[#F5A000] rounded-full transition-all duration-500"
                                :style="{ width: progress + '%' }"
                            />
                        </div>
                    </div>

                    <!-- Right side: business name (editable) in chat/generate, StoryBot label in basics -->
                    <div class="flex items-center gap-2 shrink-0">
                        <template v-if="phase === 0">
                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-[#FFC837] to-[#F5A000] flex items-center justify-center">
                                <Sparkles class="w-3.5 h-3.5 text-white" />
                            </div>
                            <span class="hidden sm:block text-sm font-bold text-[#1A1A1A]">StoryBot</span>
                        </template>
                        <template v-else>
                            <button
                                type="button"
                                @click="goBack"
                                class="flex items-center gap-1.5 text-xs font-semibold text-[#555555] hover:text-[#F5A000] px-2.5 h-7 rounded-lg border border-[#DDDDDD] hover:border-[#F5A000]/50 transition-all duration-150 cursor-pointer max-w-[140px]"
                                title="Edit business name"
                            >
                                <span class="truncate">{{ basics.business_name }}</span>
                                <Pencil class="w-3 h-3 flex-shrink-0" />
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- ─── PHASE 0: Basics ──────────────────────────────────────────── -->
            <div v-if="phase === 0" class="flex-1 flex items-start justify-center px-4 py-10">
                <div class="w-full max-w-lg">
                    <div class="mb-8 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-50 mb-4">
                            <Sparkles class="w-7 h-7 text-[#F5A000]" />
                        </div>
                        <h1 class="text-2xl font-black text-[#1A1A1A] mb-2">Let's get started</h1>
                        <p class="text-[#555555]">Tell us a bit about your business, then StoryBot will interview you.</p>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#DDDDDD] p-6 space-y-5">
                        <div class="space-y-2">
                            <Label for="business_name" class="text-[#1A1A1A] font-semibold">
                                Business Name <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                id="business_name"
                                v-model="basics.business_name"
                                placeholder="e.g. Bright Path Consulting"
                                class="h-11 border-[#DDDDDD] focus:border-[#F5A000] focus:ring-[#F5A000]"
                                @keyup.enter="startInterview"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="business_url" class="text-[#1A1A1A] font-semibold">
                                    Website
                                    <span class="text-[#AAAAAA] font-normal text-xs">(optional)</span>
                                </Label>
                                <Input
                                    id="business_url"
                                    v-model="basics.business_url"
                                    placeholder="https://..."
                                    type="url"
                                    class="h-11 border-[#DDDDDD] focus:border-[#F5A000] focus:ring-[#F5A000]"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="industry" class="text-[#1A1A1A] font-semibold">
                                    Industry
                                    <span class="text-[#AAAAAA] font-normal text-xs">(optional)</span>
                                </Label>
                                <Input
                                    id="industry"
                                    v-model="basics.industry"
                                    placeholder="e.g. Consulting"
                                    class="h-11 border-[#DDDDDD] focus:border-[#F5A000] focus:ring-[#F5A000]"
                                />
                            </div>
                        </div>

                        <Button
                            type="button"
                            :disabled="!canStartInterview"
                            @click="startInterview"
                            class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-12 rounded-xl transition-all duration-300 cursor-pointer disabled:opacity-40 mt-2"
                        >
                            Start My Interview
                            <ArrowRight class="w-4 h-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- ─── PHASE 1: AI Chat ─────────────────────────────────────────── -->
            <div
                v-else-if="phase === 1"
                class="flex-1 min-h-0 overflow-hidden flex flex-col max-w-2xl mx-auto w-full px-4 py-4"
            >
                <!-- Messages — flex-1 + min-h-0 allows it to shrink and scroll -->
                <div class="flex-1 min-h-0 space-y-4 overflow-y-auto pb-4 pr-1">
                    <div
                        v-for="(msg, i) in chatLog"
                        :key="i"
                        class="flex gap-3"
                        :class="msg.role === 'user' ? 'flex-row-reverse' : ''"
                    >
                        <!-- Bot avatar -->
                        <div
                            v-if="msg.role === 'assistant'"
                            class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-[#FFC837] to-[#F5A000] flex items-center justify-center mt-0.5"
                        >
                            <Sparkles class="w-3.5 h-3.5 text-white" />
                        </div>

                        <!-- Bubble -->
                        <div
                            class="max-w-[80%] px-4 py-3 rounded-2xl text-sm leading-relaxed whitespace-pre-wrap"
                            :class="msg.role === 'assistant'
                                ? 'bg-white border border-[#DDDDDD] text-[#1A1A1A] rounded-tl-sm'
                                : 'bg-[#1A1A1A] text-white rounded-tr-sm'"
                        >
                            {{ msg.content }}
                        </div>
                    </div>

                    <!-- Typing indicator -->
                    <div v-if="isLoading" class="flex gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-[#FFC837] to-[#F5A000] flex items-center justify-center">
                            <Sparkles class="w-3.5 h-3.5 text-white" />
                        </div>
                        <div class="bg-white border border-[#DDDDDD] px-4 py-3.5 rounded-2xl rounded-tl-sm flex items-center gap-1.5">
                            <span class="w-2 h-2 bg-[#DDDDDD] rounded-full animate-bounce" style="animation-delay:0ms" />
                            <span class="w-2 h-2 bg-[#DDDDDD] rounded-full animate-bounce" style="animation-delay:150ms" />
                            <span class="w-2 h-2 bg-[#DDDDDD] rounded-full animate-bounce" style="animation-delay:300ms" />
                        </div>
                    </div>

                    <div ref="chatBottom" />
                </div>

                <!-- Input — always pinned to the bottom -->
                <div
                    class="flex-shrink-0 bg-white border border-[#DDDDDD] rounded-2xl p-3 flex gap-3 items-end mt-2"
                    :class="complete ? 'opacity-50 pointer-events-none' : ''"
                >
                    <Textarea
                        ref="inputRef"
                        v-model="currentInput"
                        :disabled="isLoading || complete"
                        placeholder="Type your answer… (Enter to send, Shift+Enter for new line)"
                        rows="2"
                        class="flex-1 resize-none border-0 focus:ring-0 focus:outline-none text-sm text-[#1A1A1A] placeholder-[#AAAAAA] bg-transparent p-0"
                        @keydown="onKeydown"
                    />
                    <button
                        type="button"
                        :disabled="!canSubmit"
                        @click="submitAnswer"
                        class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-200 cursor-pointer disabled:opacity-30"
                        :class="canSubmit
                            ? 'bg-gradient-to-br from-[#FFC837] to-[#F5A000] text-white hover:shadow-md'
                            : 'bg-gray-100 text-[#AAAAAA]'"
                    >
                        <Send class="w-4 h-4" />
                    </button>
                </div>

                <p class="flex-shrink-0 text-center text-xs text-[#AAAAAA] mt-2 pb-2">
                    {{ userMessageCount }} / 16 questions answered · Press Enter to send
                </p>
            </div>

            <!-- ─── PHASE 2: Generate options ───────────────────────────────── -->
            <div v-else class="flex-1 flex items-start justify-center px-4 py-10">
                <div class="w-full max-w-lg">
                    <div class="mb-8 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-50 mb-4">
                            <Check class="w-7 h-7 text-[#F5A000]" />
                        </div>
                        <h1 class="text-2xl font-black text-[#1A1A1A] mb-2">Interview complete!</h1>
                        <p class="text-[#555555]">
                            {{ userMessageCount }} answers collected for
                            <strong>{{ basics.business_name }}</strong>.
                            Now choose your episode format.
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#DDDDDD] p-6 space-y-8">

                        <!-- Episode count -->
                        <div class="space-y-3">
                            <Label class="text-[#1A1A1A] font-bold text-base block">How many episodes?</Label>
                            <div class="flex gap-3">
                                <button
                                    v-for="c in counts" :key="c"
                                    type="button"
                                    @click="episodeCount = c"
                                    class="flex-1 py-3 rounded-xl border-2 text-sm font-bold transition-all duration-200 cursor-pointer"
                                    :class="episodeCount === c
                                        ? 'border-[#F5A000] bg-amber-50 text-[#F5A000]'
                                        : 'border-[#DDDDDD] text-[#555555] hover:border-[#F5A000]/50'"
                                >{{ c }}</button>
                            </div>
                        </div>

                        <!-- Format -->
                        <div class="space-y-3">
                            <Label class="text-[#1A1A1A] font-bold text-base block">Episode format</Label>
                            <div class="space-y-2">
                                <button
                                    v-for="f in formats" :key="f.value"
                                    type="button"
                                    @click="format = f.value"
                                    class="w-full flex items-center gap-4 p-4 rounded-xl border-2 text-left transition-all duration-200 cursor-pointer"
                                    :class="format === f.value
                                        ? 'border-[#F5A000] bg-amber-50'
                                        : 'border-[#DDDDDD] hover:border-[#F5A000]/50'"
                                >
                                    <div
                                        class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0"
                                        :class="format === f.value ? 'border-[#F5A000] bg-[#F5A000]' : 'border-[#DDDDDD]'"
                                    >
                                        <div v-if="format === f.value" class="w-2 h-2 rounded-full bg-white" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-[#1A1A1A] text-sm">{{ f.label }}</div>
                                        <div class="text-xs text-[#555555] mt-0.5">{{ f.desc }}</div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="bg-amber-50 rounded-xl p-4 border border-amber-100">
                            <p class="text-sm font-medium text-[#1A1A1A]">
                                ✨ Generating
                                <span class="text-[#F5A000] font-bold">{{ episodeCount }} {{ format }}</span>
                                episodes for
                                <span class="text-[#F5A000] font-bold">{{ basics.business_name }}</span>
                            </p>
                            <p class="text-xs text-[#555555] mt-1">Uses 1 story credit · Takes 15–30 seconds</p>
                        </div>

                        <Button
                            type="button"
                            :disabled="storeForm.processing"
                            @click="submit"
                            class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-12 rounded-xl transition-all duration-300 cursor-pointer disabled:opacity-60"
                        >
                            <Loader2 v-if="storeForm.processing" class="w-4 h-4 animate-spin" />
                            <Sparkles v-else class="w-4 h-4" />
                            {{ storeForm.processing ? 'Generating your story…' : 'Generate My Story' }}
                        </Button>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
