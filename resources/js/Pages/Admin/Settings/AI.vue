<script setup>
import { ref, watch, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { Cpu, Eye, EyeOff, RefreshCcw, CircleCheck, CircleDot, Loader2, TriangleAlert } from '@lucide/vue';

const props = defineProps({
    anthropic_api_key:       String,
    env_key_set:             Boolean,
    interview_model:         String,
    generation_model:        String,
    interview_price_input:   Number,
    interview_price_output:  Number,
    generation_price_input:  Number,
    generation_price_output: Number,
});

const form = useForm({
    anthropic_api_key:       props.anthropic_api_key,
    interview_model:         props.interview_model,
    generation_model:        props.generation_model,
    interview_price_input:   props.interview_price_input,
    interview_price_output:  props.interview_price_output,
    generation_price_input:  props.generation_price_input,
    generation_price_output: props.generation_price_output,
});

const showKey    = ref(false);
const saved      = ref(false);
const models     = ref([]);
const fetching   = ref(false);
const fetchError = ref('');

const save = () => form.post(route('admin.settings.ai.update'));
watch(() => form.recentlySuccessful, v => {
    if (v) { saved.value = true; setTimeout(() => saved.value = false, 2500); }
});

const fetchModels = async () => {
    fetching.value   = true;
    fetchError.value = '';
    try {
        const res  = await fetch(route('admin.settings.ai.models'));
        const data = await res.json();
        if (data.error) {
            fetchError.value = data.error;
        } else {
            models.value = data.models;
        }
    } catch {
        fetchError.value = 'Could not reach the Anthropic API. Check your key and try again.';
    } finally {
        fetching.value = false;
    }
};

onMounted(() => {
    if (props.anthropic_api_key || props.env_key_set) {
        fetchModels();
    }
});

const phases = [
    { key: 'interview_model',  label: 'Interview Phase',  hint: 'Conducts the 15-question chat with the business owner.' },
    { key: 'generation_model', label: 'Story Generation', hint: 'Writes the final episodes. Stronger models produce better output.' },
];
</script>

<template>
    <SettingsLayout>
        <Head title="AI Models — Settings" />

        <div class="space-y-1 mb-6">
            <h1 class="text-xl font-black" style="color:#1A1A1A;">AI Models</h1>
            <p class="text-sm" style="color:#555555;">Configure your Anthropic API key and choose which Claude model powers each phase.</p>
        </div>

        <form @submit.prevent="save" class="space-y-4">

            <!-- ─── API Key card ─────────────────────────────────────────── -->
            <div class="bg-white rounded-2xl p-6" style="border:1px solid #DDDDDD;">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#FEF9EC;">
                        <Cpu class="w-4 h-4" style="color:#F5A000;" />
                    </div>
                    <div>
                        <h2 class="text-sm font-black" style="color:#1A1A1A;">Anthropic API Key</h2>
                        <p class="text-xs" style="color:#555555;">Database value takes priority over <code class="font-mono text-xs px-1 rounded" style="background:#F5F5F5;">.env</code>.</p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-bold" style="color:#1A1A1A;">Secret key</label>
                        <span
                            class="flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                            :style="form.anthropic_api_key
                                ? 'background:#F0FDF4; color:#16A34A;'
                                : env_key_set
                                    ? 'background:#F5F5F5; color:#555555;'
                                    : 'background:#FEF2F2; color:#DC2626;'"
                        >
                            <CircleCheck v-if="form.anthropic_api_key" class="w-3 h-3" />
                            <CircleDot v-else class="w-3 h-3" />
                            {{ form.anthropic_api_key ? 'Database override' : env_key_set ? 'Using .env' : 'Not set' }}
                        </span>
                    </div>
                    <div class="relative">
                        <input
                            v-model="form.anthropic_api_key"
                            :type="showKey ? 'text' : 'password'"
                            placeholder="sk-ant-api03-..."
                            class="w-full pr-10 pl-3 py-2.5 rounded-lg text-sm font-mono outline-none transition-all duration-200"
                            style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
                            @focus="e => (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                            @blur="e => (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                        />
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer hover:opacity-70" style="color:#AAAAAA;" @click="showKey = !showKey">
                            <EyeOff v-if="showKey" class="w-4 h-4" />
                            <Eye v-else class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── Model Selection card ─────────────────────────────────── -->
            <div class="bg-white rounded-2xl p-6" style="border:1px solid #DDDDDD;">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-sm font-black" style="color:#1A1A1A;">Model Selection</h2>
                        <p class="text-xs mt-0.5" style="color:#555555;">Models are fetched live from the Anthropic API.</p>
                    </div>
                    <button
                        type="button"
                        :disabled="fetching"
                        @click="fetchModels"
                        class="flex items-center gap-1.5 text-xs font-semibold px-3 h-8 rounded-lg border transition-all duration-150 cursor-pointer disabled:opacity-50"
                        style="border-color:#DDDDDD; color:#555555;"
                    >
                        <Loader2 v-if="fetching" class="w-3.5 h-3.5 animate-spin" />
                        <RefreshCcw v-else class="w-3.5 h-3.5" />
                        {{ fetching ? 'Loading…' : 'Refresh' }}
                    </button>
                </div>

                <!-- Error -->
                <div v-if="fetchError" class="flex items-start gap-2.5 rounded-xl px-4 py-3 mb-5 text-sm" style="background:#FEF2F2; border:1px solid #FECACA; color:#DC2626;">
                    <TriangleAlert class="w-4 h-4 mt-0.5 shrink-0" />
                    <span>{{ fetchError }}</span>
                </div>

                <!-- Loading skeleton -->
                <div v-else-if="fetching" class="space-y-6">
                    <div v-for="i in 2" :key="i" class="space-y-2">
                        <div class="h-4 w-32 rounded animate-pulse" style="background:#F5F5F5;" />
                        <div class="grid grid-cols-2 gap-2">
                            <div v-for="j in 4" :key="j" class="h-16 rounded-xl animate-pulse" style="background:#F5F5F5;" />
                        </div>
                    </div>
                </div>

                <!-- No key set yet -->
                <div v-else-if="!models.length && !fetchError" class="text-center py-8" style="color:#AAAAAA;">
                    <Cpu class="w-8 h-8 mx-auto mb-2 opacity-30" />
                    <p class="text-sm">Enter your API key above and click <strong>Refresh</strong> to load available models.</p>
                </div>

                <!-- Phase selectors -->
                <div v-else class="space-y-7">
                    <div v-for="phase in phases" :key="phase.key">
                        <p class="text-sm font-bold mb-0.5" style="color:#1A1A1A;">{{ phase.label }}</p>
                        <p class="text-xs mb-3" style="color:#555555;">{{ phase.hint }}</p>

                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="m in models"
                                :key="m.id"
                                type="button"
                                @click="form[phase.key] = m.id"
                                class="flex flex-col items-start px-3 py-2.5 rounded-xl border-2 text-left transition-all duration-150 cursor-pointer"
                                :style="form[phase.key] === m.id
                                    ? 'border-color:#F5A000; background:#FFFBF0;'
                                    : 'border-color:#DDDDDD; background:#FFFFFF;'"
                            >
                                <span
                                    class="text-xs font-bold leading-tight"
                                    :style="form[phase.key] === m.id ? 'color:#F5A000' : 'color:#1A1A1A'"
                                >{{ m.display_name }}</span>
                                <span class="text-xs font-mono mt-1 leading-none" style="color:#AAAAAA;">{{ m.id }}</span>
                                <span v-if="m.max_tokens" class="text-xs mt-1.5" style="color:#555555;">
                                    {{ (m.max_tokens / 1000).toFixed(0) }}k max tokens
                                </span>
                            </button>
                        </div>

                        <!-- Fallback: show current selection if not in fetched list -->
                        <p v-if="form[phase.key] && !models.find(m => m.id === form[phase.key])" class="mt-2 text-xs" style="color:#AAAAAA;">
                            Currently saved: <code class="font-mono">{{ form[phase.key] }}</code> (not in fetched list)
                        </p>
                    </div>
                </div>
            </div>

            <!-- ─── Pricing card ─────────────────────────────────────────── -->
            <div class="bg-white rounded-2xl p-6" style="border:1px solid #DDDDDD;">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#FEF9EC;">
                        <Cpu class="w-4 h-4" style="color:#F5A000;" />
                    </div>
                    <div>
                        <h2 class="text-sm font-black" style="color:#1A1A1A;">Model Pricing</h2>
                        <p class="text-xs mt-0.5" style="color:#555555;">Set the price per 1M tokens for each phase. Used to estimate API costs in Usage & Billing.</p>
                    </div>
                </div>

                <div class="space-y-5">
                    <div v-for="phase in [
                        { label: 'Interview Phase', inputKey: 'interview_price_input', outputKey: 'interview_price_output' },
                        { label: 'Story Generation', inputKey: 'generation_price_input', outputKey: 'generation_price_output' },
                    ]" :key="phase.label">
                        <p class="text-sm font-bold mb-3" style="color:#1A1A1A;">{{ phase.label }}</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="text-xs font-semibold" style="color:#555555;">Input price (per 1M tokens)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold" style="color:#AAAAAA;">$</span>
                                    <input
                                        v-model="form[phase.inputKey]"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-7 pr-3 py-2.5 rounded-lg text-sm outline-none transition-all"
                                        style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
                                        @focus="e => (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                                        @blur="e => (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                                    />
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-semibold" style="color:#555555;">Output price (per 1M tokens)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold" style="color:#AAAAAA;">$</span>
                                    <input
                                        v-model="form[phase.outputKey]"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-7 pr-3 py-2.5 rounded-lg text-sm outline-none transition-all"
                                        style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
                                        @focus="e => (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                                        @blur="e => (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
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
