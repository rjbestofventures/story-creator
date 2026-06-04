<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Search, X, ChevronLeft } from '@lucide/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    stories: Array,
    filterUser: Object,
});

const search = ref('');

const filtered = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return props.stories;
    return props.stories.filter(s =>
        s.title?.toLowerCase().includes(q) ||
        s.user.name.toLowerCase().includes(q) ||
        s.user.email.toLowerCase().includes(q)
    );
});

const statusBadge = (status) => {
    const map = {
        interviewing:       { label: 'Interviewing',  class: 'bg-blue-50 text-blue-600' },
        interview_complete: { label: 'Interview Done', class: 'bg-amber-50 text-amber-600' },
        generating:         { label: 'Generating',    class: 'bg-purple-50 text-purple-600' },
        draft:              { label: 'Draft',          class: 'bg-gray-100 text-gray-600' },
    };
    return map[status] ?? { label: status, class: 'bg-gray-100 text-gray-600' };
};

const clearFilter = () => router.get(route('admin.stories.index'));
</script>

<template>
    <AdminLayout>
        <Head title="Stories — Admin" />

        <!-- Header -->
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-lg font-black text-[#1A1A1A]">Stories</h1>
                <p class="text-xs mt-0.5 text-muted-foreground">
                    {{ filterUser ? `Showing stories by ${filterUser.name}` : 'All stories across all users.' }}
                </p>
            </div>

            <button
                v-if="filterUser"
                class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border transition hover:bg-gray-50"
                style="border-color: #DDDDDD; color: #555555;"
                @click="clearFilter"
            >
                <ChevronLeft class="w-3.5 h-3.5" />
                All stories
            </button>
        </div>

        <!-- Filter banner -->
        <div v-if="filterUser" class="flex items-center gap-3 mb-4 px-4 py-3 rounded-xl text-sm" style="background-color: #FEF3D0; border: 1px solid #F5A000;">
            <BookOpen class="w-4 h-4 shrink-0" style="color: #F5A000;" />
            <span style="color: #1A1A1A;">
                Filtered to <span class="font-bold">{{ filterUser.name }}</span>
                <span class="font-normal" style="color: #888888;"> · {{ filterUser.email }}</span>
            </span>
            <button class="ml-auto" @click="clearFilter">
                <X class="w-4 h-4" style="color: #888888;" />
            </button>
        </div>

        <!-- Search -->
        <div class="relative mb-4">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
            <Input v-model="search" placeholder="Search by title or user…" class="pl-9" />
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl overflow-hidden" style="border: 1px solid #DDDDDD;">
            <table class="w-full text-sm">
                <thead>
                    <tr style="border-bottom: 1px solid #EBEBEB; background-color: #FAFAF8;">
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider">Title</th>
                        <th v-if="!filterUser" class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider hidden md:table-cell">User</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider hidden sm:table-cell">Episodes</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider">Status</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider hidden md:table-cell">Created</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="story in filtered"
                        :key="story.id"
                        style="border-bottom: 1px solid #F5F5F5;"
                        class="hover:bg-[#FAFAF8] transition-colors"
                    >
                        <td class="px-5 py-3.5">
                            <button
                                class="text-left font-semibold text-[#1A1A1A] leading-tight hover:underline"
                                @click="router.get(route('admin.stories.show', story.id))"
                            >
                                {{ story.title || '—' }}
                            </button>
                        </td>
                        <td v-if="!filterUser" class="px-5 py-3.5 hidden md:table-cell">
                            <button
                                class="text-left hover:underline"
                                style="color: #1A1A1A;"
                                @click="router.get(route('admin.stories.index', { user_id: story.user.id }))"
                            >
                                <p class="font-medium text-xs">{{ story.user.name }}</p>
                                <p class="text-[10px] text-muted-foreground">{{ story.user.email }}</p>
                            </button>
                        </td>
                        <td class="px-5 py-3.5 hidden sm:table-cell">
                            <span class="text-xs font-semibold text-[#1A1A1A]">{{ story.episodes_count }}</span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold capitalize"
                                :class="statusBadge(story.status).class"
                            >
                                {{ statusBadge(story.status).label }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 hidden md:table-cell text-xs text-muted-foreground">
                            {{ story.created_at }}
                        </td>
                    </tr>

                    <tr v-if="filtered.length === 0">
                        <td colspan="5" class="px-5 py-16 text-center">
                            <BookOpen class="w-8 h-8 mx-auto mb-3 text-muted-foreground opacity-40" />
                            <p class="text-sm font-semibold text-[#1A1A1A]">No stories found</p>
                            <p class="text-xs mt-1 text-muted-foreground">
                                {{ search ? 'Try a different search term' : filterUser ? `${filterUser.name} hasn't created any stories yet` : 'No stories have been created yet' }}
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-xs text-muted-foreground mt-3">{{ filtered.length }} {{ filtered.length === 1 ? 'story' : 'stories' }}</p>
    </AdminLayout>
</template>
