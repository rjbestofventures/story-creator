<script setup>
import { ref, computed, nextTick, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import {
    Tooltip, TooltipContent, TooltipProvider, TooltipTrigger,
} from '@/Components/ui/tooltip';
import { ArrowLeft, ArrowRight, Sparkles, Send, Check, Lock, Volume2, VolumeX, Loader2 } from 'lucide-vue-next';
import AnnouncementBar from '@/Components/AnnouncementBar.vue';
import Footer from '@/Components/Footer.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

// ─── Baked demo dataset — same for everyone, no backend, no AI tokens ─────────
const basics = {
    business_name: 'Tammy Spa',
    industry: 'Health & Wellness',
    business_url: 'tammyspa.com',
    linkedin_url: 'linkedin.com/in/tammy-spa',
    social_url: 'instagram.com/tammyspa',
    biography: 'Founder-led wellness spa focused on personalized, science-backed care — peptides, biohacking, and protocols built around each client rather than a generic template.',
    services: 'Personalized wellness consultations, peptide therapy, biohacking protocols, recovery and longevity treatments.',
};

// Hover tooltips describing each locked demo field, matching the live form.
const DEMO_LOCK_HINT = 'This is a pre-filled example for Tammy Spa. The fields are locked — just click through to see how StoryBot works.';
const fieldHints = {
    business_name: DEMO_LOCK_HINT,
    business_url:  DEMO_LOCK_HINT,
    industry:      DEMO_LOCK_HINT,
    linkedin_url:  DEMO_LOCK_HINT,
    social_url:    DEMO_LOCK_HINT,
    biography:     'You will add a short bio about you and your business here.',
    services:      'You will add the services or products your business offers here.',
};

// Chat transcript: alternating user/assistant, with synthetic "[…]" button markers
// between reactions and the next question — identical shape to the real interview.
const demoMessages = [
    { role: 'user',      content: 'Please begin the interview.' },
    { role: 'assistant', content: "Hi, I am your StoryCreator.Bot Assistant, or you can call me StoryBot! I'll ask you a few quick questions about Tammy Spa, then turn your answers into a library of stories worth sharing. Ready?" },
    { role: 'user',      content: '[Ready to begin]' },
    { role: 'assistant', isQuestion: true, content: "How did you first get into wellness and fitness, and what was that turning point that made you realize this was going to be your life's work?" },
    { role: 'user',      content: "I grew up with every advantage. My family had money, I had access to the best trainers, nutritionists, the whole thing. But it wasn't until my mid twenties that I realized having access and actually understanding your body are two completely different things. I started diving deep into peptides, into biohacking, into understanding how the body actually works at a cellular level. I became obsessed. Not in a vain way, but in a way where I genuinely wanted to optimize every system. That's when it clicked for me that this knowledge, this passion, it had to mean something beyond just me looking good in a mirror. I wanted to help other people experience that same transformation, that same understanding of what their body is actually capable of when you treat it right." },
    { role: 'assistant', content: "There's something powerful in growing up with every advantage and still discovering that access and understanding are two completely different things. That obsession you found in your twenties, wanting to understand every system, is exactly the kind of origin story people trust." },
    { role: 'user',      content: '[Ready for next question]' },
    { role: 'assistant', isQuestion: true, content: 'Tell me about a specific moment when you realized your approach to wellness was different from everyone else doing this work.' },
    { role: 'user',      content: "There was this woman who came to me probably five years ago. She'd been to every spa, every wellness place in town. She was tired, frustrated, feeling like nobody really saw her or understood what she actually needed. We spent two hours just talking. Not selling, just talking. And I told her the truth about what would actually work for her body, her lifestyle, her goals. It wasn't the most expensive package. It wasn't what made us the most money. It was what she actually needed. She became a client for three years. She referred probably twenty people. That's when I understood that trust and honesty, they're the actual product. The treatments, the peptides, the protocols, they're just the delivery mechanism." },
    { role: 'assistant', content: "You walked away from the bigger sale to give her the truth, and she paid you back with three years and twenty referrals. That is the whole philosophy in one story, trust is the product and everything else is just delivery." },
    { role: 'user',      content: '[Ready for next question]' },
    { role: 'assistant', isQuestion: true, content: "What do you want people to know about Tammy's spa that they won't find anywhere else?" },
    { role: 'user',      content: "We don't believe in one size fits all. Every single person who walks through that door gets treated like we're solving a puzzle that's unique to them. Yeah, I'm into the latest science, the peptides, the cutting edge stuff. But I'm also into listening. I'm into understanding your life, your stress, your actual goals. And then we build something that works for you, not for some generic idea of wellness. That's the difference. That's why people come back." },
    { role: 'assistant', content: "That's a good question. That is everything I need. You've given me a lot to work with, the science, the listening, the way you refuse to treat anyone like a template. Give me a moment while I put your story library together." },
];

const demoEpisodes = [
    {
        episode_number: 1,
        title: 'Access Was Never Understanding',
        content: "I grew up with every advantage. Money, the best trainers, nutritionists, all of it. And for years I assumed that was the same thing as knowing my own body.\n\nIt wasn't.\n\nIt took until my mid twenties for that to click. I started going deep, into peptides, into biohacking, into how the body actually works at a cellular level. I became obsessed, not in a vain way, but in a way where I wanted to understand every system and how to make it better.\n\nSomewhere in there it stopped being about me looking good in a mirror. I realized this knowledge could change how other people feel in their own bodies too.\n\nThat's the moment Tammy Spa really started, long before it had a name.\n\nWhat's something you had access to for years before you truly understood it?",
    },
    {
        episode_number: 2,
        title: 'The Two Hours That Built Everything',
        content: "A woman came to me about five years ago. She'd been to every spa, every wellness place in town, and she was tired. Frustrated. Feeling like nobody actually saw her.\n\nWe spent two hours just talking. Not selling. Talking.\n\nAnd then I told her the truth about what would actually work for her body and her life. It wasn't the most expensive package. It wasn't what made us the most money. It was what she needed.\n\nShe stayed a client for three years. She referred close to twenty people.\n\nThat's when I understood something I build everything around now: trust and honesty are the actual product. The treatments, the peptides, the protocols, they're just how we deliver it.\n\nWhen was the last time someone gave you the honest answer instead of the expensive one?",
    },
    {
        episode_number: 3,
        title: "A Puzzle That's Only Yours",
        content: "Here's what you won't find anywhere else at Tammy Spa: we don't believe in one size fits all.\n\nEvery single person who walks through that door gets treated like we're solving a puzzle that's unique to them. Yes, I'm into the latest science, the peptides, the cutting edge protocols. But I'm just as into listening.\n\nInto understanding your life, your stress, your actual goals. And then building something that works for you, not for some generic idea of wellness.\n\nThat's the difference. That's why people come back, and why they bring the people they care about.\n\nYou are not a template here. You never will be.\n\nIf your wellness plan was built around your real life, what would you finally have room for?",
    },
];

// ─── Phase: 0 = basics, 1 = interview, 2 = generating, 3 = story library ──────
const phase = ref(0);

// ─── Chat replay state ────────────────────────────────────────────────────────
const chatLog = ref([]);
const currentInput = ref('');
const answerCount = ref(0);
const complete = ref(false);
const position = ref(0);
const chatBottom = ref(null);

const isTyping = ref(false);
const typingText = ref('');
const typingSkip = ref(false);
const typingIsQuestion = ref(false);
const typingQuestionNumber = ref(0);

const isTypingAnswer = ref(false);
const answerTypingSkip = ref(false);
const isThinkingAnswer = ref(false);
let resolveThinkingAnswer = null;

const isThinking = ref(false);
let resolveThinking = null;

const currentTurn = ref({ show_input: false, button_text: '', complete: false });

const displayLog = computed(() =>
    chatLog.value.filter(m => !(m.role === 'user' && m.content.startsWith('['))),
);

// Number the question turns so the amber highlight matches the live interview's numbering.
const enrichedDisplayLog = computed(() => {
    let qNum = 0;
    return displayLog.value.map(msg => {
        if (msg.role !== 'assistant' || !msg.isQuestion) return msg;
        qNum++;
        return { ...msg, _questionNumber: qNum };
    });
});

const progress = computed(() => {
    if (phase.value === 0) return 0;
    if (phase.value >= 2) return 100;
    return Math.min(Math.round((answerCount.value / 3) * 100), 90);
});

const scrollDown = () => {
    nextTick(() => chatBottom.value?.scrollIntoView({ behavior: 'smooth' }));
};

// ─── Text-to-voice — read any assistant bubble aloud (whitelisted lines only) ─
const speakingMsgIdx = ref(null);
const loadingMsgIdx  = ref(null);
let speakMsgAudio     = null;
const speakMsgAudioUrls = {}; // idx -> cached object URL

const stopMsgSpeaking = () => {
    speakMsgAudio?.pause();
    speakMsgAudio = null;
    speakingMsgIdx.value = null;
};

const toggleSpeakMessage = async (msg, idx) => {
    if (speakingMsgIdx.value === idx) { stopMsgSpeaking(); return; }
    stopMsgSpeaking();

    if (speakMsgAudioUrls[idx]) {
        speakingMsgIdx.value = idx;
        speakMsgAudio = new Audio(speakMsgAudioUrls[idx]);
        speakMsgAudio.onended = () => { if (speakingMsgIdx.value === idx) speakingMsgIdx.value = null; };
        speakMsgAudio.play();
        return;
    }

    loadingMsgIdx.value = idx;
    try {
        const res = await fetch(route('demo.speak'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ text: msg.content }),
        });
        if (!res.ok) throw new Error('Text-to-speech failed.');

        const url = URL.createObjectURL(await res.blob());
        speakMsgAudioUrls[idx] = url;

        speakingMsgIdx.value = idx;
        speakMsgAudio = new Audio(url);
        speakMsgAudio.onended = () => { if (speakingMsgIdx.value === idx) speakingMsgIdx.value = null; };
        speakMsgAudio.play();
    } catch {
        // preview feature — fail silently, the demo still works fine without audio
    } finally {
        loadingMsgIdx.value = null;
    }
};

onUnmounted(() => {
    speakMsgAudio?.pause();
    for (const url of Object.values(speakMsgAudioUrls)) URL.revokeObjectURL(url);
});

const typeOut = (text) => new Promise((resolve) => {
    typingText.value = '';
    isTyping.value = true;
    typingSkip.value = false;
    let i = 0;
    let lastTime = null;
    const CHARS_PER_SEC = 110;

    const tick = (ts) => {
        if (typingSkip.value) {
            typingText.value = text;
            isTyping.value = false;
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

// 3s of "thinking" dots in the input box before the answer starts auto-typing — skippable.
const thinkAnswer = () => new Promise((resolve) => {
    isThinkingAnswer.value = true;
    const finish = () => { isThinkingAnswer.value = false; resolveThinkingAnswer = null; resolve(); };
    resolveThinkingAnswer = finish;
    setTimeout(() => { if (isThinkingAnswer.value) finish(); }, 3000);
});

const typeOutInput = async (text) => {
    await thinkAnswer();
    return new Promise((resolve) => {
        currentInput.value = '';
        isTypingAnswer.value = true;
        answerTypingSkip.value = false;
        let i = 0;
        let lastTime = null;
        const CHARS_PER_SEC = 110;

        const tick = (ts) => {
            if (answerTypingSkip.value) {
                currentInput.value = text;
                isTypingAnswer.value = false;
                answerTypingSkip.value = false;
                resolve();
                return;
            }
            if (lastTime !== null) {
                const add = Math.max(1, Math.floor(((ts - lastTime) / 1000) * CHARS_PER_SEC));
                i = Math.min(i + add, text.length);
                currentInput.value = text.slice(0, i);
            }
            lastTime = ts;
            if (i < text.length) requestAnimationFrame(tick);
            else { isTypingAnswer.value = false; resolve(); }
        };
        requestAnimationFrame(tick);
    });
};

const nextMode = () => {
    const upcoming = demoMessages[position.value];
    if (!upcoming) return { show_input: false, button_text: '', complete: true };
    if (upcoming.content.startsWith('[')) {
        return { show_input: false, button_text: answerCount.value === 0 ? 'Get started' : 'Next question', complete: false };
    }
    return { show_input: true, button_text: '', complete: false };
};

// 3s of "thinking" dots before the typing effect starts — skippable, same as typing.
const think = () => new Promise((resolve) => {
    isThinking.value = true;
    const finish = () => { isThinking.value = false; resolveThinking = null; resolve(); };
    resolveThinking = finish;
    setTimeout(() => { if (isThinking.value) finish(); }, 3000);
});

const questionCountSoFar = () => chatLog.value.filter(m => m.role === 'assistant' && m.isQuestion).length;

const playAssistantTurn = async (assistantMsg) => {
    typingIsQuestion.value = !!assistantMsg.isQuestion;
    typingQuestionNumber.value = assistantMsg.isQuestion ? questionCountSoFar() + 1 : 0;
    scrollDown();
    await think();
    scrollDown();
    await typeOut(assistantMsg.content);
    chatLog.value.push(assistantMsg);
};

const startInterview = async () => {
    phase.value = 1;
    chatLog.value = [];
    answerCount.value = 0;
    complete.value = false;
    const firstAssistant = demoMessages[1];
    position.value = 2;
    await playAssistantTurn(firstAssistant);
    currentTurn.value = nextMode();
    if (currentTurn.value.show_input) typeOutInput(demoMessages[position.value]?.content ?? '');
    scrollDown();
};

const advance = async () => {
    if (isThinking.value) { resolveThinking?.(); return; }
    if (isTyping.value) { typingSkip.value = true; return; }
    if (isThinkingAnswer.value) { resolveThinkingAnswer?.(); return; }
    if (isTypingAnswer.value) { answerTypingSkip.value = true; return; }

    const userMsg = demoMessages[position.value];
    const assistantMsg = demoMessages[position.value + 1];
    if (!userMsg) return;

    chatLog.value.push(userMsg);
    if (!userMsg.content.startsWith('[')) answerCount.value++;
    currentInput.value = '';
    scrollDown();

    if (!assistantMsg) {
        finishInterview();
        return;
    }

    await playAssistantTurn(assistantMsg);
    position.value += 2;

    if (position.value >= demoMessages.length) {
        finishInterview();
    } else {
        currentTurn.value = nextMode();
        if (currentTurn.value.show_input) typeOutInput(demoMessages[position.value]?.content ?? '');
    }
    scrollDown();
};

const finishInterview = () => {
    complete.value = true;
    currentTurn.value = { show_input: false, button_text: '', complete: true };
    scrollDown();
};

// ─── Generate (templated — reveals baked episodes after a short loader) ───────
const generate = () => {
    phase.value = 2;
    setTimeout(() => { phase.value = 3; }, 2600);
};

const goBack = () => {
    if (phase.value === 1) phase.value = 0;
    else if (phase.value === 3) phase.value = 1;
};
</script>

<template>
    <Head title="Live Demo — StoryCreator.Bot" />

    <div class="bg-[#FAFAF8] flex flex-col" :class="phase !== 1 && 'min-h-screen'" :style="phase === 1 ? 'height: 100vh; height: 100dvh; overflow: hidden;' : ''">

        <AnnouncementBar />

        <!-- Top bar -->
        <div class="bg-white border-b border-[#DDDDDD] px-4 md:px-8 py-4 flex-shrink-0">
            <div class="max-w-2xl mx-auto flex items-center justify-between gap-4">
                <component
                    :is="phase === 0 ? Link : 'button'"
                    :href="phase === 0 ? '/' : undefined"
                    type="button"
                    @click="phase > 0 ? goBack() : undefined"
                    class="flex items-center gap-2 text-sm text-[#555555] hover:text-[#1A1A1A] transition-colors shrink-0 cursor-pointer"
                >
                    <ArrowLeft class="w-4 h-4" />
                    <span class="hidden sm:inline">Back</span>
                </component>

                <div class="flex-1 max-w-sm">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-semibold text-[#555555]">
                            <template v-if="phase === 0">Live Demo — Tammy Spa</template>
                            <template v-else-if="phase === 1">{{ complete ? 'Interview complete' : `${answerCount} of 3 answered` }}</template>
                            <template v-else>Your story library</template>
                        </span>
                        <span class="text-xs text-[#AAAAAA]">{{ progress }}%</span>
                    </div>
                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-[#FFC837] to-[#F5A000] rounded-full transition-all duration-500" :style="{ width: progress + '%' }" />
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-[#FFC837] to-[#F5A000] flex items-center justify-center">
                        <Sparkles class="w-3.5 h-3.5 text-white" />
                    </div>
                    <span class="hidden sm:block text-sm font-bold text-[#1A1A1A]">StoryBot</span>
                </div>
            </div>
        </div>

        <!-- ─── PHASE 0: Basics (all fields disabled / read-only) ──────────── -->
        <div v-if="phase === 0" class="flex-1 flex items-start justify-center px-4 py-10">
            <div class="w-full max-w-lg">
                <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-[#555555]">
                    <span class="font-bold text-[#1A1A1A]">Demo mode.</span>
                    This is a pre-filled example for Tammy Spa. The fields are locked — just click through to see how StoryBot works.
                </div>

                <div class="mb-8 text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-50 mb-4">
                        <Sparkles class="w-7 h-7 text-[#F5A000]" />
                    </div>
                    <h1 class="text-2xl font-black text-[#1A1A1A] mb-2">See StoryBot in action</h1>
                    <p class="text-[#555555]">Here's a business we've already filled in. Start the interview to watch StoryBot work.</p>
                </div>

                <TooltipProvider :delay-duration="150">
                <div class="bg-white rounded-2xl border border-[#DDDDDD] p-6 space-y-5">
                    <div class="space-y-2">
                        <Label class="text-[#1A1A1A] font-semibold">Business Name</Label>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Input :model-value="basics.business_name" disabled class="h-11 bg-gray-100 text-[#555555] border-[#DDDDDD] cursor-not-allowed transition-colors hover:border-[#F5A000] hover:text-[#B87800]" />
                            </TooltipTrigger>
                            <TooltipContent>{{ fieldHints.business_name }}</TooltipContent>
                        </Tooltip>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label class="text-[#1A1A1A] font-semibold">Website</Label>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Input :model-value="basics.business_url" disabled class="h-11 bg-gray-100 text-[#555555] border-[#DDDDDD] cursor-not-allowed transition-colors hover:border-[#F5A000] hover:text-[#B87800]" />
                                </TooltipTrigger>
                                <TooltipContent>{{ fieldHints.business_url }}</TooltipContent>
                            </Tooltip>
                        </div>
                        <div class="space-y-2">
                            <Label class="text-[#1A1A1A] font-semibold">Industry</Label>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Input :model-value="basics.industry" disabled class="h-11 bg-gray-100 text-[#555555] border-[#DDDDDD] cursor-not-allowed transition-colors hover:border-[#F5A000] hover:text-[#B87800]" />
                                </TooltipTrigger>
                                <TooltipContent>{{ fieldHints.industry }}</TooltipContent>
                            </Tooltip>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label class="text-[#1A1A1A] font-semibold">LinkedIn</Label>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Input :model-value="basics.linkedin_url" disabled class="h-11 bg-gray-100 text-[#555555] border-[#DDDDDD] cursor-not-allowed transition-colors hover:border-[#F5A000] hover:text-[#B87800]" />
                                </TooltipTrigger>
                                <TooltipContent>{{ fieldHints.linkedin_url }}</TooltipContent>
                            </Tooltip>
                        </div>
                        <div class="space-y-2">
                            <Label class="text-[#1A1A1A] font-semibold">Facebook / Instagram</Label>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Input :model-value="basics.social_url" disabled class="h-11 bg-gray-100 text-[#555555] border-[#DDDDDD] cursor-not-allowed transition-colors hover:border-[#F5A000] hover:text-[#B87800]" />
                                </TooltipTrigger>
                                <TooltipContent>{{ fieldHints.social_url }}</TooltipContent>
                            </Tooltip>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-[#1A1A1A] font-semibold">About the business</Label>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Textarea :model-value="basics.biography" disabled rows="3" class="bg-gray-100 text-[#555555] border-[#DDDDDD] resize-none cursor-not-allowed transition-colors hover:border-[#F5A000] hover:text-[#B87800]" />
                            </TooltipTrigger>
                            <TooltipContent>{{ fieldHints.biography }}</TooltipContent>
                        </Tooltip>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-[#1A1A1A] font-semibold">Services</Label>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Textarea :model-value="basics.services" disabled rows="2" class="bg-gray-100 text-[#555555] border-[#DDDDDD] resize-none cursor-not-allowed transition-colors hover:border-[#F5A000] hover:text-[#B87800]" />
                            </TooltipTrigger>
                            <TooltipContent>{{ fieldHints.services }}</TooltipContent>
                        </Tooltip>
                    </div>

                    <Button
                        type="button"
                        @click="startInterview"
                        class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-12 rounded-xl transition-all duration-300 cursor-pointer mt-2"
                    >
                        Start the Interview
                        <ArrowRight class="w-4 h-4" />
                    </Button>
                </div>
                </TooltipProvider>
            </div>
        </div>

        <!-- ─── PHASE 1: Interview replay ──────────────────────────────────── -->
        <div v-else-if="phase === 1" class="flex-1 min-h-0 overflow-hidden flex flex-col max-w-2xl mx-auto w-full px-4 py-4">
            <div class="flex-1 min-h-0 flex flex-col justify-end space-y-4 overflow-y-auto pb-4 pr-1">
                <div
                    v-for="(msg, i) in enrichedDisplayLog"
                    :key="i"
                    class="flex gap-3"
                    :class="msg.role === 'user' ? 'flex-row-reverse' : ''"
                >
                    <div v-if="msg.role === 'assistant'" class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-[#FFC837] to-[#F5A000] flex items-center justify-center mt-0.5">
                        <Sparkles class="w-3.5 h-3.5 text-white" />
                    </div>
                    <div
                        class="max-w-[80%] px-4 py-3 rounded-2xl text-sm leading-relaxed whitespace-pre-wrap"
                        :class="msg.role === 'assistant'
                            ? (msg._questionNumber ? 'bg-amber-50 border border-amber-200 text-amber-800 font-semibold rounded-tl-sm' : 'bg-white border border-[#DDDDDD] text-[#1A1A1A] rounded-tl-sm')
                            : 'bg-[#1A1A1A] text-white rounded-tr-sm'"
                    >
                        <span v-if="msg._questionNumber" class="text-amber-500 mr-1.5">{{ msg._questionNumber }}.</span>{{ msg.content }}

                        <button
                            v-if="msg.role === 'assistant'"
                            type="button"
                            :disabled="loadingMsgIdx === i"
                            :aria-label="speakingMsgIdx === i ? 'Stop reading aloud' : 'Read aloud'"
                            @click="toggleSpeakMessage(msg, i)"
                            class="mt-1.5 inline-flex items-center justify-center w-6 h-6 rounded-md transition-colors cursor-pointer disabled:cursor-wait"
                            :class="speakingMsgIdx === i ? 'text-[#F5A000] bg-amber-50' : 'text-[#AAAAAA] hover:text-[#F5A000] hover:bg-amber-50'"
                        >
                            <Loader2 v-if="loadingMsgIdx === i" class="w-3.5 h-3.5 animate-spin" />
                            <VolumeX v-else-if="speakingMsgIdx === i" class="w-3.5 h-3.5" />
                            <Volume2 v-else class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Thinking bubble: 3s of dots before typing starts -->
                <div v-if="isThinking" class="flex gap-3 cursor-pointer select-none" @click="resolveThinking?.()">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-[#FFC837] to-[#F5A000] flex items-center justify-center mt-0.5">
                        <Sparkles class="w-3.5 h-3.5 text-white" />
                    </div>
                    <div class="bg-white border border-[#DDDDDD] px-4 py-3.5 rounded-2xl rounded-tl-sm flex items-center gap-1.5">
                        <span class="w-2 h-2 bg-[#DDDDDD] rounded-full animate-bounce" style="animation-delay:0ms" />
                        <span class="w-2 h-2 bg-[#DDDDDD] rounded-full animate-bounce" style="animation-delay:150ms" />
                        <span class="w-2 h-2 bg-[#DDDDDD] rounded-full animate-bounce" style="animation-delay:300ms" />
                    </div>
                </div>

                <!-- Typing bubble -->
                <div v-if="isTyping" class="flex gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-[#FFC837] to-[#F5A000] flex items-center justify-center mt-0.5">
                        <Sparkles class="w-3.5 h-3.5 text-white" />
                    </div>
                    <div
                        class="max-w-[80%] px-4 py-3 rounded-2xl rounded-tl-sm text-sm leading-relaxed whitespace-pre-wrap"
                        :class="typingIsQuestion ? 'bg-amber-50 border border-amber-200 text-amber-800 font-semibold' : 'bg-white border border-[#DDDDDD] text-[#1A1A1A]'"
                    >
                        <span v-if="typingIsQuestion" class="text-amber-500 mr-1.5">{{ typingQuestionNumber }}.</span>{{ typingText }}<span class="inline-block w-0.5 h-[1em] bg-[#F5A000] align-middle animate-pulse ml-0.5" />
                    </div>
                </div>

                <div ref="chatBottom" />
            </div>

            <!-- Action area -->
            <div class="flex-shrink-0 mt-2">
                <div v-if="complete" class="bg-white border border-[#DDDDDD] rounded-2xl p-3 flex items-center justify-between gap-3">
                    <p class="text-sm text-[#555555]">Thank you for completing the interview. Ready to see the story library.</p>
                    <button
                        type="button"
                        @click="generate"
                        class="flex-shrink-0 flex items-center gap-2 h-9 px-4 rounded-xl font-bold text-sm cursor-pointer transition-all duration-200"
                        style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                    >
                        Generate My Story
                        <ArrowRight class="w-3.5 h-3.5" />
                    </button>
                </div>

                <div v-else class="bg-white border border-amber-300 bg-amber-50/30 rounded-2xl p-3 flex gap-3 items-end">
                    <div v-if="isThinkingAnswer" class="flex-1 flex items-center gap-1.5 py-2 cursor-pointer select-none" @click="resolveThinkingAnswer?.()">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:0ms" />
                        <span class="w-1.5 h-1.5 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:150ms" />
                        <span class="w-1.5 h-1.5 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:300ms" />
                    </div>
                    <Textarea
                        v-else
                        :model-value="currentTurn.show_input ? currentInput : ''"
                        readonly
                        rows="2"
                        :placeholder="currentTurn.show_input ? '' : ''"
                        class="flex-1 resize-none border-0 focus:ring-0 focus:outline-none text-sm text-[#1A1A1A] bg-transparent p-0 cursor-default select-none"
                        :class="!currentTurn.show_input ? 'opacity-30' : ''"
                    />
                    <button
                        type="button"
                        @click="advance"
                        class="flex-shrink-0 flex items-center justify-center font-bold text-sm transition-all duration-300 cursor-pointer"
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

        <!-- ─── PHASE 2: Generating loader ─────────────────────────────────── -->
        <div v-else-if="phase === 2" class="flex-1 flex items-center justify-center px-4 py-10">
            <div class="text-center max-w-sm">
                <div class="relative inline-flex items-center justify-center mb-8">
                    <div class="absolute w-24 h-24 rounded-full bg-amber-100 animate-ping opacity-40" />
                    <div class="absolute w-20 h-20 rounded-full bg-amber-100 animate-ping opacity-30" style="animation-delay: 300ms" />
                    <div class="relative w-16 h-16 rounded-2xl bg-gradient-to-br from-[#FFC837] to-[#F5A000] flex items-center justify-center shadow-lg">
                        <Sparkles class="w-8 h-8 text-white animate-pulse" />
                    </div>
                </div>
                <h2 class="text-2xl font-black text-[#1A1A1A] mb-2">Crafting your story…</h2>
                <p class="text-[#555555] mb-6">StoryCreator is writing episodes for <span class="font-semibold text-[#1A1A1A]">Tammy Spa</span>.</p>
                <div class="flex items-center justify-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:0ms" />
                    <span class="w-2.5 h-2.5 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:150ms" />
                    <span class="w-2.5 h-2.5 rounded-full bg-[#F5A000] animate-bounce" style="animation-delay:300ms" />
                </div>
            </div>
        </div>

        <!-- ─── PHASE 3: Story library (templated episodes) ────────────────── -->
        <div v-else class="flex-1 px-4 py-10">
            <div class="w-full max-w-2xl mx-auto">
                <div class="mb-8 text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-50 mb-4">
                        <Check class="w-7 h-7 text-[#F5A000]" />
                    </div>
                    <h1 class="text-2xl font-black text-[#1A1A1A] mb-2">Tammy Spa's story library</h1>
                    <p class="text-[#555555]">These Demo Episodes are written from the interview above.</p>
                </div>

                <div class="space-y-6">
                    <article
                        v-for="ep in demoEpisodes"
                        :key="ep.episode_number"
                        class="bg-white rounded-2xl border border-[#DDDDDD] overflow-hidden"
                    >
                        <div class="px-4 sm:px-6 pt-5 pb-4 border-b border-[#F5F5F5] space-y-3">
                            <div class="flex items-center gap-2">
                                <Badge class="bg-blue-50 text-blue-700 border-blue-200 text-xs font-semibold border shrink-0">
                                    Social Media
                                </Badge>
                                <span class="text-xs font-black bg-[#F5A000] text-white px-2.5 py-1 rounded-lg shrink-0">
                                    Episode {{ ep.episode_number }}
                                </span>
                            </div>
                        </div>
                        <div class="px-4 sm:px-6 py-5">
                            <h2 class="text-xl font-black text-[#1A1A1A] mb-3">{{ ep.title }}</h2>
                            <div class="text-[#333333] text-[15px] leading-[1.8] whitespace-pre-wrap">{{ ep.content }}</div>
                        </div>
                    </article>
                </div>

                <!-- Convert CTA -->
                <div class="mt-8 rounded-2xl p-6 text-center" style="background-color: #1A1A1A;">
                    <div class="inline-flex items-center gap-2 mb-2">
                        <Lock class="w-4 h-4" style="color: #F5A000;" />
                        <span class="text-xs font-bold uppercase tracking-widest" style="color: #888888;">This Preview was created for demonstration purposes only</span>
                    </div>
                    <h3 class="text-xl font-black text-white mb-2">Are you ready to tell your story?</h3>
                    <p class="text-sm mb-6" style="color: #CCCCCC;">Answer a few questions about your business and StoryCreator.Bot builds your full library, in your voice.</p>
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center gap-2 px-7 py-3.5 rounded-lg font-bold text-base transition hover:opacity-90"
                            style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                        >
                            Get Started <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </Link>
                        <Link
                            :href="route('partner')"
                            class="inline-flex items-center gap-2 px-7 py-3.5 rounded-lg font-bold text-base border transition hover:opacity-90"
                            style="background-color: transparent; color: #FFFFFF; border-color: #444444;"
                        >
                            Become a Verified Partner
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <Footer v-if="phase !== 1" />

    </div>
</template>
