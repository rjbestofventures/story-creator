<script setup>
import { ref, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardList, MessageSquare, Headphones, Square, Loader2 } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    interview: Object,
});

const normalizeUrl = (url) => (url.startsWith('http') ? url : `https://${url}`);

const hasProfile = props.interview.business_name || props.interview.industry
    || props.interview.business_url || props.interview.linkedin_url || props.interview.social_url
    || props.interview.biography || props.interview.services;

// ─── Listen to Questions & Answers ───────────────────────────────────────────
// Plays every pair back to back — question then answer — highlighting and
// scrolling to whichever pair is currently being read. Questions always use the
// interview bot's own voice; only the answers follow the Male/Female toggle.
const playing     = ref(false);
const speakingKey = ref(null); // `${number}:${part}` currently playing
const loadingKey  = ref(null); // `${number}:${part}` currently being synthesized
const speakError  = ref(null);
const answerVoice = ref('female');

let audio     = null;
let playAbort = false;
const audioUrls = {}; // `${number}:${part}:${voice}` -> object URL, cached so replays don't re-synthesize

const cacheKey = (number, part) => `${number}:${part}:${part === 'question' ? 'bot' : answerVoice.value}`;

const isSpeaking   = (number, part) => speakingKey.value === `${number}:${part}`;
const isLoading    = (number, part) => loadingKey.value === `${number}:${part}`;
const isActivePair = (number) => speakingKey.value?.startsWith(`${number}:`) || loadingKey.value?.startsWith(`${number}:`);

const pairEls = {};
const registerPairEl = (el, pair) => { if (el) pairEls[pair.number] = el; };

const synthesize = async (number, part) => {
    const key = cacheKey(number, part);
    if (audioUrls[key]) return audioUrls[key];
    try {
        const res = await fetch(route('stories.answers.speak', props.interview.story_id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ number, part, voice: answerVoice.value }),
        });
        if (!res.ok) throw new Error('Text-to-speech failed.');
        const url = URL.createObjectURL(await res.blob());
        audioUrls[key] = url;
        return url;
    } catch {
        return null;
    }
};

const playPart = (number, part) => new Promise(async (resolve) => {
    const marker = `${number}:${part}`;
    loadingKey.value = marker;
    const url = await synthesize(number, part);
    loadingKey.value = null;

    if (!url || playAbort) {
        if (!url) speakError.value = 'Could not read this aloud. Please try again.';
        resolve();
        return;
    }

    speakingKey.value = marker;
    audio = new Audio(url);
    audio.onended = () => { if (speakingKey.value === marker) speakingKey.value = null; resolve(); };
    audio.play();
});

const stopPlayback = () => {
    playAbort = true;
    audio?.pause();
    audio = null;
    speakingKey.value = null;
    loadingKey.value = null;
    playing.value = false;
};

const togglePlayback = async () => {
    if (playing.value) { stopPlayback(); return; }

    speakError.value = null;
    playAbort = false;
    playing.value = true;

    for (const pair of props.interview.pairs) {
        if (playAbort) break;
        pairEls[pair.number]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        await playPart(pair.number, 'question');
        if (playAbort || !pair.answer) continue;
        await playPart(pair.number, 'answer');
    }

    playing.value = false;
};

// Switching voice mid-playback would leave the rest of the run in the old voice,
// so stop first and let the user start again in the new one.
const setAnswerVoice = (voice) => {
    if (answerVoice.value === voice) return;
    if (playing.value) stopPlayback();
    answerVoice.value = voice;
};

onUnmounted(() => {
    playAbort = true;
    audio?.pause();
    for (const url of Object.values(audioUrls)) URL.revokeObjectURL(url);
});
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`${interview.business_name || 'Interview'} — My Answers`" />

        <div class="max-w-3xl mx-auto px-4 md:px-8 py-6 md:py-10">

            <!-- Back -->
            <Link
                :href="route('stories.show', interview.story_id)"
                class="flex items-center gap-1.5 text-xs mb-6 transition hover:opacity-70"
                style="color: #555555;"
            >
                <ArrowLeft class="w-3.5 h-3.5" />
                Back to your story
            </Link>

            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-black text-[#1A1A1A] leading-tight">My Answers</h1>
                <p class="text-sm mt-1" style="color: #555555;">The answers you gave during your interview for {{ interview.business_name || 'your business' }}.</p>
            </div>

            <!-- Business Profile -->
            <div v-if="hasProfile" class="bg-white rounded-2xl px-6 py-5 mb-5" style="border: 1px solid #DDDDDD;">
                <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: #888888;">Business Profile</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 mb-4">
                    <div v-if="interview.business_name">
                        <p class="text-[10px] font-bold uppercase tracking-wide" style="color: #999999;">Business Name</p>
                        <p class="text-sm text-[#1A1A1A]">{{ interview.business_name }}</p>
                    </div>
                    <div v-if="interview.industry">
                        <p class="text-[10px] font-bold uppercase tracking-wide" style="color: #999999;">Industry</p>
                        <p class="text-sm text-[#1A1A1A]">{{ interview.industry }}</p>
                    </div>
                    <div v-if="interview.business_url">
                        <p class="text-[10px] font-bold uppercase tracking-wide" style="color: #999999;">Website</p>
                        <a :href="normalizeUrl(interview.business_url)" target="_blank" rel="noopener" class="text-sm hover:underline break-all" style="color: #F5A000;">{{ interview.business_url }}</a>
                    </div>
                    <div v-if="interview.linkedin_url">
                        <p class="text-[10px] font-bold uppercase tracking-wide" style="color: #999999;">LinkedIn</p>
                        <a :href="normalizeUrl(interview.linkedin_url)" target="_blank" rel="noopener" class="text-sm hover:underline break-all" style="color: #F5A000;">{{ interview.linkedin_url }}</a>
                    </div>
                    <div v-if="interview.social_url">
                        <p class="text-[10px] font-bold uppercase tracking-wide" style="color: #999999;">Facebook</p>
                        <a :href="normalizeUrl(interview.social_url)" target="_blank" rel="noopener" class="text-sm hover:underline break-all" style="color: #F5A000;">{{ interview.social_url }}</a>
                    </div>
                    <div v-if="interview.instagram_url">
                        <p class="text-[10px] font-bold uppercase tracking-wide" style="color: #999999;">Instagram</p>
                        <a :href="normalizeUrl(interview.instagram_url)" target="_blank" rel="noopener" class="text-sm hover:underline break-all" style="color: #F5A000;">{{ interview.instagram_url }}</a>
                    </div>
                </div>

                <div v-if="interview.biography" class="mb-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide mb-1" style="color: #999999;">About the business</p>
                    <p class="text-sm leading-relaxed" style="color: #333333;">{{ interview.biography }}</p>
                </div>
                <div v-if="interview.services">
                    <p class="text-[10px] font-bold uppercase tracking-wide mb-1" style="color: #999999;">Services</p>
                    <p class="text-sm leading-relaxed" style="color: #333333;">{{ interview.services }}</p>
                </div>
            </div>

            <!-- Listen controls -->
            <div v-if="interview.pairs.length > 0" class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <button
                    type="button"
                    @click="togglePlayback"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full font-bold text-sm transition-all duration-200 cursor-pointer"
                    :class="playing
                        ? 'border-2 border-[#F5A000]/40 bg-amber-50 text-[#F5A000]'
                        : 'bg-gradient-to-r from-[#FFC837] to-[#F5A000] text-[#1A1A1A] hover:opacity-90'"
                >
                    <Square v-if="playing" class="w-4 h-4 fill-current" />
                    <Headphones v-else class="w-4 h-4" />
                    {{ playing ? 'Stop Listening' : 'Listen to Questions & Answers' }}
                </button>

                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold" style="color: #888888;">Answer voice</span>
                    <div class="inline-flex rounded-full p-0.5 bg-white" style="border: 1px solid #DDDDDD;">
                        <button
                            v-for="option in [{ id: 'male', label: 'Male' }, { id: 'female', label: 'Female' }]"
                            :key="option.id"
                            type="button"
                            @click="setAnswerVoice(option.id)"
                            class="px-4 py-1.5 rounded-full text-xs font-bold transition-colors cursor-pointer"
                            :class="answerVoice === option.id
                                ? 'bg-[#F5A000] text-[#1A1A1A]'
                                : 'text-[#555555] hover:text-[#1A1A1A]'"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Playback error -->
            <div v-if="speakError" class="mb-5 flex items-center justify-between gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-2.5">
                <p class="text-sm text-red-600">{{ speakError }}</p>
                <button type="button" @click="speakError = null" class="text-red-400 hover:text-red-600 cursor-pointer text-xs font-semibold">Dismiss</button>
            </div>

            <!-- Q&A pairs -->
            <div class="space-y-3">
                <div
                    v-for="pair in interview.pairs"
                    :key="pair.number"
                    :ref="(el) => registerPairEl(el, pair)"
                    class="bg-white rounded-xl overflow-hidden"
                    :style="isActivePair(pair.number) ? 'border: 1px solid #F5A000;' : 'border: 1px solid #DDDDDD;'"
                >
                    <!-- Question -->
                    <div
                        class="flex gap-3 px-5 py-4"
                        :style="isSpeaking(pair.number, 'question')
                            ? 'background-color: #FDF1DA; border-bottom: 1px solid #F0F0F0;'
                            : 'background-color: #FEFBF3; border-bottom: 1px solid #F0F0F0;'"
                    >
                        <span class="text-xs font-black w-6 shrink-0 text-center pt-0.5" style="color: #F5A000;">{{ pair.number }}</span>
                        <p class="text-sm font-semibold text-[#1A1A1A] leading-relaxed flex-1">{{ pair.question }}</p>
                        <Loader2 v-if="isLoading(pair.number, 'question')" class="w-4 h-4 shrink-0 mt-0.5 animate-spin" style="color: #F5A000;" />
                        <Headphones v-else-if="isSpeaking(pair.number, 'question')" class="w-4 h-4 shrink-0 mt-0.5" style="color: #F5A000;" />
                    </div>
                    <!-- Answer -->
                    <div
                        class="flex gap-3 px-5 py-4"
                        :style="isSpeaking(pair.number, 'answer') ? 'background-color: #FFFBF0;' : 'background-color: #FFFFFF;'"
                    >
                        <MessageSquare class="w-4 h-4 shrink-0 mt-0.5" style="color: #999999;" />
                        <p
                            v-if="pair.answer"
                            class="text-sm leading-relaxed whitespace-pre-wrap flex-1"
                            style="color: #333333;"
                        >{{ pair.answer }}</p>
                        <p v-else class="text-sm italic flex-1" style="color: #999999;">No answer recorded.</p>
                        <Loader2 v-if="isLoading(pair.number, 'answer')" class="w-4 h-4 shrink-0 mt-0.5 animate-spin" style="color: #F5A000;" />
                        <Headphones v-else-if="isSpeaking(pair.number, 'answer')" class="w-4 h-4 shrink-0 mt-0.5" style="color: #F5A000;" />
                    </div>
                </div>

                <!-- Empty -->
                <div v-if="interview.pairs.length === 0" class="bg-white rounded-2xl py-16 text-center" style="border: 1px solid #DDDDDD;">
                    <ClipboardList class="w-8 h-8 mx-auto mb-3 opacity-40" style="color: #999999;" />
                    <p class="text-sm font-semibold text-[#1A1A1A]">No questions answered yet</p>
                    <p class="text-xs mt-1" style="color: #999999;">This interview hasn't captured any answers.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
