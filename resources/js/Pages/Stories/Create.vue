<script setup>
import { ref, computed, nextTick, onMounted } from 'vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, ArrowRight, Sparkles, Send, Check, Pencil } from 'lucide-vue-next';

const props = defineProps({
    profile:       Object,
    story:         Object,
    episode_limit: Number,
});

// ─── Phase: 0 = basics, 1 = AI chat, 2 = generate options ───────────────────
const phase = ref(0);

// ─── Story ID — set after init, used for progress saves + generation ─────────
const storyId = ref(props.story?.id ?? null);

// ─── Demo mode — replay pre-scripted conversation client-side ─────────────────
// Used when story.is_demo is true; avoids all backend calls during interview.
const isDemoMode    = computed(() => !!props.story?.is_demo);
const demoMessages  = ref([]);
const demoPosition  = ref(0);
const isTyping      = ref(false);
const typingText    = ref('');
const typingSkip    = ref(false);

const typeOut = (text) => new Promise(resolve => {
    typingText.value  = '';
    isTyping.value    = true;
    typingSkip.value  = false;
    let i = 0;
    let lastTime = null;
    const CHARS_PER_SEC = 100;

    const tick = (ts) => {
        if (typingSkip.value) {
            typingText.value = text;
            isTyping.value   = false;
            typingSkip.value = false;
            scrollDown();
            resolve();
            return;
        }
        if (lastTime !== null) {
            const add = Math.max(1, Math.floor(((ts - lastTime) / 1000) * CHARS_PER_SEC));
            i = Math.min(i + add, text.length);
            typingText.value = text.slice(0, i);
            scrollDown();
        }
        lastTime = ts;
        if (i < text.length) requestAnimationFrame(tick);
        else { isTyping.value = false; resolve(); }
    };
    requestAnimationFrame(tick);
});

const demoBuildTurn = () => {
    const next = demoMessages.value[demoPosition.value];
    if (!next) return { show_input: false, button_text: '', complete: true };
    if (next.content.startsWith('[')) {
        return { show_input: false, button_text: answerCount.value === 0 ? 'Get started' : 'Next question', complete: false };
    }
    return { show_input: true, button_text: '', complete: false };
};

const advanceDemoReplay = async () => {
    const userMsg      = demoMessages.value[demoPosition.value];
    const assistantMsg = demoMessages.value[demoPosition.value + 1];
    if (!userMsg) return;

    chatLog.value.push(userMsg);

    if (!assistantMsg) {
        complete.value = true;
        setTimeout(() => { phase.value = 2; }, 1800);
        scrollDown();
        return;
    }

    scrollDown();
    await typeOut(assistantMsg.content);
    chatLog.value.push(assistantMsg);

    if (!userMsg.content.startsWith('[')) answerCount.value++;

    demoPosition.value += 2;

    if (demoPosition.value >= demoMessages.value.length) {
        complete.value = true;
        currentTurn.value = { message: assistantMsg.content, question: '', button_text: '', show_input: false, complete: true };
        setTimeout(() => { phase.value = 2; }, 1800);
    } else {
        const mode = demoBuildTurn();
        currentTurn.value = { message: assistantMsg.content, question: '', ...mode };
        if (mode.show_input) {
            currentInput.value = demoMessages.value[demoPosition.value]?.content ?? '';
        }
    }

    scrollDown();
};

// ─── Basics ──────────────────────────────────────────────────────────────────
const basics = ref({
    business_name: props.profile?.business_name ?? '',
    business_url:  props.profile?.business_url  ?? '',
    industry:      props.profile?.industry       ?? '',
    biography:     props.profile?.biography      ?? '',
    linkedin_url:  props.profile?.linkedin_url   ?? '',
    social_url:    props.profile?.social_url     ?? '',
});
const canStartInterview = computed(() => basics.value.business_name.trim().length > 0);

// ─── Chat ─────────────────────────────────────────────────────────────────────
const chatLog      = ref([]);
const currentInput = ref('');
const isLoading    = ref(false);
const complete     = ref(false);
const chatError    = ref('');
const chatBottom   = ref(null);
const inputRef     = ref(null);
const answerCount  = ref(0); // actual text answers submitted (excludes button clicks)

// Structured response from Claude — drives button vs input mode
const currentTurn = ref({
    message:     '',
    question:    '',
    button_text: '',
    show_input:  false,
    complete:    false,
});

// Only show real messages in the chat (filter synthetic button-click markers)
const displayLog = computed(() =>
    chatLog.value.filter(m => !(m.role === 'user' && m.content.startsWith('[')))
);

// ─── Generate options ─────────────────────────────────────────────────────────
const episodeCount = computed(() => isDemoMode.value ? 3 : (props.episode_limit ?? 5));
const format       = ref('social');
const storeForm    = useForm({
    format: 'social',
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const scrollDown = () => {
    nextTick(() => chatBottom.value?.scrollIntoView({ behavior: 'smooth' }));
};

// Determine the correct UI mode from the chatLog alone (used when restoring state).
// Returns null if the last message is from the user (meaning we need to call the API).
// Returns {show_input, button_text} otherwise.
const detectCurrentMode = (messages) => {
    const last = messages[messages.length - 1];
    if (!last) return { show_input: false, button_text: 'Get started' };

    // Last message is from user — Claude hasn't responded yet, needs an API call
    if (last.role === 'user') return null;

    // Last message is from assistant — look at what came before it
    const prev = messages[messages.length - 2];

    // If nothing came before, or the message before was a [marker] button click
    // → assistant just asked a question → user needs to type their answer
    if (!prev || (prev.role === 'user' && prev.content.startsWith('['))) {
        return { show_input: true, button_text: '' };
    }

    // Previous was a real user answer → assistant just acknowledged → show Next button
    const answers = messages.filter(m => m.role === 'user' && !m.content.startsWith('['));
    return {
        show_input:  false,
        button_text: answers.length === 0 ? 'Get started' : 'Next question',
    };
};

const callInterview = async (isAnswer = false) => {
    isLoading.value = true;
    try {
        let messages = chatLog.value;
        if (messages.length === 0 || messages[0].role === 'assistant') {
            messages = [{ role: 'user', content: 'Please begin the interview.' }, ...messages];
        }

        const res = await fetch(route('stories.interview'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({
                messages,
                business_name: basics.value.business_name,
                business_url:  basics.value.business_url,
                industry:      basics.value.industry,
                story_id:      storyId.value,
            }),
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data = await res.json();
        chatError.value = '';
        currentTurn.value = data;

        // Only count the answer if Claude accepted it as valid
        if (isAnswer && data.valid) {
            answerCount.value++;
        }

        // Store combined assistant content in chatLog for API history
        const combined = [data.message, data.question].filter(Boolean).join('\n\n');
        if (combined.trim()) {
            chatLog.value.push({ role: 'assistant', content: combined });
        }

        if (data.complete) {
            complete.value = true;
            await saveProgress('interview_complete');
            setTimeout(() => { phase.value = 2; }, 2000);
        } else {
            await saveProgress();
        }

        scrollDown();
    } catch (err) {
        chatError.value = 'Something went wrong. Please try again.';
    } finally {
        isLoading.value = false;
        nextTick(() => inputRef.value?.focus());
    }
};

// ─── Button click — user proceeds to next question ───────────────────────────
const handleButtonClick = async () => {
    if (isTyping.value) { typingSkip.value = true; return; }
    if (isDemoMode.value) { await advanceDemoReplay(); return; }
    const marker = answerCount.value === 0 ? '[Ready to begin]' : '[Ready for next question]';
    chatLog.value.push({ role: 'user', content: marker });
    await callInterview();
};

// ─── Save progress to DB ──────────────────────────────────────────────────────
const saveProgress = async (status = null) => {
    if (!storyId.value || isDemoMode.value) return; // demo: no backend calls
    try {
        await fetch(route('stories.progress', storyId.value), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ messages: chatLog.value, status }),
        });
    } catch { /* non-critical, ignore */ }
};

// ─── Start interview ──────────────────────────────────────────────────────────
const startInterview = async () => {
    if (!canStartInterview.value) return;

    if (isDemoMode.value) {
        phase.value = 1;
        chatLog.value = [];
        answerCount.value = 0;
        complete.value = false;
        chatError.value = '';
        const firstAssistant = demoMessages.value[1];
        if (firstAssistant) {
            demoPosition.value = 2;
            scrollDown();
            await typeOut(firstAssistant.content);
            chatLog.value.push(firstAssistant);
            const mode = demoBuildTurn();
            currentTurn.value = { message: firstAssistant.content, question: '', ...mode };
        }
        scrollDown();
        return;
    }

    // Create story + profile in DB before starting
    try {
        const res = await fetch(route('stories.init'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({
                business_name: basics.value.business_name,
                business_url:  basics.value.business_url,
                industry:      basics.value.industry,
                biography:     basics.value.biography,
                linkedin_url:  basics.value.linkedin_url,
                social_url:    basics.value.social_url,
            }),
        });
        const data = await res.json();
        storyId.value = data.story_id;
    } catch {
        chatError.value = 'Could not start the interview. Please try again.';
        return;
    }

    phase.value = 1;
    chatLog.value = [];
    answerCount.value = 0;
    complete.value = false;
    chatError.value = '';
    currentTurn.value = { message: '', question: '', button_text: '', show_input: false, complete: false };
    scrollDown();
    await callInterview();
};

// ─── Restore on mount (DB only) ──────────────────────────────────────────────
onMounted(async () => {
    localStorage.removeItem('sc_interview_session');
    if (props.story) {
        storyId.value = props.story.id;

        if (isDemoMode.value) {
            // Demo: load pre-scripted messages but start at Phase 0 (basics pre-filled).
            // Interview runs client-side from Phase 1 onwards; no backend calls.
            demoMessages.value = props.story.messages ?? [];
            phase.value = 0;
            return;
        }

        chatLog.value     = props.story.messages ?? [];
        complete.value    = props.story.status === 'interview_complete';
        answerCount.value = chatLog.value.filter(m => m.role === 'user' && !m.content.startsWith('[')).length;
        phase.value       = complete.value ? 2 : 1;

        if (!complete.value) {
            const mode = detectCurrentMode(chatLog.value);
            if (mode === null) {
                await callInterview();
            } else {
                currentTurn.value = { message: '', question: '', complete: false, ...mode };
            }
        }

        scrollDown();
    }
});

// ─── Submit answer ────────────────────────────────────────────────────────────
const canSubmit = computed(() => currentInput.value.trim().length >= 3 && !isLoading.value && !complete.value);

const submitAnswer = async () => {
    if (isTyping.value) { typingSkip.value = true; return; }
    if (!canSubmit.value) return;
    if (isDemoMode.value) { currentInput.value = ''; await advanceDemoReplay(); return; }
    const text = currentInput.value.trim();
    chatLog.value.push({ role: 'user', content: text });
    currentInput.value = '';
    scrollDown();
    await callInterview(true); // isAnswer=true so Claude's valid field controls the counter
};

const onKeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        submitAnswer();
    }
};

// ─── Final generate ───────────────────────────────────────────────────────────
const generateError   = ref('');
const demoGenerating  = ref(false);

const submit = () => {
    if (isDemoMode.value) {
        demoGenerating.value = true;
        setTimeout(() => {
            router.visit(route('stories.show', storyId.value));
        }, 3200);
        return;
    }
    generateError.value = '';
    storeForm.format = format.value;
    storeForm.post(route('stories.generate', storyId.value), {
        onError: () => {
            generateError.value = 'Something went wrong generating your story. Please try again.';
        },
    });
};

// ─── Back navigation ─────────────────────────────────────────────────────────
const goBack = () => {
    if (phase.value > 0) {
        phase.value = phase.value - 1;
    }
};

// ─── Progress ─────────────────────────────────────────────────────────────────
const progress = computed(() => {
    if (phase.value === 0) return 0;
    if (phase.value === 2) return 100;
    return Math.min(Math.round((answerCount.value / 15) * 100), 90);
});

const formats = [
    { value: 'social',   label: 'Social Post',  desc: '150–200 words · Instagram / Facebook' },
    { value: 'linkedin', label: 'LinkedIn',      desc: '200–300 words · Professional tone' },
    { value: 'blog',     label: 'Blog Article',  desc: '300–400 words · Long-form narrative' },
];
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
                                    {{ complete ? 'Interview complete' : `${answerCount} of 15 answered` }}
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
                    <!-- Demo notice -->
                    <div v-if="isDemoMode" class="mb-6 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-[#555555]">
                        <span class="font-bold text-[#1A1A1A]">Demo mode.</span>
                        This is a pre-filled example. Click through to experience the full interview flow.
                    </div>

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
                                    placeholder="e.g. Landscaping"
                                    class="h-11 border-[#DDDDDD] focus:border-[#F5A000] focus:ring-[#F5A000]"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="linkedin_url" class="text-[#1A1A1A] font-semibold">
                                    LinkedIn
                                    <span class="text-[#AAAAAA] font-normal text-xs">(optional)</span>
                                </Label>
                                <Input
                                    id="linkedin_url"
                                    v-model="basics.linkedin_url"
                                    placeholder="linkedin.com/in/..."
                                    class="h-11 border-[#DDDDDD] focus:border-[#F5A000] focus:ring-[#F5A000]"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="social_url" class="text-[#1A1A1A] font-semibold">
                                    Facebook / Instagram
                                    <span class="text-[#AAAAAA] font-normal text-xs">(optional)</span>
                                </Label>
                                <Input
                                    id="social_url"
                                    v-model="basics.social_url"
                                    placeholder="instagram.com/..."
                                    class="h-11 border-[#DDDDDD] focus:border-[#F5A000] focus:ring-[#F5A000]"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="biography" class="text-[#1A1A1A] font-semibold">
                                About You / Biography
                                <span class="text-[#AAAAAA] font-normal text-xs">(optional)</span>
                            </Label>
                            <Textarea
                                id="biography"
                                v-model="basics.biography"
                                placeholder="Tell us about yourself — your background, what drives you, your journey into this business..."
                                rows="3"
                                class="border-[#DDDDDD] focus:border-[#F5A000] focus:ring-[#F5A000] resize-none"
                            />
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
                <!-- Messages scroll area -->
                <div class="flex-1 min-h-0 space-y-4 overflow-y-auto pb-4 pr-1">

                    <div
                        v-for="(msg, i) in displayLog"
                        :key="i"
                        class="flex gap-3"
                        :class="msg.role === 'user' ? 'flex-row-reverse' : ''"
                    >
                        <div
                            v-if="msg.role === 'assistant'"
                            class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-[#FFC837] to-[#F5A000] flex items-center justify-center mt-0.5"
                        >
                            <Sparkles class="w-3.5 h-3.5 text-white" />
                        </div>
                        <div
                            class="max-w-[80%] px-4 py-3 rounded-2xl text-sm leading-relaxed whitespace-pre-wrap"
                            :class="msg.role === 'assistant'
                                ? 'bg-white border border-[#DDDDDD] text-[#1A1A1A] rounded-tl-sm'
                                : 'bg-[#1A1A1A] text-white rounded-tr-sm'"
                        >
                            {{ msg.content }}
                        </div>
                    </div>

                    <!-- Demo typing bubble -->
                    <div v-if="isTyping" class="flex gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-[#FFC837] to-[#F5A000] flex items-center justify-center mt-0.5">
                            <Sparkles class="w-3.5 h-3.5 text-white" />
                        </div>
                        <div class="max-w-[80%] px-4 py-3 rounded-2xl rounded-tl-sm text-sm leading-relaxed whitespace-pre-wrap bg-white border border-[#DDDDDD] text-[#1A1A1A]">
                            {{ typingText }}<span class="inline-block w-0.5 h-[1em] bg-[#F5A000] align-middle animate-pulse ml-0.5" />
                        </div>
                    </div>

                    <!-- Typing indicator (real API loading) -->
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

                <!-- Error banner -->
                <div
                    v-if="chatError"
                    class="flex-shrink-0 flex items-center justify-between gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-2.5 mt-2"
                >
                    <p class="text-sm text-red-600">{{ chatError }}</p>
                    <button type="button" @click="chatError = ''" class="text-red-400 hover:text-red-600 cursor-pointer text-xs font-semibold">Dismiss</button>
                </div>

                <!-- ─── Action area — always visible ──────────────────────────── -->
                <div class="flex-shrink-0 mt-2">

                    <!-- Interview complete: navigate to generate phase -->
                    <div v-if="complete" class="bg-white border border-[#DDDDDD] rounded-2xl p-3 flex items-center justify-between gap-3">
                        <p class="text-sm text-[#555555]">Interview complete. Ready to generate your story.</p>
                        <button
                            type="button"
                            @click="phase = 2"
                            class="flex-shrink-0 flex items-center gap-2 h-9 px-4 rounded-xl font-bold text-sm cursor-pointer transition-all duration-200"
                            style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                        >
                            Continue
                            <ArrowRight class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- Typing: skip strip -->
                    <div
                        v-else-if="isTyping"
                        class="bg-white border border-[#DDDDDD] rounded-2xl p-3 flex items-center justify-between gap-3 cursor-pointer select-none hover:bg-gray-50 transition"
                        @click="typingSkip = true"
                    >
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:0ms" />
                            <span class="w-1.5 h-1.5 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:150ms" />
                            <span class="w-1.5 h-1.5 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:300ms" />
                            <span class="text-sm text-[#AAAAAA]">StoryBot is typing…</span>
                        </div>
                        <span class="flex items-center gap-1 text-xs font-semibold text-[#555555]">
                            Skip <ArrowRight class="w-3 h-3" />
                        </span>
                    </div>

                    <!-- Normal interview input -->
                    <div v-else class="bg-white border border-[#DDDDDD] rounded-2xl p-3 flex gap-3 items-end"
                         :class="isDemoMode && currentTurn.show_input ? 'border-amber-300 bg-amber-50/30' : ''"
                    >
                        <Textarea
                            ref="inputRef"
                            v-model="currentInput"
                            :disabled="isLoading || !currentTurn.show_input"
                            :placeholder="currentTurn.show_input ? 'Type your answer… (Enter to send, Shift+Enter for new line)' : ''"
                            rows="2"
                            class="flex-1 resize-none border-0 focus:ring-0 focus:outline-none text-sm text-[#1A1A1A] placeholder-[#AAAAAA] bg-transparent p-0 transition-opacity duration-300"
                            :class="!currentTurn.show_input ? 'opacity-30 cursor-not-allowed' : ''"
                            @keydown="onKeydown"
                        />

                        <!-- Morphing button: text label → send icon -->
                        <button
                            type="button"
                            :disabled="isLoading || (currentTurn.show_input && !canSubmit)"
                            @click="currentTurn.show_input ? submitAnswer() : handleButtonClick()"
                            class="flex-shrink-0 flex items-center justify-center font-bold text-sm transition-all duration-300 cursor-pointer disabled:opacity-40"
                            :class="currentTurn.show_input
                                ? 'w-9 h-9 rounded-xl bg-gradient-to-br from-[#FFC837] to-[#F5A000] text-white hover:shadow-md'
                                : 'h-9 px-4 gap-2 rounded-xl bg-gradient-to-r from-[#FFC837] to-[#F5A000] text-[#1A1A1A]'"
                        >
                            <Send v-if="currentTurn.show_input" class="w-4 h-4" />
                            <template v-else>
                                <span>{{ currentTurn.button_text || '…' }}</span>
                                <ArrowRight class="w-3.5 h-3.5" />
                            </template>
                        </button>
                    </div>

                </div>

            </div>

            <!-- ─── PHASE 2: Generating (full-screen loader) ─────────────────── -->
            <div v-else-if="storeForm.processing || demoGenerating" class="flex-1 flex items-center justify-center px-4 py-10">
                <div class="text-center max-w-sm">
                    <!-- Pulsing logo -->
                    <div class="relative inline-flex items-center justify-center mb-8">
                        <div class="absolute w-24 h-24 rounded-full bg-amber-100 animate-ping opacity-40" />
                        <div class="absolute w-20 h-20 rounded-full bg-amber-100 animate-ping opacity-30" style="animation-delay: 300ms" />
                        <div class="relative w-16 h-16 rounded-2xl bg-gradient-to-br from-[#FFC837] to-[#F5A000] flex items-center justify-center shadow-lg">
                            <Sparkles class="w-8 h-8 text-white animate-pulse" />
                        </div>
                    </div>

                    <h2 class="text-2xl font-black text-[#1A1A1A] mb-2">Crafting your story…</h2>
                    <p class="text-[#555555] mb-6">
                        StoryCreator is writing
                        <span class="font-semibold text-[#1A1A1A]">{{ episodeCount }} episodes</span>
                        for <span class="font-semibold text-[#1A1A1A]">{{ basics.business_name }}</span>.
                    </p>

                    <!-- Animated progress dots -->
                    <div class="flex items-center justify-center gap-2 mb-6">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:0ms" />
                        <span class="w-2.5 h-2.5 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:150ms" />
                        <span class="w-2.5 h-2.5 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:300ms" />
                    </div>

                    <p class="text-xs text-[#AAAAAA]">This takes 15–30 seconds. Please don't close this page.</p>
                </div>
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
                            {{ answerCount }} answers collected for
                            <strong>{{ basics.business_name }}</strong>.
                            Now choose your episode format.
                        </p>
                    </div>

                    <!-- Error message -->
                    <div v-if="generateError" class="mb-4 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-600">
                        {{ generateError }}
                    </div>

                    <div class="bg-white rounded-2xl border border-[#DDDDDD] p-6 space-y-8">

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
                            @click="submit"
                            class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-12 rounded-xl transition-all duration-300 cursor-pointer"
                        >
                            <Sparkles class="w-4 h-4" />
                            Generate My Story
                        </Button>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
