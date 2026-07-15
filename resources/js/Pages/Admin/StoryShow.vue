<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronDown, ChevronUp, FileText } from '@lucide/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    story: Object,
});

const expanded = ref(null);

const toggle = (id) => {
    expanded.value = expanded.value === id ? null : id;
};

const statusBadge = (status) => {
    const map = {
        interviewing:       'bg-blue-50 text-blue-600',
        interview_complete: 'bg-amber-50 text-amber-600',
        generating:         'bg-purple-50 text-purple-600',
        draft:              'bg-gray-100 text-gray-600',
    };
    return map[status] ?? 'bg-gray-100 text-gray-600';
};

const formatLabel = (format) => {
    const map = { social: 'Social', blog: 'Blog', linkedin: 'LinkedIn' };
    return map[format] ?? format;
};
</script>

<template>
    <AdminLayout>
        <Head :title="`${story.title || 'Story'} — Admin`" />

        <!-- Back -->
        <button
            class="flex items-center gap-1.5 text-xs mb-6 transition hover:opacity-70"
            style="color: #555555;"
            @click="router.get(story.back === 'all' ? route('admin.stories.index') : route('admin.stories.index', { user_id: story.user.id }))"
        >
            <ChevronLeft class="w-3.5 h-3.5" />
            {{ story.back === 'all' ? 'Back to all stories' : `Back to ${story.user.name}'s stories` }}
        </button>

        <!-- Story header -->
        <div class="bg-white rounded-2xl px-6 py-5 mb-5" style="border: 1px solid #DDDDDD;">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg font-black text-[#1A1A1A] leading-tight">{{ story.title || 'Untitled Story' }}</h1>
                    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                        <button
                            class="text-xs font-medium hover:underline"
                            style="color: #555555;"
                            @click="router.get(route('admin.stories.index', { user_id: story.user.id }))"
                        >
                            {{ story.user.name }}
                        </button>
                        <span style="color: #DDDDDD;">·</span>
                        <span class="text-xs" style="color: #888888;">{{ story.user.email }}</span>
                        <span style="color: #DDDDDD;">·</span>
                        <span class="text-xs" style="color: #888888;">{{ story.created_at }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold capitalize"
                        :class="statusBadge(story.status)"
                    >
                        {{ story.status }}
                    </span>
                    <span class="text-xs px-2.5 py-1 rounded-full font-bold" style="background-color: #F5F5F5; color: #555555;">
                        {{ story.episodes.length }} chapter{{ story.episodes.length !== 1 ? 's' : '' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Episodes -->
        <div class="space-y-2">
            <div
                v-for="ep in story.episodes"
                :key="ep.id"
                class="bg-white rounded-xl overflow-hidden"
                style="border: 1px solid #DDDDDD;"
            >
                <!-- Episode header row -->
                <button
                    class="w-full flex items-center gap-4 px-5 py-4 text-left transition hover:bg-[#FAFAF8]"
                    @click="toggle(ep.id)"
                >
                    <span class="text-xs font-black w-6 shrink-0 text-center" style="color: #F5A000;">{{ ep.episode_number }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-[#1A1A1A] truncate">{{ ep.title }}</p>
                        <p class="text-[10px] text-muted-foreground mt-0.5">{{ formatLabel(ep.format) }}</p>
                    </div>
                    <ChevronDown v-if="expanded !== ep.id" class="w-4 h-4 shrink-0 text-muted-foreground" />
                    <ChevronUp v-else class="w-4 h-4 shrink-0 text-muted-foreground" />
                </button>

                <!-- Episode content -->
                <div v-if="expanded === ep.id" style="border-top: 1px solid #F0F0F0;">
                    <div class="px-5 py-4">
                        <pre class="whitespace-pre-wrap text-sm leading-relaxed font-sans" style="color: #333333;">{{ ep.content }}</pre>
                    </div>
                </div>
            </div>

            <!-- Empty -->
            <div v-if="story.episodes.length === 0" class="bg-white rounded-2xl py-16 text-center" style="border: 1px solid #DDDDDD;">
                <FileText class="w-8 h-8 mx-auto mb-3 text-muted-foreground opacity-40" />
                <p class="text-sm font-semibold text-[#1A1A1A]">No chapters yet</p>
                <p class="text-xs mt-1 text-muted-foreground">This story hasn't generated any chapters.</p>
            </div>
        </div>
    </AdminLayout>
</template>
