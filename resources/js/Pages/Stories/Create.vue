<script setup>
import { ref, computed, nextTick, onMounted } from 'vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/Components/ui/dialog';
import {
    Tooltip, TooltipContent, TooltipProvider, TooltipTrigger,
} from '@/Components/ui/tooltip';
import { ArrowLeft, ArrowRight, Sparkles, Send, Check, Pencil, AlertTriangle, Lock, Mic } from 'lucide-vue-next';

const props = defineProps({
    profile:         Object,
    story:           Object,
    credits:         { type: Number, default: null },
    max_episodes:    { type: Number, default: null },
    episode_options: {
        type: Array,
        default: () => [12, 18, 24].map((count) => ({ count, locked: false, unlock_label: null })),
    },
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
    services:      props.profile?.services       ?? '',
    linkedin_url:  props.profile?.linkedin_url   ?? '',
    social_url:    props.profile?.social_url     ?? '',
});
const hasOneUrl = computed(() =>
    basics.value.business_url.trim().length > 0 ||
    basics.value.linkedin_url.trim().length > 0 ||
    basics.value.social_url.trim().length > 0
);
const canStartInterview = computed(() =>
    basics.value.business_name.trim().length > 0 &&
    basics.value.industry.trim().length > 0 &&
    hasOneUrl.value
);
const formErrors = computed(() => {
    const e = [];
    if (!basics.value.industry.trim()) e.push('Industry is required.');
    if (!hasOneUrl.value) e.push('Please add at least one of: Website, LinkedIn, or Facebook/Instagram.');
    return e;
});

// ─── Chat ─────────────────────────────────────────────────────────────────────
const chatLog      = ref([]);
const currentInput = ref('');
const isLoading    = ref(false);
const complete     = ref(false);
const chatError    = ref('');
const chatBottom   = ref(null);
const inputRef     = ref(null);
const answerCount  = ref(0); // actual text answers submitted (excludes button clicks)
const voiceGuideOpen = ref(false);

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

const enrichedDisplayLog = computed(() => {
    let qNum = 0;
    return displayLog.value.map(msg => {
        if (msg.role === 'assistant' && msg._question) {
            // A retry re-asks the same question after an invalid answer — it
            // reuses the current number instead of advancing the count.
            if (!msg._retry) qNum++;
            return { ...msg, _questionNumber: qNum };
        }
        return msg;
    });
});

// ─── Generate options ─────────────────────────────────────────────────────────
const format = ref('social');

const isUnlimited   = computed(() => props.credits === null); // admins
const creditBalance = computed(() => props.credits ?? 0);
const episodeOptions = computed(() => props.episode_options ?? []);

// Selected episode count: default to the largest option the user can both
// unlock (pack tier) and afford (credits).
const selectedEpisodes = ref(null);

const affordable = (count) => isUnlimited.value || creditBalance.value >= count;
const unlocked = (opt) => isUnlimited.value || !opt.locked;
const selectable = (opt) => unlocked(opt) && affordable(opt.count);

const selectOption = (opt) => {
    if (!selectable(opt)) return;
    selectedEpisodes.value = opt.count;
};

const initEpisodeChoice = () => {
    const opts = episodeOptions.value;
    const best = [...opts].reverse().find(selectable)
        ?? opts.find(unlocked)
        ?? opts[0];
    selectedEpisodes.value = best?.count ?? 12;
};

const episodeCount = computed(() => {
    if (isDemoMode.value) return 3;
    return selectedEpisodes.value ?? episodeOptions.value[0]?.count ?? 12;
});

const canAffordSelected = computed(() => isUnlimited.value || creditBalance.value >= episodeCount.value);

const storeForm = useForm({
    format:        'social',
    episode_count: null,
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
        // Build clean message list for the API: strip invalid-answer/retry pairs so
        // Claude never miscounts questions when an answer was rejected and re-asked.
        const raw = chatLog.value;
        let messages = [];
        let i = 0;
        while (i < raw.length) {
            if (raw[i]._invalid) {
                i++; // skip the invalid user message
                if (i < raw.length && raw[i]._retry) i++; // skip the paired retry assistant message
                continue;
            }
            messages.push({ role: raw[i].role, content: raw[i].content });
            i++;
        }
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
        } else if (isAnswer && data.valid === false) {
            // Tag the invalid user answer so it's excluded from future API calls
            for (let j = chatLog.value.length - 1; j >= 0; j--) {
                if (chatLog.value[j].role === 'user' && !chatLog.value[j].content.startsWith('[')) {
                    chatLog.value[j] = { ...chatLog.value[j], _invalid: true };
                    break;
                }
            }
        }

        // Store combined assistant content in chatLog for display and API history
        const combined = [data.message, data.question].filter(Boolean).join('\n\n');
        if (combined.trim()) {
            const entry = { role: 'assistant', content: combined };
            if (data.question) entry._question = data.question;
            if (data.valid === false) entry._retry = true; // paired with the invalid user msg above
            chatLog.value.push(entry);
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
        // Keep _invalid/_retry/_question so question numbering and history
        // filtering still work correctly after the interview is resumed.
        const messages = chatLog.value;
        await fetch(route('stories.progress', storyId.value), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ messages, status }),
        });
    } catch { /* non-critical, ignore */ }
};

// ─── Start interview ──────────────────────────────────────────────────────────
const confirmStartOpen = ref(false);

const requestStartInterview = () => {
    if (!canStartInterview.value) return;
    if (isDemoMode.value) { startInterview(); return; }
    confirmStartOpen.value = true;
};

const confirmStartInterview = () => {
    confirmStartOpen.value = false;
    startInterview();
};

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

    // Create story + profile in DB before starting (skip if already created — e.g. user went back from Phase 1)
    if (!storyId.value) {
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
                    services:      basics.value.services,
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
    initEpisodeChoice();
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

    if (!canAffordSelected.value) {
        router.visit(route('shop.index'), {
            data: { notice: 'You need more credits to generate this story. Your interview answers are saved.' },
        });
        return;
    }

    generateError.value      = '';
    storeForm.format         = format.value;
    storeForm.episode_count  = episodeCount.value;
    storeForm.post(route('stories.generate', storyId.value), {
        onError: () => {
            generateError.value = 'Something went wrong generating your story. Please try again.';
        },
    });
};

const confirmGenerateOpen = ref(false);

const requestGenerate = () => {
    // Demo has no cost, and an unaffordable choice routes straight to the shop —
    // only confirm when real credits are about to be spent.
    if (isDemoMode.value || !canAffordSelected.value) { submit(); return; }
    confirmGenerateOpen.value = true;
};

const confirmGenerate = () => {
    confirmGenerateOpen.value = false;
    submit();
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
    { value: 'social',   label: 'Social Media',  desc: '150–200 words · Instagram / Facebook' },
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
                                @keyup.enter="requestStartInterview"
                            />
                        </div>
                        <p class="text-xs text-[#555555]">Add at least one link so StoryBot can learn more about your business. <span class="text-red-500">*</span></p>
                        <p class="text-xs text-[#AAAAAA]">When adding a link, please make sure it is set to public access so the system can properly fetch and process it.</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="business_url" class="text-[#1A1A1A] font-semibold">
                                    Website
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
                                    <span class="text-red-500 ml-0.5">*</span>
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
                                placeholder="Tell us about yourself, your background, what drives you, your journey into this business..."
                                rows="3"
                                class="border-[#DDDDDD] focus:border-[#F5A000] focus:ring-[#F5A000] resize-none"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="services" class="text-[#1A1A1A] font-semibold">
                                Services that you offer
                                <span class="text-[#AAAAAA] font-normal text-xs">(optional)</span>
                            </Label>
                            <Textarea
                                id="services"
                                v-model="basics.services"
                                placeholder="e.g. Espresso drinks, in-house roasting, catering for local events..."
                                rows="3"
                                class="border-[#DDDDDD] focus:border-[#F5A000] focus:ring-[#F5A000] resize-none"
                            />
                        </div>

                        <div v-if="formErrors.length > 0 && basics.business_name.trim()" class="space-y-1">
                            <p v-for="err in formErrors" :key="err" class="text-xs text-red-500">{{ err }}</p>
                        </div>

                        <Button
                            type="button"
                            :disabled="!canStartInterview"
                            @click="requestStartInterview"
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
                        v-for="(msg, i) in enrichedDisplayLog"
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
                            <template v-if="msg.role === 'assistant' && msg._question">
                                <span v-if="msg.content.replace(msg._question, '').trim()">{{ msg.content.replace(msg._question, '').trim() }}</span>
                                <div
                                    v-if="msg.content.replace(msg._question, '').trim()"
                                    class="mt-2 mb-2 border-t border-amber-200"
                                />
                                <span class="block font-semibold text-amber-800 bg-amber-50 rounded-lg px-2.5 py-1.5">
                                    <span class="text-amber-500 mr-1.5">{{ msg._questionNumber }}.</span>{{ msg._question }}
                                </span>
                            </template>
                            <template v-else>{{ msg.content }}</template>
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
                    <div v-else>
                        <button
                            v-if="currentTurn.show_input && !isDemoMode"
                            type="button"
                            @click="voiceGuideOpen = true"
                            class="flex items-center gap-2 mb-2 px-3 h-9 rounded-full border border-[#DDDDDD] bg-white hover:border-[#F5A000]/50 hover:bg-amber-50 transition-all duration-150 cursor-pointer"
                        >
                            <Mic class="w-3.5 h-3.5 text-[#F5A000]" />
                            <span class="text-sm font-bold text-[#1A1A1A]">Speak Instead of Type</span>
                            <span class="text-xs text-[#888888]">For longer answers</span>
                        </button>

                        <div class="bg-white border border-[#DDDDDD] rounded-2xl p-3 flex gap-3 items-end"
                             :class="isDemoMode && currentTurn.show_input ? 'border-amber-300 bg-amber-50/30' : ''"
                        >
                            <Textarea
                                ref="inputRef"
                                v-model="currentInput"
                                :disabled="isLoading || !currentTurn.show_input"
                                :readonly="isDemoMode && currentTurn.show_input"
                                :placeholder="currentTurn.show_input ? (isDemoMode ? '' : 'Type your answer… (Enter to send, Shift+Enter for new line)') : ''"
                                rows="2"
                                class="flex-1 resize-none border-0 focus:ring-0 focus:outline-none text-sm text-[#1A1A1A] placeholder-[#AAAAAA] bg-transparent p-0 transition-opacity duration-300"
                                :class="!currentTurn.show_input ? 'opacity-30 cursor-not-allowed' : (isDemoMode ? 'cursor-default select-none' : '')"
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

                    <p class="text-xs text-[#AAAAAA]">This takes 1-3 minutes. Please don't close this page.</p>
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

                        <!-- Episode count chooser -->
                        <div v-if="!isDemoMode" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <Label class="text-[#1A1A1A] font-bold text-base block">How many episodes?</Label>
                                <span v-if="!isUnlimited" class="text-xs font-semibold text-[#555555]">
                                    {{ creditBalance }} credit{{ creditBalance === 1 ? '' : 's' }} available
                                </span>
                            </div>
                            <TooltipProvider>
                                <div class="grid grid-cols-3 gap-2">
                                    <Tooltip v-for="opt in episodeOptions" :key="opt.count" :delay-duration="100">
                                        <TooltipTrigger as-child>
                                            <button
                                                type="button"
                                                :disabled="unlocked(opt) && !affordable(opt.count)"
                                                @click="selectOption(opt)"
                                                class="relative flex flex-col items-center gap-1 p-4 pt-5 rounded-xl border-2 text-center transition-all duration-200 overflow-hidden"
                                                :class="[
                                                    selectedEpisodes === opt.count
                                                        ? 'border-[#F5A000] bg-amber-50'
                                                        : 'border-[#DDDDDD] hover:border-[#F5A000]/50',
                                                    selectable(opt) ? 'cursor-pointer' : 'opacity-40 cursor-not-allowed',
                                                ]"
                                            >
                                                <span
                                                    v-if="!unlocked(opt)"
                                                    class="absolute top-0 inset-x-0 bg-[#1A1A1A] text-white text-[8px] font-bold uppercase tracking-wide py-0.5 truncate px-1"
                                                >
                                                    Buy {{ opt.unlock_label || 'Pro' }} to unlock
                                                </span>
                                                <Lock v-if="!unlocked(opt)" class="absolute top-6 right-2 w-3 h-3 text-[#AAAAAA]" />
                                                <span class="text-xl font-black text-[#1A1A1A]">{{ opt.count }}</span>
                                                <span class="text-[11px] text-[#555555]">episodes</span>
                                                <span v-if="!isUnlimited" class="text-[10px] text-[#AAAAAA]">{{ opt.count }} credits</span>
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent v-if="!unlocked(opt)" side="bottom" class="max-w-xs p-3 flex-col items-start gap-1">
                                            <p class="text-xs leading-relaxed text-white">
                                                <template v-if="opt.unlock_label">
                                                    Unlock {{ opt.count }}-episode stories with the
                                                    <strong class="font-semibold text-white">{{ opt.unlock_label }}</strong>.
                                                </template>
                                                <template v-else>
                                                    This episode count requires a higher pack.
                                                </template>
                                            </p>
                                            <Link :href="route('shop.index')" class="text-xs font-semibold text-[#F5A000] hover:underline">
                                                View packs →
                                            </Link>
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                            </TooltipProvider>
                            <p v-if="!isUnlimited && !canAffordSelected" class="text-xs text-red-600">
                                You don't have enough credits — you'll be taken to the shop to top up.
                            </p>
                        </div>

                        <!-- Format -->
                        <div class="space-y-3">
                            <Label class="text-[#1A1A1A] font-bold text-base block">Platform Options</Label>
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
                                <span class="text-[#F5A000] font-bold">{{ episodeCount }} Story</span>
                                episodes for
                                <span class="text-[#F5A000] font-bold">{{ basics.business_name }}</span>
                            </p>
                            <p class="text-xs text-[#555555] mt-1">
                                <template v-if="!isUnlimited">This costs 1 StoryBot credit per episode · </template>Takes 1-3 minutes
                            </p>
                        </div>

                        <Button
                            type="button"
                            @click="requestGenerate"
                            class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-12 rounded-xl transition-all duration-300 cursor-pointer"
                        >
                            <Sparkles class="w-4 h-4" />
                            Generate My Story
                        </Button>
                    </div>
                </div>
            </div>

        </div>

        <!-- Start interview confirmation -->
        <!-- Speak instead of type: voice typing guide -->
        <Dialog v-model:open="voiceGuideOpen">
            <DialogContent class="max-w-2xl p-0 gap-0 overflow-hidden">
                <DialogTitle class="sr-only">Voice Typing Guide</DialogTitle>
                <iframe
                    src="/guides/voice-typing-guide.html"
                    title="Voice Typing Guide"
                    class="w-full h-[80vh] border-0"
                />
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="confirmStartOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center mb-2">
                        <AlertTriangle class="w-5 h-5 text-[#F5A000]" />
                    </div>
                    <DialogTitle class="text-[#1A1A1A]">Are your details correct?</DialogTitle>
                    <DialogDescription class="text-[#555555]">
                        StoryBot will base your entire story on the business details and answers you provide.
                        Double-check your business name and links — you won't be able to change these once your story is generated.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="confirmStartOpen = false" class="cursor-pointer">Go back &amp; review</Button>
                    <Button
                        @click="confirmStartInterview"
                        class="bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-[#1A1A1A] font-bold cursor-pointer"
                    >
                        Yes, start my interview
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Generate confirmation -->
        <Dialog v-model:open="confirmGenerateOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center mb-2">
                        <Sparkles class="w-5 h-5 text-[#F5A000]" />
                    </div>
                    <DialogTitle class="text-[#1A1A1A]">Generate your story?</DialogTitle>
                    <DialogDescription as="div" class="text-[#555555]">
                        <p>
                            StoryBot will generate
                            <strong class="text-[#1A1A1A]">{{ episodeCount }} episodes</strong>
                            for <strong class="text-[#1A1A1A]">{{ basics.business_name }}</strong>.
                        </p>
                        <ul v-if="!isUnlimited" class="mt-2 space-y-1 list-disc list-inside">
                            <li>Current StoryBot Credits: <strong class="text-[#1A1A1A]">{{ creditBalance }}</strong></li>
                            <li>Cost: <strong class="text-[#1A1A1A]">{{ episodeCount }} credit{{ episodeCount === 1 ? '' : 's' }}</strong> (1 credit per episode)</li>
                            <li>Remaining Balance After Generation: <strong class="text-[#1A1A1A]">{{ creditBalance - episodeCount }} credits</strong></li>
                        </ul>
                        <p class="mt-2 text-xs">
                            Once confirmed, StoryBot will immediately begin generating your episodes.
                            <template v-if="!isUnlimited"> Credits used are non-refundable.</template>
                        </p>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="confirmGenerateOpen = false" class="cursor-pointer">Cancel</Button>
                    <Button
                        @click="confirmGenerate"
                        class="bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-[#1A1A1A] font-bold cursor-pointer"
                    >
                        Yes, generate
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
