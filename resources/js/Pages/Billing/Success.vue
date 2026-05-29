<script setup>
import { onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle } from 'lucide-vue-next';

onMounted(() => {
    setTimeout(() => router.visit(route('stories.index')), 3000);
});
</script>

<template>
    <Head title="You're all set!" />

    <div class="min-h-screen flex flex-col items-center justify-center px-4" style="background-color: #FAFAF8;">

        <!-- Logo -->
        <Link href="/" class="flex items-center text-xl font-bold tracking-tight mb-12">
            <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
            <span style="color: #1A1A1A;">.Bot</span>
        </Link>

        <div class="text-center max-w-sm">
            <!-- Icon -->
            <div class="flex justify-center mb-6">
                <CheckCircle class="w-16 h-16" style="color: #F5A000;" />
            </div>

            <h1 class="text-2xl font-black mb-2" style="color: #1A1A1A;">You're all set!</h1>
            <p class="text-base mb-8" style="color: #555555;">
                Your subscription is being activated. You'll be redirected in a moment…
            </p>

            <!-- Animated dots -->
            <div class="flex items-center justify-center gap-1.5 mb-8">
                <span class="w-2 h-2 rounded-full animate-bounce" style="background:#F5A000; animation-delay:0ms" />
                <span class="w-2 h-2 rounded-full animate-bounce" style="background:#F5A000; animation-delay:150ms" />
                <span class="w-2 h-2 rounded-full animate-bounce" style="background:#F5A000; animation-delay:300ms" />
            </div>

            <Link
                :href="route('stories.index')"
                class="text-sm font-semibold underline transition hover:opacity-70"
                style="color: #555555;"
            >
                Go to my stories →
            </Link>
        </div>
    </div>
</template>
