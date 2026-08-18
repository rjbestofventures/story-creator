<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardList, MessageSquare } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    interview: Object,
});

const normalizeUrl = (url) => (url.startsWith('http') ? url : `https://${url}`);

const hasProfile = props.interview.business_name || props.interview.industry
    || props.interview.business_url || props.interview.linkedin_url || props.interview.social_url
    || props.interview.biography || props.interview.services;
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`${interview.business_name || 'Interview'} — My Answers`" />

        <div class="max-w-3xl mx-auto px-4 md:px-8 py-6 md:py-10">

            <!-- Back -->
            <Link
                :href="route('stories.show', interview.story_id)"
                class="flex items-center gap-1.5 text-xs mb-6 transition hover:opacity-70"
                style="color: #555555;"
            >
                <ArrowLeft class="w-3.5 h-3.5" />
                Back to your story
            </Link>

            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-black text-[#1A1A1A] leading-tight">My Answers</h1>
                <p class="text-sm mt-1" style="color: #555555;">The answers you gave during your interview for {{ interview.business_name || 'your business' }}.</p>
            </div>

            <!-- Business Profile -->
            <div v-if="hasProfile" class="bg-white rounded-2xl px-6 py-5 mb-5" style="border: 1px solid #DDDDDD;">
                <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: #888888;">Business Profile</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 mb-4">
                    <div v-if="interview.business_name">
                        <p class="text-[10px] font-bold uppercase tracking-wide" style="color: #999999;">Business Name</p>
                        <p class="text-sm text-[#1A1A1A]">{{ interview.business_name }}</p>
                    </div>
                    <div v-if="interview.industry">
                        <p class="text-[10px] font-bold uppercase tracking-wide" style="color: #999999;">Industry</p>
                        <p class="text-sm text-[#1A1A1A]">{{ interview.industry }}</p>
                    </div>
                    <div v-if="interview.business_url">
                        <p class="text-[10px] font-bold uppercase tracking-wide" style="color: #999999;">Website</p>
                        <a :href="normalizeUrl(interview.business_url)" target="_blank" rel="noopener" class="text-sm hover:underline break-all" style="color: #F5A000;">{{ interview.business_url }}</a>
                    </div>
                    <div v-if="interview.linkedin_url">
                        <p class="text-[10px] font-bold uppercase tracking-wide" style="color: #999999;">LinkedIn</p>
                        <a :href="normalizeUrl(interview.linkedin_url)" target="_blank" rel="noopener" class="text-sm hover:underline break-all" style="color: #F5A000;">{{ interview.linkedin_url }}</a>
                    </div>
                    <div v-if="interview.social_url">
                        <p class="text-[10px] font-bold uppercase tracking-wide" style="color: #999999;">Facebook</p>
                        <a :href="normalizeUrl(interview.social_url)" target="_blank" rel="noopener" class="text-sm hover:underline break-all" style="color: #F5A000;">{{ interview.social_url }}</a>
                    </div>
                    <div v-if="interview.instagram_url">
                        <p class="text-[10px] font-bold uppercase tracking-wide" style="color: #999999;">Instagram</p>
                        <a :href="normalizeUrl(interview.instagram_url)" target="_blank" rel="noopener" class="text-sm hover:underline break-all" style="color: #F5A000;">{{ interview.instagram_url }}</a>
                    </div>
                </div>

                <div v-if="interview.biography" class="mb-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide mb-1" style="color: #999999;">About the business</p>
                    <p class="text-sm leading-relaxed" style="color: #333333;">{{ interview.biography }}</p>
                </div>
                <div v-if="interview.services">
                    <p class="text-[10px] font-bold uppercase tracking-wide mb-1" style="color: #999999;">Services</p>
                    <p class="text-sm leading-relaxed" style="color: #333333;">{{ interview.services }}</p>
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
                        <MessageSquare class="w-4 h-4 shrink-0 mt-0.5" style="color: #999999;" />
                        <p
                            v-if="pair.answer"
                            class="text-sm leading-relaxed whitespace-pre-wrap"
                            style="color: #333333;"
                        >{{ pair.answer }}</p>
                        <p v-else class="text-sm italic" style="color: #999999;">No answer recorded.</p>
                    </div>
                </div>

                <!-- Empty -->
                <div v-if="interview.pairs.length === 0" class="bg-white rounded-2xl py-16 text-center" style="border: 1px solid #DDDDDD;">
                    <ClipboardList class="w-8 h-8 mx-auto mb-3 opacity-40" style="color: #999999;" />
                    <p class="text-sm font-semibold text-[#1A1A1A]">No questions answered yet</p>
                    <p class="text-xs mt-1" style="color: #999999;">This interview hasn't captured any answers.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
