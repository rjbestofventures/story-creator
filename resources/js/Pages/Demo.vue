<script setup>
import { ref, computed, nextTick, onUnmounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import {
    Tooltip, TooltipContent, TooltipProvider, TooltipTrigger,
} from '@/Components/ui/tooltip';
import { ArrowLeft, ArrowRight, Sparkles, Send, Check, Lock, Volume2, VolumeX, Loader2, Headphones, Square } from 'lucide-vue-next';
import AnnouncementBar from '@/Components/AnnouncementBar.vue';
import Footer from '@/Components/Footer.vue';
import PartnerApplyDialog from '@/Components/PartnerApplyDialog.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

// Logged-in users land here from their dashboard, not the homepage — send them
// back to the library instead of hardcoding '/' and losing where they came from.
const exitHref = computed(() => usePage().props.auth?.user ? route('stories.index') : '/');

// Signup is closed to non-partners for now: Get Started opens the partner
// application dialog instead of routing to /register.
const signUpOpen = ref(false);

// ─── Baked demo dataset — same for everyone, no backend, no AI tokens ─────────
const basics = {
    business_name: 'Barnacle Busters',
    industry: 'Yacht Maintenance',
    business_url: 'barnaclebusters.com',
    linkedin_url: 'linkedin.com/in/barnaclebusters',
    social_url: 'facebook.com/barnaclebusters',
    instagram_url: 'instagram.com/barnaclebusters',
    biography: 'Barnacle Busters offers Yacht Maintenance services Palm Beach, Broward, and Martin Counties with fully trained, experienced, and certified professional divers. Our large fleet of service vehicles will service your vessel right at your dock or marina.',
    services: 'Services include Underwater Inspections and Photography and one time boat bottom cleanings.',
};

// Hover tooltips describing each locked demo field, matching the live form.
const DEMO_LOCK_HINT = 'This is a pre-filled example for Barnacle Busters. The fields are locked - just click through to see how StoryBot works.';
// Website, Industry, LinkedIn, Facebook, and Instagram all share the same copy.
const ACCURATE_STORIES_HINT = 'By including this we can generate the most accurate stories.';
const fieldHints = {
    business_name: DEMO_LOCK_HINT,
    business_url:  ACCURATE_STORIES_HINT,
    industry:      ACCURATE_STORIES_HINT,
    linkedin_url:  ACCURATE_STORIES_HINT,
    social_url:    ACCURATE_STORIES_HINT,
    instagram_url: ACCURATE_STORIES_HINT,
    biography:     'By providing your biography or company history we can generate the most authentic outcomes.',
    services:      'Please include a short description of your primary services.',
};

// Chat transcript: alternating user/assistant, with synthetic "[…]" button markers
// between reactions and the next question — identical shape to the real interview.
const demoMessages = [
    { role: 'user',      content: 'Please begin the interview.' },
    { role: 'assistant', content: "Hi, I am your StoryCreator.Bot Assistant, or you can call me StoryBot! I'll ask you a few quick questions about Barnacle Busters, then turn your answers into a library of stories worth sharing. Ready?" },
    { role: 'user',      content: '[Ready to begin]' },
    { role: 'assistant', isQuestion: true, content: "How did you get into this business? You don't need to make it polished. Just tell it like you would explain it to someone over coffee." },
    { role: 'user',      content: "I started cleaning boats for extra money while I was young in Northeast. For coffee or a beer. And so while my friends were, you know, partying during the summer, I was cleaning boats. And, and yeah, that's how I started it." },
    { role: 'assistant', content: "I totally relate. I remember Brady Baxter started a lawnmowing business that he turned into a big landscaping firm. The guy seemed like a mogul by senior year. Hands on responsibility at a young age is all about natural character and work ethic." },
    { role: 'user',      content: '[Ready for next question]' },
    { role: 'assistant', isQuestion: true, content: 'What is one experience that changed the way you run your business today? Think of one specific customer, mistake, problem, mentor, job, opportunity, or turning point that taught you something you still use now.' },
    { role: 'user',      content: "My early success as a one man band, doing all the work myself,didn't live up the promise I made to go \"overboard\" for my customers…when i saw one of my first customers's boat getting scrubbed at a neighboring spit, as briny as the water was it was painfully clear that the busier i got the more my customers would be pissed." },
    { role: 'assistant', content: "That's a real turning point, understanding that going overboard for your customers meant the business had to grow beyond just you. And handing it to your son makes it a family story people will remember." },
    { role: 'user',      content: '[Ready for next question]' },
    { role: 'assistant', isQuestion: true, content: "What is something you believe about your business that you learned from experience?" },
    { role: 'user',      content: "If you take care of people first everything comes from that." },
    { role: 'assistant', content: "Customers first. Short and sweet. I think you've given me everything I need. I thank you for these thoughtful and revealing responses. Now, if you give me a moment, I'll process your stories! Are You Ready?" },
];

const demoEpisodes = [
    {
        episode_number: 1,
        title: 'It Started With a Bucket and a Beer',
        content: "I was just a kid growing up in the northeast, cleaning boats for extra money. Nothing glamorous. I'd do it for coffee, or a beer, whatever someone wanted to hand me.\n\nAnd while my friends were off partying all summer, I was in the water, scrubbing hulls.\n\nI didn't know it at the time, but that was the beginning of Barnacle Busters. No business plan, no big vision. Just a kid who didn't mind getting in the water and doing the work nobody else wanted to do.\n\nThat's really how it started.\n\nWhat's something you started just to make a little extra money that turned into something bigger? (bet you know people who started like me, I was all hard work, wanting to make people happy and pride).",
    },
    {
        episode_number: 2,
        title: 'Going Overboard Meant Growing Beyond Myself',
        content: "At some point I realized something. If I wanted to clean as many bottoms as I could and truly go overboard for my customers, it couldn't just be me anymore.\n\nOne person only has so many hours, so many dives in a day.\n\nSo I started to grow the business beyond myself. And as I got older, I got my family involved, and it became even bigger than I ever pictured back when I was that kid with a bucket.\n\nNow I'm proud to say my son, Rayan Danielle, is running it.\n\nThat's the part I'm most proud of. Not the fleet, not the counties we cover. The family.\n\nWhat would it take for your business to grow beyond just you?",
    },
    {
        episode_number: 3,
        title: 'Take Care of People First',
        content: "After all these years, all the boats, all the divers, all the growth, here's what I believe more than anything.\n\nIf you take care of people first, everything comes from that.\n\nThe customers, the crew, my own family. Take care of them first, do right by them, and the rest follows. The vessels get serviced right at the dock. The work gets done by people who are trained and certified and actually care.\n\nThat's not a slogan. That's just how we've always done it, since the beginning.\n\nI'm not jaded. I feel really lucky and maybe I'm a bit old fashioned but I still believe when you put people first it all comes back around. How about you?",
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

// A single reusable narration element. Browsers only "bless" a media element for
// unprompted playback the first time it plays from a user gesture; a brand-new
// Audio() per line would need its own gesture and go silent after the first one.
// Reusing this element — unlocked once by unlockAudio() on the Start click — keeps
// every later line (questions, answers, episodes) playing on a fresh browser.
const narrationEl = typeof Audio !== 'undefined' ? new Audio() : null;

// Global mute — persisted so it stays off/on across visits, same as the actual interview.
const speechMuted = ref(typeof localStorage !== 'undefined' && localStorage.getItem('sc_tts_muted') === '1');
const toggleMute = () => {
    speechMuted.value = !speechMuted.value;
    localStorage.setItem('sc_tts_muted', speechMuted.value ? '1' : '0');
    if (speechMuted.value) stopMsgSpeaking();
};

const stopMsgSpeaking = () => {
    narrationEl?.pause();
    speakMsgAudio = null;
    speakingMsgIdx.value = null;
};

// Fetches (or reuses the cached) TTS audio blob URL for a line, without touching
// the shared player. Safe to call for pre-warming while another line is playing.
// Returns the object URL, or null if synthesis failed.
const cacheAudioUrl = async (text, idx) => {
    if (speakMsgAudioUrls[idx]) return speakMsgAudioUrls[idx];

    loadingMsgIdx.value = idx;
    // Bound the request: TTS is a nice-to-have, so a slow or hung backend must
    // never block the interview. On timeout we abort and fall back to silent typing.
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 8000);
    try {
        const res = await fetch(route('demo.speak'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ text }),
            signal: controller.signal,
        });
        if (!res.ok) throw new Error('Text-to-speech failed.');
        const url = URL.createObjectURL(await res.blob());
        speakMsgAudioUrls[idx] = url;
        return url;
    } catch {
        return null; // preview feature — fail silently, the demo still works fine without audio
    } finally {
        clearTimeout(timeout);
        loadingMsgIdx.value = null;
    }
};

// Loads a line's audio into the shared narration element and returns it ready to
// play (metadata + finite duration resolved, playhead reset to the start), or null
// if synthesis failed or the source is undecodable — in which case callers fall
// back to silent typing.
const prepareNarration = async (text, idx) => {
    if (!narrationEl) return null;
    const url = await cacheAudioUrl(text, idx);
    if (!url) return null;

    if (narrationEl.src !== url) {
        narrationEl.src = url;
        const loaded = await withTimeout(new Promise(resolve => {
            narrationEl.addEventListener('loadedmetadata', () => resolve(true), { once: true });
            narrationEl.addEventListener('error', () => resolve(false), { once: true });
            if (narrationEl.readyState >= 1) resolve(true);
        }), 2000);
        if (!loaded || narrationEl.error || narrationEl.readyState < 1) return null;
    }

    await ensureFiniteDuration(narrationEl);
    narrationEl.currentTime = 0; // probing the duration may have left the head at the end
    return narrationEl;
};

// Resolves with the promise's value, or `undefined` after `ms` — used to keep
// audio preparation from ever stalling the typing animation.
const withTimeout = (promise, ms) => new Promise((resolve) => {
    let settled = false;
    const done = (val) => { if (!settled) { settled = true; resolve(val); } };
    const timer = setTimeout(() => done(undefined), ms);
    promise.then((val) => { clearTimeout(timer); done(val); });
});

// Blob-sourced MP3s often report duration as Infinity on loadedmetadata (no
// duration header until the browser scans the whole stream). Left alone, the
// typing pace below divides by Infinity, falls back to the slowest speed, and
// the on-screen text keeps animating long after the narration audio already
// ended — i.e. the tail of the answer silently "plays" with no voice. Seeking
// near the end forces Chrome/Firefox to resolve the real duration immediately.
const ensureFiniteDuration = (audio) => new Promise((resolve) => {
    if (Number.isFinite(audio.duration)) { resolve(); return; }
    let done = false;
    const finish = () => {
        if (done) return;
        done = true;
        clearTimeout(timer);
        audio.removeEventListener('timeupdate', onTimeUpdate);
        resolve();
    };
    const onTimeUpdate = () => { audio.currentTime = 0; finish(); };
    // Some browsers never emit `timeupdate` for a seek on a paused blob audio,
    // which would otherwise hang the interview — cap the wait and move on.
    const timer = setTimeout(finish, 1500);
    audio.addEventListener('timeupdate', onTimeUpdate);
    audio.currentTime = 1e101;
});

const playMsgAudio = async (msg, idx) => {
    const audio = await prepareNarration(msg.content, idx);
    if (!audio) return;
    speakingMsgIdx.value = idx;
    speakMsgAudio = audio;
    audio.onended = () => { if (speakingMsgIdx.value === idx) speakingMsgIdx.value = null; };
    audio.play().catch(() => {});
};

const toggleSpeakMessage = (msg, idx) => {
    // While "Listen to Your Story" is narrating, the only enabled episode button
    // is the one currently playing — clicking it stops the whole story instead
    // of just pausing this episode (which would strand the sequence).
    if (storyPlaying.value && typeof idx === 'string' && idx.startsWith('episode-')) {
        if (speakingMsgIdx.value === idx) toggleFullStory();
        return;
    }

    if (speakingMsgIdx.value === idx) { stopMsgSpeaking(); return; }
    stopMsgSpeaking();
    playMsgAudio(msg, idx);
};

// ─── "Listen to Your Story" — plays every demo episode back to back, auto-
// scrolling to and highlighting whichever one is currently narrating ──────────
const episodeCardEls = {};
const registerEpisodeCard = (el, episodeNumber) => { if (el) episodeCardEls[episodeNumber] = el; };

const storyPlaying = ref(false);
let storyPlayAbort = false;

const playEpisodeAndAwaitEnd = (ep) => new Promise(async (resolve) => {
    const key = `episode-${ep.episode_number}`;
    const audio = await prepareNarration(`${ep.title}. ${ep.content}`, key);
    if (!audio || storyPlayAbort) { resolve(); return; }
    speakingMsgIdx.value = key;
    speakMsgAudio = audio;
    audio.onended = () => { if (speakingMsgIdx.value === key) speakingMsgIdx.value = null; resolve(); };
    audio.play().catch(() => resolve()); // if playback is refused, don't strand the sequence
});

const toggleFullStory = async () => {
    if (storyPlaying.value) {
        storyPlayAbort = true;
        stopMsgSpeaking();
        storyPlaying.value = false;
        return;
    }

    storyPlayAbort = false;
    storyPlaying.value = true;

    for (const ep of demoEpisodes) {
        if (storyPlayAbort) break;
        episodeCardEls[ep.episode_number]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        await playEpisodeAndAwaitEnd(ep);
    }

    storyPlaying.value = false;
};

// Types `text` out while (unless muted) simultaneously narrating it via TTS, pacing the
// reveal speed so the text finishes right as the audio does, matching the sync behavior
// of the actual (non-demo) interview.
const typeWithSpeech = async (text, idx) => {
    if (speechMuted.value) {
        cacheAudioUrl(text, idx); // pre-warm the cache in the background so a later manual play is instant
        await typeOut(text);
        return;
    }

    stopMsgSpeaking();
    const audio = await prepareNarration(text, idx);
    if (!audio) { await typeOut(text); return; }

    speakingMsgIdx.value = idx;
    speakMsgAudio = audio;
    audio.onended = () => { if (speakingMsgIdx.value === idx) speakingMsgIdx.value = null; };
    audio.play().catch(() => {});
    await typeOut(text, audio.duration * 1000, audio);
};

onUnmounted(() => {
    narrationEl?.pause();
    storyPlayAbort = true;
    for (const url of Object.values(speakMsgAudioUrls)) URL.revokeObjectURL(url);
    if (silenceUrl) URL.revokeObjectURL(silenceUrl);
});

// Punctuation gets extra "weight" so the reveal briefly holds after sentence/clause
// boundaries instead of marching at a flat character rate — closer to natural speech
// rhythm without needing real per-word timestamps.
const PAUSE_WEIGHT = { '.': 6, '!': 6, '?': 6, '\n': 6, ',': 3, ';': 3, ':': 3, '—': 3 };
const weighTextForTyping = (text) => {
    const weights = new Array(text.length);
    let total = 0;
    for (let i = 0; i < text.length; i++) {
        weights[i] = 1 + (PAUSE_WEIGHT[text[i]] ?? 0);
        total += weights[i];
    }
    return { weights, total };
};

// Core reveal loop shared by the question and answer sides. When an `audio`
// element is supplied and actually playing, the reveal position is driven by the
// audio's real currentTime, so the text can never drift from the voice and the two
// always finish together. Until the audio starts (or if it never does, e.g. muted
// or autoplay-blocked), it falls back to a wall-clock estimate paced to durationMs.
const AUDIO_START_GRACE_MS = 800;
const pacedReveal = ({ text, durationMs, audio, apply, shouldSkip, onSkip }) => new Promise((resolve) => {
    if (!text) { resolve(); return; }

    const { weights, total: totalWeight } = weighTextForTyping(text);
    const prefix = new Array(text.length + 1).fill(0);
    for (let i = 0; i < text.length; i++) prefix[i + 1] = prefix[i] + weights[i];
    const avgWeight = totalWeight / text.length;

    const DEFAULT_CPS = 110;
    const MIN_CPS = 12;  // floor so long narration doesn't crawl unreadably slow
    const MAX_CPS = 110; // ceiling so short narration doesn't flash the text instantly
    const CPS = durationMs
        ? Math.min(MAX_CPS, Math.max(MIN_CPS, text.length / (durationMs / 1000)))
        : DEFAULT_CPS;
    const weightPerSec = CPS * avgWeight; // preserves the same total duration as flat pacing

    let i = 0;
    let elapsedWeight = 0;
    let lastTime = null;
    let audioStarted = false;
    let lastCt = -1;        // last observed audio.currentTime
    let lastProgress = 0;   // ts of the last currentTime advance
    const startedAt = performance.now();

    const tick = (ts) => {
        if (shouldSkip()) { apply(text); onSkip?.(); resolve(); return; }

        if (audio && audio.currentTime > 0) audioStarted = true;
        const audioUsable = audio && Number.isFinite(audio.duration) && audio.duration > 0;

        // Watch for a playback stall: if the audio starts but its clock stops
        // advancing (buffering, a paused/broken element), the reveal must not
        // freeze with it — fall back to wall-clock pacing after a short grace.
        if (audioStarted && audio.currentTime !== lastCt) { lastCt = audio.currentTime; lastProgress = ts; }
        const stalled = audioStarted && !audio.ended && lastProgress > 0 && (ts - lastProgress) > 1500;

        if (audioStarted && audioUsable && !stalled) {
            // Follow the real playback clock, revealing characters linearly with
            // elapsed audio — the voice itself supplies the pauses and rhythm, so
            // the text stays locked to it without any punctuation weighting.
            const target = Math.round((audio.currentTime / audio.duration) * text.length);
            if (target > i) i = Math.min(target, text.length);
            elapsedWeight = prefix[i]; // keep the wall-clock cursor in sync for a possible fallback
        } else if (audio && !audioStarted && (ts - startedAt) < AUDIO_START_GRACE_MS) {
            // Briefly hold at the start, giving the narration a moment to begin so
            // the text doesn't sprint ahead of a voice that's about to play.
        } else {
            // No audio, it never started, or it stalled — pace by wall clock (with
            // punctuation weighting to fake rhythm) so the reveal always completes.
            if (lastTime !== null) elapsedWeight += ((ts - lastTime) / 1000) * weightPerSec;
            while (i < text.length && prefix[i + 1] <= elapsedWeight) i++;
        }
        lastTime = ts;

        apply(text.slice(0, i));

        if (i < text.length) requestAnimationFrame(tick);
        else resolve();
    };
    requestAnimationFrame(tick);
});

// durationMs, when given, paces the reveal to finish alongside audio of that length
// (e.g. TTS narration) instead of the fixed default speed. Pass the audio element
// too to lock the reveal to the voice's real playback clock.
const typeOut = (text, durationMs = null, audio = null) => {
    typingText.value = '';
    isTyping.value = true;
    typingSkip.value = false;
    return pacedReveal({
        text, durationMs, audio,
        apply: (s) => { typingText.value = s; scrollDown(); },
        shouldSkip: () => typingSkip.value,
        onSkip: () => { typingSkip.value = false; },
    }).then(() => { isTyping.value = false; });
};

// 8s of "thinking" dots in the input box before the answer starts auto-typing — skippable.
const thinkAnswer = () => new Promise((resolve) => {
    isThinkingAnswer.value = true;
    const finish = () => { isThinkingAnswer.value = false; resolveThinkingAnswer = null; resolve(); };
    resolveThinkingAnswer = finish;
    setTimeout(() => { if (isThinkingAnswer.value) finish(); }, 8000);
});

// Reveals `text` into the input box, using the same audio-locked pacing as
// typeOut so the answer and question sides feel identical.
const revealInput = (text, durationMs = null, audio = null) => {
    currentInput.value = '';
    isTypingAnswer.value = true;
    answerTypingSkip.value = false;
    return pacedReveal({
        text, durationMs, audio,
        apply: (s) => { currentInput.value = s; },
        shouldSkip: () => answerTypingSkip.value,
        onSkip: () => { answerTypingSkip.value = false; stopMsgSpeaking(); }, // skipping the text skips the narration with it
    }).then(() => { isTypingAnswer.value = false; });
};

// Auto-types the customer's answer while narrating it in the customer voice,
// pacing the reveal to the audio — same sync behavior as the assistant's turns.
const typeOutInput = async (text, idx = null) => {
    const key = idx === null ? null : `answer-${idx}`;

    // Warm the cache during the "thinking" pause so playback starts promptly.
    if (key && !speechMuted.value) cacheAudioUrl(text, key);

    await thinkAnswer();

    if (!key || speechMuted.value) return revealInput(text);

    stopMsgSpeaking();
    const audio = await prepareNarration(text, key);
    if (!audio) return revealInput(text);

    speakingMsgIdx.value = key;
    speakMsgAudio = audio;
    audio.onended = () => { if (speakingMsgIdx.value === key) speakingMsgIdx.value = null; };
    audio.play().catch(() => {});
    return revealInput(text, audio.duration * 1000, audio);
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
    await typeWithSpeech(assistantMsg.content, displayLog.value.length);
    chatLog.value.push(assistantMsg);
};

// Browsers block programmatic audio until the page has played something from a
// user gesture. The first narration play() happens several awaits after the Start
// click, so on a fresh profile (no prior media engagement) it is blocked and the
// demo stays silent until the user manually taps a speaker. Playing a short silent
// clip synchronously inside the Start gesture unlocks playback for the session.
let audioUnlocked = false;
let silenceUrl = null;
const makeSilenceUrl = () => {
    const rate = 8000, samples = 800; // ~0.1s of 8-bit silence
    const buf = new ArrayBuffer(44 + samples);
    const view = new DataView(buf);
    const put = (o, s) => { for (let i = 0; i < s.length; i++) view.setUint8(o + i, s.charCodeAt(i)); };
    put(0, 'RIFF'); view.setUint32(4, 36 + samples, true); put(8, 'WAVE'); put(12, 'fmt ');
    view.setUint32(16, 16, true); view.setUint16(20, 1, true); view.setUint16(22, 1, true);
    view.setUint32(24, rate, true); view.setUint32(28, rate, true);
    view.setUint16(32, 1, true); view.setUint16(34, 8, true); put(36, 'data'); view.setUint32(40, samples, true);
    for (let i = 0; i < samples; i++) view.setUint8(44 + i, 128); // 128 = silence for unsigned 8-bit
    return URL.createObjectURL(new Blob([buf], { type: 'audio/wav' }));
};
const unlockAudio = () => {
    if (audioUnlocked || !narrationEl) return;
    audioUnlocked = true;
    try {
        if (!silenceUrl) silenceUrl = makeSilenceUrl();
        // Unlock the very element we reuse for narration, so every later line is
        // allowed to play without its own gesture.
        narrationEl.src = silenceUrl;
        narrationEl.play().then(() => { narrationEl.pause(); narrationEl.currentTime = 0; }).catch(() => {});
    } catch { /* best effort — falls back to tap-to-play */ }
};

const startInterview = async () => {
    unlockAudio(); // runs inside the click gesture — grants autoplay for the session
    phase.value = 1;
    chatLog.value = [];
    answerCount.value = 0;
    complete.value = false;
    const firstAssistant = demoMessages[1];
    position.value = 2;
    await playAssistantTurn(firstAssistant);
    currentTurn.value = nextMode();
    if (currentTurn.value.show_input) typeOutInput(demoMessages[position.value]?.content ?? '', position.value);
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
        if (currentTurn.value.show_input) typeOutInput(demoMessages[position.value]?.content ?? '', position.value);
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
                    :href="phase === 0 ? exitHref : undefined"
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
                            <template v-if="phase === 0">Live Demo — Barnacle Busters</template>
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
                    <button
                        v-if="phase === 1"
                        type="button"
                        @click="toggleMute"
                        :aria-label="speechMuted ? 'Unmute StoryBot voice' : 'Mute StoryBot voice'"
                        :title="speechMuted ? 'Unmute StoryBot voice' : 'Mute StoryBot voice'"
                        class="flex items-center justify-center w-7 h-7 rounded-lg border transition-all duration-150 cursor-pointer"
                        :class="speechMuted
                            ? 'border-[#DDDDDD] text-[#AAAAAA] hover:text-[#555555] hover:bg-gray-50'
                            : 'border-[#F5A000]/40 text-[#F5A000] bg-amber-50'"
                    >
                        <VolumeX v-if="speechMuted" class="w-3.5 h-3.5" />
                        <Volume2 v-else class="w-3.5 h-3.5" />
                    </button>
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
                    This is a pre-filled example for Barnacle Busters. The fields are locked - just click through to see how StoryBot works.
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
                    <div id="demo-tour-name" class="space-y-2">
                        <Label class="text-[#1A1A1A] font-semibold">Business Name</Label>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Input :model-value="basics.business_name" disabled class="h-11 bg-gray-100 text-[#555555] border-[#DDDDDD] cursor-not-allowed transition-colors hover:border-[#F5A000] hover:text-[#B87800]" />
                            </TooltipTrigger>
                            <TooltipContent>{{ fieldHints.business_name }}</TooltipContent>
                        </Tooltip>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div id="demo-tour-website" class="space-y-2">
                            <Label class="text-[#1A1A1A] font-semibold">Website</Label>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Input :model-value="basics.business_url" disabled class="h-11 bg-gray-100 text-[#555555] border-[#DDDDDD] cursor-not-allowed transition-colors hover:border-[#F5A000] hover:text-[#B87800]" />
                                </TooltipTrigger>
                                <TooltipContent>{{ fieldHints.business_url }}</TooltipContent>
                            </Tooltip>
                        </div>
                        <div id="demo-tour-industry" class="space-y-2">
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
                        <div id="demo-tour-linkedin" class="space-y-2">
                            <Label class="text-[#1A1A1A] font-semibold">LinkedIn</Label>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Input :model-value="basics.linkedin_url" disabled class="h-11 bg-gray-100 text-[#555555] border-[#DDDDDD] cursor-not-allowed transition-colors hover:border-[#F5A000] hover:text-[#B87800]" />
                                </TooltipTrigger>
                                <TooltipContent>{{ fieldHints.linkedin_url }}</TooltipContent>
                            </Tooltip>
                        </div>
                        <div id="demo-tour-social" class="space-y-2">
                            <Label class="text-[#1A1A1A] font-semibold">Facebook</Label>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Input :model-value="basics.social_url" disabled class="h-11 bg-gray-100 text-[#555555] border-[#DDDDDD] cursor-not-allowed transition-colors hover:border-[#F5A000] hover:text-[#B87800]" />
                                </TooltipTrigger>
                                <TooltipContent>{{ fieldHints.social_url }}</TooltipContent>
                            </Tooltip>
                        </div>
                    </div>

                    <div id="demo-tour-instagram" class="space-y-2">
                        <Label class="text-[#1A1A1A] font-semibold">Instagram</Label>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Input :model-value="basics.instagram_url" disabled class="h-11 bg-gray-100 text-[#555555] border-[#DDDDDD] cursor-not-allowed transition-colors hover:border-[#F5A000] hover:text-[#B87800]" />
                            </TooltipTrigger>
                            <TooltipContent>{{ fieldHints.instagram_url }}</TooltipContent>
                        </Tooltip>
                    </div>

                    <div id="demo-tour-about" class="space-y-2">
                        <Label class="text-[#1A1A1A] font-semibold">About the business</Label>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Textarea :model-value="basics.biography" disabled rows="3" class="bg-gray-100 text-[#555555] border-[#DDDDDD] resize-none cursor-not-allowed transition-colors hover:border-[#F5A000] hover:text-[#B87800]" />
                            </TooltipTrigger>
                            <TooltipContent>{{ fieldHints.biography }}</TooltipContent>
                        </Tooltip>
                    </div>

                    <div id="demo-tour-services" class="space-y-2">
                        <Label class="text-[#1A1A1A] font-semibold">Services</Label>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Textarea :model-value="basics.services" disabled rows="2" class="bg-gray-100 text-[#555555] border-[#DDDDDD] resize-none cursor-not-allowed transition-colors hover:border-[#F5A000] hover:text-[#B87800]" />
                            </TooltipTrigger>
                            <TooltipContent>{{ fieldHints.services }}</TooltipContent>
                        </Tooltip>
                    </div>

                    <Button
                        id="demo-tour-start"
                        type="button"
                        @click="startInterview"
                        class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-[#FFC837] to-[#F5A000] hover:bg-gradient-to-br text-white font-bold h-12 rounded-xl transition-all duration-300 cursor-pointer mt-2"
                    >
                        Start the Demo
                        <ArrowRight class="w-4 h-4" />
                    </Button>
                </div>
                </TooltipProvider>
            </div>
        </div>

        <!-- ─── PHASE 1: Interview replay ──────────────────────────────────── -->
        <div v-else-if="phase === 1" class="flex-1 min-h-0 overflow-hidden flex flex-col max-w-2xl mx-auto w-full px-4 py-4">
            <div class="flex-1 min-h-0 overflow-y-auto pb-4 pr-1">
              <div class="min-h-full flex flex-col justify-end space-y-4">
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
                            class="mt-2 flex items-center justify-center w-6 h-6 rounded-md transition-colors cursor-pointer disabled:cursor-wait"
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
            </div>

            <!-- Action area -->
            <div class="flex-shrink-0 mt-2">
                <div v-if="complete" class="bg-white border border-[#DDDDDD] rounded-2xl p-3 flex items-center justify-end gap-3">
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
                <p class="text-[#555555] mb-6">StoryCreator is writing episodes for <span class="font-semibold text-[#1A1A1A]">Barnacle Busters</span>.</p>
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
                    <h1 class="text-2xl font-black text-[#1A1A1A] mb-2">Barnacle Busters' story library</h1>
                    <p class="text-[#555555] mb-6">These Demo Episodes are written from the interview above.</p>

                    <button
                        type="button"
                        @click="toggleFullStory"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-full font-bold text-sm transition-all duration-200 cursor-pointer"
                        :class="storyPlaying
                            ? 'border-2 border-[#F5A000]/40 bg-amber-50 text-[#F5A000]'
                            : 'bg-gradient-to-r from-[#FFC837] to-[#F5A000] text-[#1A1A1A] hover:opacity-90'"
                    >
                        <Square v-if="storyPlaying" class="w-4 h-4 fill-current" />
                        <Headphones v-else class="w-4 h-4" />
                        {{ storyPlaying ? 'Stop Listening' : 'Listen to Your Story' }}
                    </button>
                </div>

                <div class="space-y-6">
                    <article
                        v-for="ep in demoEpisodes"
                        :key="ep.episode_number"
                        :ref="el => registerEpisodeCard(el, ep.episode_number)"
                        class="bg-white rounded-2xl border overflow-hidden transition-all duration-200"
                        :class="speakingMsgIdx === `episode-${ep.episode_number}` ? '' : 'border-[#DDDDDD]'"
                        :style="speakingMsgIdx === `episode-${ep.episode_number}`
                            ? 'border-color: rgba(245,160,0,0.4); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);'
                            : ''"
                    >
                        <div class="px-4 sm:px-6 pt-5 pb-4 border-b border-[#F5F5F5] space-y-3">
                            <div class="flex items-center gap-2">
                                <Badge class="bg-blue-50 text-blue-700 border-blue-200 text-xs font-semibold border shrink-0">
                                    Social Media
                                </Badge>
                                <span class="text-xs font-black bg-[#F5A000] text-white px-2.5 py-1 rounded-lg shrink-0">
                                    Episode {{ ep.episode_number }}
                                </span>
                                <button
                                    type="button"
                                    :disabled="loadingMsgIdx === `episode-${ep.episode_number}` || (storyPlaying && speakingMsgIdx !== `episode-${ep.episode_number}`)"
                                    :aria-label="speakingMsgIdx === `episode-${ep.episode_number}` ? 'Stop reading episode aloud' : 'Read episode aloud'"
                                    @click="toggleSpeakMessage({ content: `${ep.title}. ${ep.content}` }, `episode-${ep.episode_number}`)"
                                    class="ml-auto flex items-center justify-center w-8 h-8 rounded-lg transition-colors cursor-pointer disabled:cursor-wait disabled:opacity-40 shrink-0"
                                    :class="speakingMsgIdx === `episode-${ep.episode_number}` ? 'text-[#F5A000] bg-amber-50' : 'text-[#AAAAAA] hover:text-[#F5A000] hover:bg-amber-50'"
                                >
                                    <Loader2 v-if="loadingMsgIdx === `episode-${ep.episode_number}`" class="w-4 h-4 animate-spin" />
                                    <VolumeX v-else-if="speakingMsgIdx === `episode-${ep.episode_number}`" class="w-4 h-4" />
                                    <Volume2 v-else class="w-4 h-4" />
                                </button>
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
                        <button
                            v-if="canRegister"
                            type="button"
                            @click="signUpOpen = true"
                            class="inline-flex items-center gap-2 px-7 py-3.5 rounded-lg font-bold text-base transition hover:opacity-90 cursor-pointer"
                            style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                        >
                            Get Started <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </button>
                        <Link
                            :href="route('partner')"
                            class="inline-flex items-center gap-2 px-7 py-3.5 rounded-lg font-bold text-base border transition hover:opacity-90"
                            style="background-color: transparent; color: #FFFFFF; border-color: #444444;"
                        >
                            Learn More about Verified Partner
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <Footer v-if="phase !== 1" />

        <PartnerApplyDialog v-model:open="signUpOpen" />

    </div>
</template>
