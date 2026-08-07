<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Search, ChevronLeft, ChevronRight, ChevronDown, Check } from '@lucide/vue';

const props = defineProps({
    modelValue: String,
    voices: { type: Array, default: () => [] },
    disabled: Boolean,
    placeholder: { type: String, default: 'Select a voice…' },
});
const emit = defineEmits(['update:modelValue']);

const PAGE_SIZE = 10;
const open = ref(false);
const search = ref('');
const page = ref(1);
const root = ref(null);

const selected = computed(() => props.voices.find(v => v.id === props.modelValue) ?? null);

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.voices;
    return props.voices.filter(v => v.name.toLowerCase().includes(q));
});

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / PAGE_SIZE)));

const paged = computed(() => {
    const start = (page.value - 1) * PAGE_SIZE;
    return filtered.value.slice(start, start + PAGE_SIZE);
});

watch(search, () => { page.value = 1; });
watch(open, (isOpen) => { if (isOpen) { search.value = ''; page.value = 1; } });

const choose = (voice) => {
    emit('update:modelValue', voice.id);
    open.value = false;
};

const onClickOutside = (e) => {
    if (open.value && root.value && !root.value.contains(e.target)) open.value = false;
};

onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => document.removeEventListener('click', onClickOutside));
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            :disabled="disabled"
            @click="open = !open"
            class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-lg text-sm text-left outline-none transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
        >
            <span :style="selected ? '' : 'color:#AAAAAA;'">{{ selected?.name ?? placeholder }}</span>
            <ChevronDown class="w-4 h-4 shrink-0" style="color:#AAAAAA;" />
        </button>

        <div
            v-if="open"
            class="absolute z-20 mt-1.5 w-full rounded-xl bg-white shadow-lg overflow-hidden"
            style="border:1px solid #DDDDDD;"
        >
            <div class="p-2" style="border-bottom:1px solid #F0F0F0;">
                <div class="relative">
                    <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 pointer-events-none" style="color:#AAAAAA;" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search voices…"
                        autofocus
                        class="w-full pl-8 pr-2 py-1.5 rounded-lg text-sm outline-none"
                        style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FAFAF8;"
                    />
                </div>
            </div>

            <div class="max-h-64 overflow-y-auto">
                <button
                    v-for="v in paged"
                    :key="v.id"
                    type="button"
                    @click="choose(v)"
                    class="w-full flex items-center justify-between gap-2 px-3 py-2 text-left text-sm cursor-pointer transition-colors"
                    :class="v.id !== modelValue && 'hover:bg-[#FAFAF8]'"
                    :style="v.id === modelValue ? 'color:#F5A000; background:#FFFBF0; font-weight:700;' : 'color:#1A1A1A;'"
                >
                    {{ v.name }}
                    <Check v-if="v.id === modelValue" class="w-3.5 h-3.5 shrink-0" style="color:#F5A000;" />
                </button>
                <p v-if="filtered.length === 0" class="px-3 py-4 text-center text-sm" style="color:#AAAAAA;">No voices match "{{ search }}".</p>
            </div>

            <div v-if="totalPages > 1" class="flex items-center justify-between px-3 py-2" style="border-top:1px solid #F0F0F0;">
                <button
                    type="button"
                    :disabled="page === 1"
                    @click="page--"
                    class="flex items-center justify-center w-7 h-7 rounded-lg cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                    style="border:1px solid #DDDDDD;"
                >
                    <ChevronLeft class="w-3.5 h-3.5" style="color:#555555;" />
                </button>
                <span class="text-xs" style="color:#888888;">Page {{ page }} of {{ totalPages }}</span>
                <button
                    type="button"
                    :disabled="page === totalPages"
                    @click="page++"
                    class="flex items-center justify-center w-7 h-7 rounded-lg cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                    style="border:1px solid #DDDDDD;"
                >
                    <ChevronRight class="w-3.5 h-3.5" style="color:#555555;" />
                </button>
            </div>
        </div>
    </div>
</template>
