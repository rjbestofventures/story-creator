<script setup>
import { ref, watch, onUnmounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { Volume2, Play, Square, Loader2 } from '@lucide/vue';

const props = defineProps({
    tts_voice: String,
    tts_instructions: String,
    voices: Array,
});

const form = useForm({
    tts_voice: props.tts_voice,
    tts_instructions: props.tts_instructions,
});

const saved = ref(false);
const save = () => form.post(route('admin.settings.voice.update'));
watch(() => form.recentlySuccessful, v => {
    if (v) { saved.value = true; setTimeout(() => saved.value = false, 2500); }
});

// ─── Preview playback — hear a short sample in a given voice + tone ───────────
const previewingId    = ref(null); // voice id currently playing
const loadingPreview  = ref(null); // voice id currently being synthesized
let previewAudio      = null;

const stopPreview = () => {
    previewAudio?.pause();
    previewAudio = null;
    previewingId.value = null;
};

const previewVoice = async (voiceId) => {
    if (previewingId.value === voiceId) { stopPreview(); return; }
    stopPreview();

    loadingPreview.value = voiceId;
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
</script>

<template>
    <SettingsLayout>
        <Head title="Voice — Settings" />

        <div class="space-y-1 mb-6">
            <h1 class="text-xl font-black" style="color:#1A1A1A;">Voice</h1>
            <p class="text-sm" style="color:#555555;">Choose the voice and tone StoryBot uses to read chapters and chat messages aloud.</p>
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
                        <p class="text-xs" style="color:#555555;">Powers text-to-speech for chapters and interview chat bubbles. Click play to preview.</p>
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
                            :disabled="loadingPreview === v.id"
                            @click="previewVoice(v.id)"
                            class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg cursor-pointer transition-colors disabled:cursor-wait"
                            :class="previewingId === v.id ? 'text-[#F5A000] bg-amber-50' : 'text-[#AAAAAA] hover:text-[#F5A000] hover:bg-amber-50'"
                        >
                            <Loader2 v-if="loadingPreview === v.id" class="w-4 h-4 animate-spin" />
                            <Square v-else-if="previewingId === v.id" class="w-3.5 h-3.5 fill-current" />
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
