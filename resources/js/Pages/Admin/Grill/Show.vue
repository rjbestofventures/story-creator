<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ClipboardList, MessageSquare } from '@lucide/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    interview: Object,
});

const statusBadge = (status) => {
    const map = {
        interviewing:       'bg-blue-50 text-blue-600',
        interview_complete: 'bg-amber-50 text-amber-600',
        generating:         'bg-purple-50 text-purple-600',
    };
    return map[status] ?? 'bg-gray-100 text-gray-600';
};
</script>

<template>
    <AdminLayout>
        <Head :title="`${interview.business_name || 'Interview'} — Review`" />

        <!-- Back -->
        <button
            class="flex items-center gap-1.5 text-xs mb-6 transition hover:opacity-70"
            style="color: #555555;"
            @click="router.get(route('grill.index'))"
        >
            <ChevronLeft class="w-3.5 h-3.5" />
            Back to interviews
        </button>

        <!-- Header -->
        <div class="bg-white rounded-2xl px-6 py-5 mb-5" style="border: 1px solid #DDDDDD;">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg font-black text-[#1A1A1A] leading-tight">{{ interview.business_name || 'Untitled Interview' }}</h1>
                    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                        <span class="text-xs font-medium" style="color: #555555;">{{ interview.user.name }}</span>
                        <span style="color: #DDDDDD;">·</span>
                        <span class="text-xs" style="color: #888888;">{{ interview.user.email }}</span>
                        <span v-if="interview.industry" style="color: #DDDDDD;">·</span>
                        <span v-if="interview.industry" class="text-xs" style="color: #888888;">{{ interview.industry }}</span>
                        <span style="color: #DDDDDD;">·</span>
                        <span class="text-xs" style="color: #888888;">{{ interview.created_at }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold capitalize"
                        :class="statusBadge(interview.status)"
                    >
                        {{ interview.status.replace('_', ' ') }}
                    </span>
                    <Link
                        :href="route('admin.stories.show', interview.story_id)"
                        class="text-xs px-2.5 py-1 rounded-full font-bold hover:opacity-80 transition"
                        style="background-color: #F5F5F5; color: #555555;"
                    >
                        Story #{{ interview.story_id }}
                    </Link>
                </div>
            </div>
        </div>

        <!-- Q&A pairs -->
        <div class="space-y-3">
            <div
                v-for="pair in interview.pairs"
                :key="pair.number"
                class="bg-white rounded-xl overflow-hidden"
                style="border: 1px solid #DDDDDD;"
            >
                <!-- Question -->
                <div class="flex gap-3 px-5 py-4" style="background-color: #FEFBF3; border-bottom: 1px solid #F0F0F0;">
                    <span class="text-xs font-black w-6 shrink-0 text-center pt-0.5" style="color: #F5A000;">{{ pair.number }}</span>
                    <p class="text-sm font-semibold text-[#1A1A1A] leading-relaxed">{{ pair.question }}</p>
                </div>
                <!-- Answer -->
                <div class="flex gap-3 px-5 py-4">
                    <MessageSquare class="w-4 h-4 shrink-0 mt-0.5 text-muted-foreground" />
                    <p
                        v-if="pair.answer"
                        class="text-sm leading-relaxed whitespace-pre-wrap"
                        style="color: #333333;"
                    >{{ pair.answer }}</p>
                    <p v-else class="text-sm italic text-muted-foreground">No answer recorded.</p>
                </div>
            </div>

            <!-- Empty -->
            <div v-if="interview.pairs.length === 0" class="bg-white rounded-2xl py-16 text-center" style="border: 1px solid #DDDDDD;">
                <ClipboardList class="w-8 h-8 mx-auto mb-3 text-muted-foreground opacity-40" />
                <p class="text-sm font-semibold text-[#1A1A1A]">No questions answered yet</p>
                <p class="text-xs mt-1 text-muted-foreground">This interview hasn't captured any answers.</p>
            </div>
        </div>
    </AdminLayout>
</template>
