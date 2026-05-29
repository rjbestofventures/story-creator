<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail } from '@lucide/vue';

const props = defineProps({ status: String });

const form = useForm({});

const submit = () => form.post(route('verification.send'));

const sent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Verify your email" />

    <div class="min-h-screen flex flex-col items-center justify-center px-4" style="background-color: #FAFAF8;">

        <!-- Logo -->
        <Link href="/" class="flex items-center text-xl font-bold tracking-tight mb-12">
            <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
            <span style="color: #1A1A1A;">.Bot</span>
        </Link>

        <div class="w-full max-w-sm">
            <div class="rounded-2xl p-8 text-center" style="background:#FFFFFF; border:1px solid #DDDDDD;">

                <!-- Icon -->
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-5" style="background:#FEF9EC;">
                    <Mail class="w-7 h-7" style="color:#F5A000;" />
                </div>

                <h1 class="text-xl font-black mb-2" style="color: #1A1A1A;">Check your inbox</h1>
                <p class="text-sm leading-relaxed mb-6" style="color: #555555;">
                    We sent a verification link to your email address. Click the link to activate your account and choose your plan.
                </p>

                <!-- Success message -->
                <div
                    v-if="sent"
                    class="mb-5 rounded-lg px-4 py-3 text-sm font-medium"
                    style="background:#F0FDF4; color:#16A34A; border:1px solid #BBF7D0;"
                >
                    A new verification link has been sent.
                </div>

                <!-- Resend button -->
                <form @submit.prevent="submit">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-2.5 rounded-lg font-bold text-sm transition-opacity duration-200 cursor-pointer"
                        :class="{ 'opacity-60 cursor-not-allowed': form.processing }"
                        style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                    >
                        {{ form.processing ? 'Sending…' : 'Resend verification email' }}
                    </button>
                </form>

                <!-- Logout -->
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="mt-4 text-sm underline transition hover:opacity-70 cursor-pointer"
                    style="color: #AAAAAA;"
                >
                    Log out
                </Link>
            </div>
        </div>
    </div>
</template>
