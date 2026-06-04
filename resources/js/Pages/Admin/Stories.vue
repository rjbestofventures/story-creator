<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Search, X, ChevronLeft, ChevronRight, BookOpen } from '@lucide/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    stories: Object,
    filterUser: Object,
});

const search = ref('');

const rows = computed(() => props.stories.data);

const filtered = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return rows.value;
    return rows.value.filter(s =>
        String(s.id).includes(q) ||
        s.title?.toLowerCase().includes(q) ||
        s.user.name.toLowerCase().includes(q) ||
        s.user.email.toLowerCase().includes(q)
    );
});

const clearFilter = () => router.get(route('admin.stories.index'));

const goToPage = (url) => { if (url) router.get(url); };
</script>

<template>
    <AdminLayout>
        <Head title="Stories — Admin" />

        <!-- Header -->
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-lg font-black text-[#1A1A1A]">All Stories</h1>
                <p class="text-xs mt-0.5 text-muted-foreground">
                    {{ filterUser ? `Stories by ${filterUser.name}` : 'All stories across all users.' }}
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
            <Input v-model="search" placeholder="Search by ID, title, name or email…" class="pl-9" />
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl overflow-hidden" style="border: 1px solid #DDDDDD;">
            <table class="w-full text-sm">
                <thead>
                    <tr style="border-bottom: 1px solid #EBEBEB; background-color: #FAFAF8;">
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider">Story ID</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider">User Name</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider hidden md:table-cell">Email</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider hidden sm:table-cell">Date Generated</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider">AI Refine Used</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="story in filtered"
                        :key="story.id"
                        class="hover:bg-[#FAFAF8] transition-colors cursor-pointer"
                        style="border-bottom: 1px solid #F5F5F5;"
                        @click="router.get(route('admin.stories.show', story.id), filterUser ? { back: 'user' } : { back: 'all' })"
                    >
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono font-semibold text-muted-foreground">#{{ story.id }}</span>
                                <span
                                    v-if="story.status === 'interviewing' || story.status === 'interview_complete'"
                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-600"
                                >
                                    In Progress
                                </span>
                                <span
                                    v-else-if="story.status === 'generating'"
                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-600"
                                >
                                    Generating
                                </span>
                            </div>
                            <p class="font-semibold text-[#1A1A1A] text-xs mt-0.5 leading-tight max-w-[180px] truncate">{{ story.title || '—' }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="font-semibold text-sm text-[#1A1A1A]">{{ story.user.name }}</p>
                        </td>
                        <td class="px-5 py-3.5 hidden md:table-cell">
                            <p class="text-xs text-muted-foreground">{{ story.user.email }}</p>
                        </td>
                        <td class="px-5 py-3.5 hidden sm:table-cell">
                            <p class="text-xs text-[#555555]">{{ story.created_at }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            <span
                                class="inline-flex items-center justify-center w-8 h-6 rounded-md text-xs font-bold"
                                :class="story.refines_used > 0 ? 'bg-amber-50 text-amber-600' : 'bg-gray-100 text-gray-400'"
                            >
                                {{ story.refines_used }}
                            </span>
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

        <!-- Footer: count + pagination -->
        <div class="flex items-center justify-between mt-3">
            <p class="text-xs text-muted-foreground">
                {{ stories.total }} {{ stories.total === 1 ? 'story' : 'stories' }} total
            </p>
            <div v-if="stories.last_page > 1" class="flex items-center gap-1">
                <button
                    :disabled="!stories.prev_page_url"
                    class="h-7 w-7 flex items-center justify-center rounded-lg border text-xs transition hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                    style="border-color: #DDDDDD;"
                    @click="goToPage(stories.prev_page_url)"
                >
                    <ChevronLeft class="w-3.5 h-3.5" />
                </button>
                <span class="text-xs text-muted-foreground px-2">
                    {{ stories.current_page }} / {{ stories.last_page }}
                </span>
                <button
                    :disabled="!stories.next_page_url"
                    class="h-7 w-7 flex items-center justify-center rounded-lg border text-xs transition hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                    style="border-color: #DDDDDD;"
                    @click="goToPage(stories.next_page_url)"
                >
                    <ChevronRight class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
