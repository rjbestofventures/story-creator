<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, ArrowRight } from '@lucide/vue';
import Footer from '@/Components/Footer.vue';

defineProps({ status: String });

const form = useForm({ email: '' });

const submit = () => form.post(route('password.email'));
</script>

<template>
    <Head title="Forgot password" />

    <div class="min-h-screen flex flex-col" style="background-color: #FAFAF8;">
      <div class="flex-1 flex flex-col items-center justify-center px-4 py-10">

        <!-- Logo -->
        <Link href="/" class="flex items-center text-xl font-bold tracking-tight mb-12">
            <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
            <span style="color: #1A1A1A;">.Bot</span>
        </Link>

        <div class="w-full max-w-sm">
            <div class="rounded-2xl p-8" style="background:#FFFFFF; border:1px solid #DDDDDD;">

                <!-- Icon -->
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-5" style="background:#FEF9EC;">
                    <Mail class="w-7 h-7" style="color:#F5A000;" />
                </div>

                <h1 class="text-xl font-black mb-1" style="color: #1A1A1A;">Forgot your password?</h1>
                <p class="text-sm leading-relaxed mb-6" style="color: #555555;">
                    No problem. Enter your email and we'll send you a reset link.
                </p>

                <!-- Success message -->
                <div
                    v-if="status"
                    class="mb-5 rounded-lg px-4 py-3 text-sm font-medium"
                    style="background:#F0FDF4; color:#16A34A; border:1px solid #BBF7D0;"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4" novalidate>
                    <div>
                        <label for="email" class="block text-sm font-semibold mb-1.5" style="color:#1A1A1A;">Email address</label>
                        <div class="relative">
                            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color:#AAAAAA;" />
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="username"
                                autofocus
                                required
                                placeholder="you@company.com"
                                class="w-full pl-9 pr-4 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                                style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
                                :style="form.errors.email ? 'border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,0.1)' : ''"
                                @focus="(e) => !form.errors.email && (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                                @blur="(e) => !form.errors.email && (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-xs" style="color:#EF4444;">{{ form.errors.email }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg font-bold text-sm transition-opacity duration-200 cursor-pointer"
                        :class="{ 'opacity-60 cursor-not-allowed': form.processing }"
                        style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                    >
                        <span v-if="form.processing">Sending…</span>
                        <template v-else>
                            Send reset link <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </template>
                    </button>
                </form>
            </div>

            <!-- Back to login -->
            <p class="text-center mt-6 text-sm" style="color:#555555;">
                Remember your password?
                <Link :href="route('login')" class="font-semibold underline transition hover:opacity-70" style="color:#1A1A1A;">Log in</Link>
            </p>
        </div>
      </div>
      <Footer />
    </div>
</template>
