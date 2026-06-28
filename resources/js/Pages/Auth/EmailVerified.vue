<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle } from '@lucide/vue';
</script>

<template>
    <Head title="Email verified" />

    <div class="min-h-screen flex flex-col items-center justify-center px-4" style="background-color: #FAFAF8;">

        <!-- Logo -->
        <Link href="/" class="flex items-center text-xl font-bold tracking-tight mb-12">
            <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
            <span style="color: #1A1A1A;">.Bot</span>
        </Link>

        <div class="w-full max-w-sm">
            <div class="rounded-2xl p-8 text-center" style="background:#FFFFFF; border:1px solid #DDDDDD;">

                <!-- Icon -->
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-5" style="background:#F0FDF4;">
                    <CheckCircle class="w-7 h-7" style="color:#16A34A;" />
                </div>

                <h1 class="text-xl font-black mb-2" style="color: #1A1A1A;">Email verified!</h1>
                <p class="text-sm leading-relaxed mb-6" style="color: #555555;">
                    Your email address has been confirmed. You're all set to grab some credits and start creating your story library.
                </p>

                <Link
                    :href="route('shop.index')"
                    class="block w-full py-2.5 rounded-lg font-bold text-sm transition"
                    style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                >
                    Continue to Buy Credits →
                </Link>

            </div>
        </div>
    </div>
</template>
