<script setup>
import { ref, computed, watch } from 'vue';
import { Search, ChevronLeft, ChevronRight, Check } from '@lucide/vue';

const props = defineProps({
    modelValue: String,
    voices: { type: Array, default: () => [] },
    disabled: Boolean,
});
const emit = defineEmits(['update:modelValue']);

const PAGE_SIZE = 10;
const search = ref('');
const page = ref(1);

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

const choose = (voice) => {
    if (props.disabled) return;
    emit('update:modelValue', voice.id);
};
</script>

<template>
    <div>
        <div class="relative mb-2">
            <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 pointer-events-none" style="color:#AAAAAA;" />
            <input
                v-model="search"
                type="text"
                placeholder="Search voices…"
                :disabled="disabled"
                class="w-full pl-8 pr-2 py-1.5 rounded-lg text-sm outline-none disabled:opacity-50"
                style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
            />
        </div>

        <div class="rounded-lg overflow-hidden" style="border:1px solid #DDDDDD;">
            <table class="w-full text-sm border-collapse">
                <tbody>
                    <tr
                        v-for="v in paged"
                        :key="v.id"
                        @click="choose(v)"
                        class="transition-colors"
                        :class="[!disabled && v.id !== modelValue && 'cursor-pointer hover:bg-[#FAFAF8]', disabled && 'opacity-50']"
                        :style="v.id === modelValue ? 'background:#FFFBF0;' : ''"
                    >
                        <td class="px-3 py-2" :style="v.id === modelValue ? 'color:#F5A000; font-weight:700;' : 'color:#1A1A1A;'">{{ v.name }}</td>
                        <td class="px-3 py-2 w-8 text-right">
                            <Check v-if="v.id === modelValue" class="w-3.5 h-3.5 inline-block shrink-0" style="color:#F5A000;" />
                        </td>
                    </tr>
                    <tr v-if="paged.length === 0">
                        <td colspan="2" class="px-3 py-4 text-center text-sm" style="color:#AAAAAA;">
                            {{ voices.length === 0 ? 'Loading voices…' : `No voices match "${search}".` }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="totalPages > 1" class="flex items-center justify-between mt-2">
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
</template>
