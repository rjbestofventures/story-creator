<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Mail, Send } from '@lucide/vue';
import Footer from '@/Components/Footer.vue';

const props = defineProps({ status: String });

const form = useForm({});
const submit = () => form.post(route('verification.send'));

const sent = computed(() => props.status === 'verification-link-sent');
const email = usePage().props.auth.user?.email;
</script>

<template>
    <Head title="Verify your email" />

    <div class="min-h-screen flex flex-col" style="background-color: #FAFAF8;">
      <div class="flex-1 flex flex-col items-center justify-center px-4 py-10">

        <!-- Logo -->
        <Link href="/" class="flex items-center text-xl font-bold tracking-tight mb-12">
            <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
            <span style="color: #1A1A1A;">.Bot</span>
        </Link>

        <div class="w-full max-w-sm">
            <div class="rounded-2xl p-8 text-center" style="background:#FFFFFF; border:1px solid #DDDDDD;">

                <!-- Icon -->
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-5" style="background:#FEF9EC;">
                    <component :is="sent ? Mail : Send" class="w-7 h-7" style="color:#F5A000;" />
                </div>

                <!-- Before sending -->
                <template v-if="!sent">
                    <h1 class="text-xl font-black mb-2" style="color: #1A1A1A;">Verify your email</h1>
                    <p class="text-sm leading-relaxed mb-6" style="color: #555555;">
                        We sent a verification link to
                        <span class="font-semibold" style="color: #1A1A1A;">{{ email }}</span>.
                        Click the link in the email to activate your account. If you don't receive it after a minute, resend it below.
                    </p>

                    <form @submit.prevent="submit">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-2.5 rounded-lg font-bold text-sm transition cursor-pointer disabled:opacity-60"
                            style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                        >
                            {{ form.processing ? 'Sending…' : 'Resend verification' }}
                        </button>
                    </form>
                </template>

                <!-- After sending -->
                <template v-else>
                    <h1 class="text-xl font-black mb-2" style="color: #1A1A1A;">Check your inbox</h1>
                    <p class="text-sm leading-relaxed mb-5" style="color: #555555;">
                        We sent a verification link to
                        <span class="font-semibold" style="color: #1A1A1A;">{{ email }}</span>.
                        Click the link in the email to activate your account.
                    </p>

                    <div class="mb-5 rounded-lg px-4 py-3 text-sm font-medium" style="background:#F0FDF4; color:#16A34A; border:1px solid #BBF7D0;">
                        Verification email sent — check your spam folder if you don't see it.
                    </div>

                    <form @submit.prevent="submit">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-2.5 rounded-lg font-semibold text-sm border transition cursor-pointer disabled:opacity-60 hover:bg-gray-50"
                            style="background:#FFFFFF; color:#555555; border-color:#DDDDDD;"
                        >
                            {{ form.processing ? 'Sending…' : 'Resend verification email' }}
                        </button>
                    </form>
                </template>

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
      <Footer />
    </div>
</template>
