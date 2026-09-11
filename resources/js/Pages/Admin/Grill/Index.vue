<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Search, ChevronLeft, ChevronRight, ClipboardList } from '@lucide/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    interviews: Object,
    filters: Object,
});

const search = ref('');

const rows = computed(() => props.interviews.data);

const filtered = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return rows.value;
    return rows.value.filter(i =>
        String(i.id).includes(q) ||
        i.business_name?.toLowerCase().includes(q) ||
        i.user.name.toLowerCase().includes(q) ||
        i.user.email.toLowerCase().includes(q)
    );
});

const statusLabel = (status) => {
    const map = {
        interviewing:       { text: 'In Progress', cls: 'bg-blue-50 text-blue-600' },
        interview_complete: { text: 'Complete',    cls: 'bg-amber-50 text-amber-600' },
        generating:         { text: 'Generating',  cls: 'bg-purple-50 text-purple-600' },
    };
    return map[status] ?? { text: status, cls: 'bg-gray-100 text-gray-600' };
};

const goToPage = (url) => { if (url) router.get(url); };
</script>

<template>
    <AdminLayout>
        <Head title="Interviews — Admin" />

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-lg font-black text-[#1A1A1A]">Interview Review</h1>
            <p class="text-xs mt-0.5 text-muted-foreground">
                Every StoryBot interview. Open one to review the questions and answers.
            </p>
        </div>

        <!-- Search -->
        <div class="relative mb-4">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
            <Input v-model="search" placeholder="Search by ID, business, name or email…" class="pl-9" />
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl overflow-hidden" style="border: 1px solid #DDDDDD;">
            <table class="w-full text-sm">
                <thead>
                    <tr style="border-bottom: 1px solid #EBEBEB; background-color: #FAFAF8;">
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider">Story</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider">Business</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider hidden md:table-cell">User</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider hidden sm:table-cell">Date</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider">Answered</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in filtered"
                        :key="row.id"
                        class="hover:bg-[#FAFAF8] transition-colors cursor-pointer"
                        style="border-bottom: 1px solid #F5F5F5;"
                        @click="router.get(route('grill.show', row.id))"
                    >
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono font-semibold text-muted-foreground">#{{ row.id }}</span>
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold"
                                    :class="statusLabel(row.status).cls"
                                >
                                    {{ statusLabel(row.status).text }}
                                </span>
                            </div>
                            <p class="font-semibold text-[#1A1A1A] text-xs mt-0.5 leading-tight max-w-[180px] truncate">{{ row.title || '—' }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="font-semibold text-sm text-[#1A1A1A]">{{ row.business_name || '—' }}</p>
                        </td>
                        <td class="px-5 py-3.5 hidden md:table-cell">
                            <p class="font-semibold text-xs text-[#1A1A1A]">{{ row.user.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ row.user.email }}</p>
                        </td>
                        <td class="px-5 py-3.5 hidden sm:table-cell">
                            <p class="text-xs text-[#555555]">{{ row.created_at }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            <span
                                class="inline-flex items-center justify-center px-2 h-6 rounded-md text-xs font-bold"
                                :class="row.answered > 0 ? 'bg-amber-50 text-amber-600' : 'bg-gray-100 text-gray-400'"
                            >
                                {{ row.answered }} / 15
                            </span>
                        </td>
                    </tr>

                    <tr v-if="filtered.length === 0">
                        <td colspan="5" class="px-5 py-16 text-center">
                            <ClipboardList class="w-8 h-8 mx-auto mb-3 text-muted-foreground opacity-40" />
                            <p class="text-sm font-semibold text-[#1A1A1A]">No interviews found</p>
                            <p class="text-xs mt-1 text-muted-foreground">
                                {{ search ? 'Try a different search term' : 'No interviews have been started yet' }}
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer: count + pagination -->
        <div class="flex items-center justify-between mt-3">
            <p class="text-xs text-muted-foreground">
                {{ interviews.total }} {{ interviews.total === 1 ? 'interview' : 'interviews' }} total
            </p>
            <div v-if="interviews.last_page > 1" class="flex items-center gap-1">
                <button
                    :disabled="!interviews.prev_page_url"
                    class="h-7 w-7 flex items-center justify-center rounded-lg border text-xs transition hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                    style="border-color: #DDDDDD;"
                    @click="goToPage(interviews.prev_page_url)"
                >
                    <ChevronLeft class="w-3.5 h-3.5" />
                </button>
                <span class="text-xs text-muted-foreground px-2">
                    {{ interviews.current_page }} / {{ interviews.last_page }}
                </span>
                <button
                    :disabled="!interviews.next_page_url"
                    class="h-7 w-7 flex items-center justify-center rounded-lg border text-xs transition hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                    style="border-color: #DDDDDD;"
                    @click="goToPage(interviews.next_page_url)"
                >
                    <ChevronRight class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
