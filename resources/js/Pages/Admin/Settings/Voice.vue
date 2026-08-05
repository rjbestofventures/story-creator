<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { Volume2, Play, Square, Loader2, FlaskConical, KeyRound, Eye, EyeOff, CircleCheck, CircleDot } from '@lucide/vue';

const props = defineProps({
    tts_voice: String,
    tts_instructions: String,
    demo_bot_voice: String,
    demo_customer_voice: String,
    voices: Array,
    elevenlabs_api_key: String,
    elevenlabs_env_key_set: Boolean,
});

const form = useForm({
    tts_voice: props.tts_voice,
    tts_instructions: props.tts_instructions,
    demo_bot_voice: props.demo_bot_voice,
    demo_customer_voice: props.demo_customer_voice,
    elevenlabs_api_key: props.elevenlabs_api_key,
});

const showElevenKey = ref(false);

const saved = ref(false);
const save = () => form.post(route('admin.settings.voice.update'));
watch(() => form.recentlySuccessful, v => {
    if (v) { saved.value = true; setTimeout(() => saved.value = false, 2500); }
});

// The demo narrates two sides of the interview, each in its own voice.
const demoPickers = [
    { key: 'demo_bot_voice', title: 'StoryBot Questions & Responses', desc: "The assistant's side of the demo interview." },
    { key: 'demo_customer_voice', title: 'Customer Answers', desc: 'The answers that auto-type into the demo input box.' },
];

// ─── Preview playback — hear a short sample in a given voice + tone ───────────
// Ids are scoped per picker ("<field>:<voice>") so the same voice chosen in two
// grids doesn't light up both play buttons at once.
const previewingId    = ref(null); // scoped id currently playing
const loadingPreview  = ref(null); // scoped id currently being synthesized
let previewAudio      = null;

const stopPreview = () => {
    previewAudio?.pause();
    previewAudio = null;
    previewingId.value = null;
};

const previewVoice = async (voiceId, scope = 'tts_voice') => {
    const scopedId = `${scope}:${voiceId}`;
    if (previewingId.value === scopedId) { stopPreview(); return; }
    stopPreview();

    loadingPreview.value = scopedId;
    try {
        const res = await fetch(route('admin.settings.voice.preview'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ voice: voiceId, instructions: form.tts_instructions }),
        });
        if (!res.ok) throw new Error('Preview failed.');

        const url = URL.createObjectURL(await res.blob());
        previewingId.value = voiceId;
        previewAudio = new Audio(url);
        previewAudio.onended = () => { if (previewingId.value === voiceId) previewingId.value = null; };
        previewAudio.play();
    } catch {
        // preview is a convenience, fail silently
    } finally {
        loadingPreview.value = null;
    }
};

onUnmounted(() => previewAudio?.pause());

// ─── ElevenLabs voice test — evaluating voice quality only, not wired into the
// live TTS flow. Separate from everything above on purpose. ───────────────────
const elevenVoices = ref([]);
const elevenVoicesError = ref(null);
const elevenLoadingVoices = ref(false);
const elevenVoiceId = ref('');
const elevenText = ref("Here's what StoryCreator generated from your interview. Twelve episodes, ready to publish.");
const elevenPlaying = ref(false);
const elevenLoadingPreview = ref(false);
const elevenError = ref(null);
let elevenAudio = null;

// Falls back to this known-good premade voice ("George") if the key can't list
// voices — lets testing continue without the voices_read permission.
const ELEVEN_FALLBACK_VOICE_ID = 'JBFqnCBsd6RMkjVDRZzb';

const loadElevenVoices = async () => {
    elevenLoadingVoices.value = true;
    elevenVoicesError.value = null;
    try {
        const res = await fetch(route('admin.settings.voice.elevenlabs-voices'));
        if (!res.ok) throw new Error();
        elevenVoices.value = await res.json();
        elevenVoiceId.value = elevenVoices.value[0]?.id ?? '';
    } catch {
        elevenVoicesError.value = "Couldn't list voices — this API key likely doesn't have the \"voices_read\" permission (add it in ElevenLabs → API Keys, or paste a voice ID manually below; find one on elevenlabs.io/app/voice-library).";
        elevenVoiceId.value = ELEVEN_FALLBACK_VOICE_ID;
    } finally {
        elevenLoadingVoices.value = false;
    }
};

const stopEleven = () => {
    elevenAudio?.pause();
    elevenAudio = null;
    elevenPlaying.value = false;
};

const playEleven = async () => {
    if (elevenPlaying.value) { stopEleven(); return; }

    elevenError.value = null;
    elevenLoadingPreview.value = true;
    try {
        const res = await fetch(route('admin.settings.voice.elevenlabs-preview'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ voice_id: elevenVoiceId.value, text: elevenText.value }),
        });
        if (!res.ok) throw new Error();

        const url = URL.createObjectURL(await res.blob());
        elevenAudio = new Audio(url);
        elevenAudio.onended = () => { elevenPlaying.value = false; };
        elevenPlaying.value = true;
        elevenAudio.play();
    } catch {
        elevenError.value = 'Synthesis failed. Check the API key and voice selection.';
    } finally {
        elevenLoadingPreview.value = false;
    }
};

onMounted(loadElevenVoices);
onUnmounted(stopEleven);
</script>

<template>
    <SettingsLayout>
        <Head title="Voice — Settings" />

        <div class="space-y-1 mb-6">
            <h1 class="text-xl font-black" style="color:#1A1A1A;">Voice</h1>
            <p class="text-sm" style="color:#555555;">Choose the voice and tone StoryBot uses to read episodes and chat messages aloud.</p>
        </div>

        <form @submit.prevent="save" class="space-y-4">

            <!-- ─── Voice selection ──────────────────────────────────────── -->
            <div class="bg-white rounded-2xl p-6" style="border:1px solid #DDDDDD;">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#FEF9EC;">
                        <Volume2 class="w-4 h-4" style="color:#F5A000;" />
                    </div>
                    <div>
                        <h2 class="text-sm font-black" style="color:#1A1A1A;">Voice</h2>
                        <p class="text-xs" style="color:#555555;">Powers text-to-speech for episodes and interview chat bubbles. Click play to preview.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div
                        v-for="v in voices"
                        :key="v.id"
                        class="flex items-center gap-1 pl-3 pr-1.5 py-1.5 rounded-xl border-2 transition-all duration-150"
                        :style="form.tts_voice === v.id ? 'border-color:#F5A000; background:#FFFBF0;' : 'border-color:#DDDDDD; background:#FFFFFF;'"
                    >
                        <button
                            type="button"
                            @click="form.tts_voice = v.id"
                            class="flex-1 flex flex-col items-start text-left cursor-pointer py-1"
                        >
                            <span class="text-xs font-bold leading-tight" :style="form.tts_voice === v.id ? 'color:#F5A000' : 'color:#1A1A1A'">{{ v.label }}</span>
                            <span class="text-xs mt-0.5" style="color:#AAAAAA;">{{ v.desc }}</span>
                        </button>
                        <button
                            type="button"
                            :disabled="loadingPreview === `tts_voice:${v.id}`"
                            @click="previewVoice(v.id, 'tts_voice')"
                            class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg cursor-pointer transition-colors disabled:cursor-wait"
                            :class="previewingId === `tts_voice:${v.id}` ? 'text-[#F5A000] bg-amber-50' : 'text-[#AAAAAA] hover:text-[#F5A000] hover:bg-amber-50'"
                        >
                            <Loader2 v-if="loadingPreview === `tts_voice:${v.id}`" class="w-4 h-4 animate-spin" />
                            <Square v-else-if="previewingId === `tts_voice:${v.id}`" class="w-3.5 h-3.5 fill-current" />
                            <Play v-else class="w-4 h-4" fill="currentColor" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── Demo voices — one per side of the demo interview ──────── -->
            <div
                v-for="picker in demoPickers"
                :key="picker.key"
                class="bg-white rounded-2xl p-6"
                style="border:1px solid #DDDDDD;"
            >
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#FEF9EC;">
                        <Volume2 class="w-4 h-4" style="color:#F5A000;" />
                    </div>
                    <div>
                        <h2 class="text-sm font-black" style="color:#1A1A1A;">{{ picker.title }}</h2>
                        <p class="text-xs" style="color:#555555;">{{ picker.desc }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div
                        v-for="v in voices"
                        :key="v.id"
                        class="flex items-center gap-1 pl-3 pr-1.5 py-1.5 rounded-xl border-2 transition-all duration-150"
                        :style="form[picker.key] === v.id ? 'border-color:#F5A000; background:#FFFBF0;' : 'border-color:#DDDDDD; background:#FFFFFF;'"
                    >
                        <button
                            type="button"
                            @click="form[picker.key] = v.id"
                            class="flex-1 flex flex-col items-start text-left cursor-pointer py-1"
                        >
                            <span class="text-xs font-bold leading-tight" :style="form[picker.key] === v.id ? 'color:#F5A000' : 'color:#1A1A1A'">{{ v.label }}</span>
                            <span class="text-xs mt-0.5" style="color:#AAAAAA;">{{ v.desc }}</span>
                        </button>
                        <button
                            type="button"
                            :disabled="loadingPreview === `${picker.key}:${v.id}`"
                            @click="previewVoice(v.id, picker.key)"
                            class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg cursor-pointer transition-colors disabled:cursor-wait"
                            :class="previewingId === `${picker.key}:${v.id}` ? 'text-[#F5A000] bg-amber-50' : 'text-[#AAAAAA] hover:text-[#F5A000] hover:bg-amber-50'"
                        >
                            <Loader2 v-if="loadingPreview === `${picker.key}:${v.id}`" class="w-4 h-4 animate-spin" />
                            <Square v-else-if="previewingId === `${picker.key}:${v.id}`" class="w-3.5 h-3.5 fill-current" />
                            <Play v-else class="w-4 h-4" fill="currentColor" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── Speaking style ───────────────────────────────────────── -->
            <div class="bg-white rounded-2xl p-6" style="border:1px solid #DDDDDD;">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#FEF9EC;">
                        <Volume2 class="w-4 h-4" style="color:#F5A000;" />
                    </div>
                    <div>
                        <h2 class="text-sm font-black" style="color:#1A1A1A;">Speaking Style</h2>
                        <p class="text-xs" style="color:#555555;">Steers pacing, warmth, and delivery. Describe how StoryBot should sound.</p>
                    </div>
                </div>

                <textarea
                    v-model="form.tts_instructions"
                    rows="4"
                    placeholder="e.g. Speak in a warm, natural, conversational human tone..."
                    class="w-full px-3 py-2.5 rounded-lg text-sm outline-none transition-all resize-none"
                    style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
                    @focus="e => (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                    @blur="e => (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                />
            </div>

            <!-- ─── ElevenLabs API Key ──────────────────────────────────── -->
            <div class="bg-white rounded-2xl p-6" style="border:1px solid #DDDDDD;">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#FEF9EC;">
                        <KeyRound class="w-4 h-4" style="color:#F5A000;" />
                    </div>
                    <div>
                        <h2 class="text-sm font-black" style="color:#1A1A1A;">ElevenLabs API Key</h2>
                        <p class="text-xs" style="color:#555555;">Powers the voice test below. Database value takes priority over <code class="font-mono text-xs px-1 rounded" style="background:#F5F5F5;">.env</code>.</p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-bold" style="color:#1A1A1A;">Secret key</label>
                        <span
                            class="flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                            :style="form.elevenlabs_api_key
                                ? 'background:#F0FDF4; color:#16A34A;'
                                : elevenlabs_env_key_set
                                    ? 'background:#F5F5F5; color:#555555;'
                                    : 'background:#FEF2F2; color:#DC2626;'"
                        >
                            <CircleCheck v-if="form.elevenlabs_api_key" class="w-3 h-3" />
                            <CircleDot v-else class="w-3 h-3" />
                            {{ form.elevenlabs_api_key ? 'Database override' : elevenlabs_env_key_set ? 'Using .env' : 'Not set' }}
                        </span>
                    </div>
                    <div class="relative">
                        <input
                            v-model="form.elevenlabs_api_key"
                            :type="showElevenKey ? 'text' : 'password'"
                            placeholder="sk_..."
                            class="w-full pr-10 pl-3 py-2.5 rounded-lg text-sm font-mono outline-none transition-all duration-200"
                            style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
                            @focus="e => (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                            @blur="e => (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                        />
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer hover:opacity-70" style="color:#AAAAAA;" @click="showElevenKey = !showElevenKey">
                            <EyeOff v-if="showElevenKey" class="w-4 h-4" />
                            <Eye v-else class="w-4 h-4" />
                        </button>
                    </div>
                    <p class="text-xs" style="color:#888888;">Save changes below, then reload this page so the voice test picks up the new key.</p>
                </div>
            </div>

            <!-- ─── ElevenLabs test — not wired into the live TTS flow ────── -->
            <div class="rounded-2xl p-6" style="border:1px dashed #F5A000; background:#FFFBF0;">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#FEF3D0;">
                        <FlaskConical class="w-4 h-4" style="color:#F5A000;" />
                    </div>
                    <div>
                        <h2 class="text-sm font-black" style="color:#1A1A1A;">ElevenLabs Voice Test</h2>
                        <p class="text-xs" style="color:#555555;">For evaluating voice quality only — has no effect on the live site.</p>
                    </div>
                </div>

                <p v-if="elevenVoicesError" class="text-xs mb-3" style="color:#EF4444;">{{ elevenVoicesError }}</p>

                <div class="flex flex-col gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color:#1A1A1A;">Voice</label>
                        <select
                            v-if="!elevenVoicesError"
                            v-model="elevenVoiceId"
                            :disabled="elevenLoadingVoices || elevenVoices.length === 0"
                            class="w-full px-3 py-2 rounded-lg text-sm outline-none"
                            style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
                        >
                            <option v-if="elevenLoadingVoices" value="">Loading voices…</option>
                            <option v-for="v in elevenVoices" :key="v.id" :value="v.id">{{ v.name }}</option>
                        </select>
                        <input
                            v-else
                            v-model="elevenVoiceId"
                            type="text"
                            placeholder="Paste a voice ID, e.g. JBFqnCBsd6RMkjVDRZzb"
                            class="w-full px-3 py-2 rounded-lg text-sm outline-none font-mono"
                            style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color:#1A1A1A;">Sample text</label>
                        <textarea
                            v-model="elevenText"
                            rows="3"
                            maxlength="1000"
                            class="w-full px-3 py-2.5 rounded-lg text-sm outline-none resize-none"
                            style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
                        />
                    </div>

                    <p v-if="elevenError" class="text-xs" style="color:#EF4444;">{{ elevenError }}</p>

                    <button
                        type="button"
                        :disabled="elevenLoadingPreview || !elevenVoiceId || !elevenText"
                        @click="playEleven"
                        class="self-start flex items-center gap-2 px-4 py-2 rounded-lg font-bold text-xs cursor-pointer transition-opacity disabled:opacity-40 disabled:cursor-not-allowed"
                        style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                    >
                        <Loader2 v-if="elevenLoadingPreview" class="w-3.5 h-3.5 animate-spin" />
                        <Square v-else-if="elevenPlaying" class="w-3.5 h-3.5 fill-current" />
                        <Play v-else class="w-3.5 h-3.5" fill="currentColor" />
                        {{ elevenPlaying ? 'Stop' : 'Play sample' }}
                    </button>
                </div>
            </div>

            <!-- ─── Save ──────────────────────────────────────────────────── -->
            <div class="flex items-center gap-3">
                <button
                    type="submit"
                    :disabled="form.processing || !form.isDirty"
                    class="px-5 py-2.5 rounded-lg font-bold text-sm transition-opacity duration-200 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                    style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                >
                    {{ form.processing ? 'Saving…' : 'Save changes' }}
                </button>
                <span v-if="saved" class="text-sm font-medium" style="color:#16A34A;">Saved!</span>
            </div>

        </form>

    </SettingsLayout>
</template>
