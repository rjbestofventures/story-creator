<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, Lock, Eye, EyeOff, ArrowRight, Zap } from '@lucide/vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen flex">

        <!-- Left: Brand panel -->
        <div
            class="hidden lg:flex lg:w-1/2 flex-col justify-between p-12 relative overflow-hidden"
            style="background: radial-gradient(ellipse at 30% 50%, #FEF9EC 0%, #F5F5F0 55%, #EBEBE6 100%);"
        >
            <!-- Logo -->
            <Link href="/" class="flex items-center text-xl font-bold tracking-tight">
                <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
                <span style="color: #1A1A1A;">.Bot</span>
            </Link>

            <!-- Center content -->
            <div class="max-w-sm">
                <div
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold mb-6"
                    style="background-color: #F5F5F5; color: #555555; border: 1px solid #DDDDDD;"
                >
                    <Zap class="w-3.5 h-3.5" />
                    AI-powered content engine
                </div>

                <h2 class="text-4xl font-black leading-tight mb-4" style="color: #1A1A1A;">
                    Welcome back to your
                    <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">story.</span>
                </h2>

                <p class="text-base leading-relaxed mb-10" style="color: #555555;">
                    Your episodes, your audience, your brand — all in one place.
                </p>

                <!-- Testimonial card -->
                <div class="rounded-2xl p-5" style="background-color: #FFFFFF; border: 1px solid #DDDDDD;">
                    <p class="text-sm leading-relaxed mb-4" style="color: #1A1A1A;">
                        "StoryCreator.Bot saved us 10+ hours a week. We went from struggling to post once a month to publishing weekly across every channel."
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold" style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;">
                            JM
                        </div>
                        <div>
                            <p class="text-xs font-bold" style="color: #1A1A1A;">Jamie Mitchell</p>
                            <p class="text-xs" style="color: #555555;">Founder, BrightLocal Co.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom stat -->
            <p class="text-xs" style="color: #555555;">
                Trusted by <span class="font-bold" style="color: #1A1A1A;">10,000+</span> businesses telling their story
            </p>

            <!-- Decorative gradient blob -->
            <div
                class="absolute -bottom-24 -right-24 w-72 h-72 rounded-full opacity-30 pointer-events-none"
                style="background: radial-gradient(circle, #FFC837, transparent);"
            />
        </div>

        <!-- Right: Login form -->
        <div class="flex-1 flex flex-col items-center justify-center px-6 py-12" style="background-color: #FFFFFF;">

            <!-- Mobile logo -->
            <Link href="/" class="flex items-center text-xl font-bold tracking-tight mb-10 lg:hidden">
                <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
                <span style="color: #1A1A1A;">.Bot</span>
            </Link>

            <div class="w-full max-w-sm">

                <!-- Header -->
                <h1 class="text-2xl font-black mb-1" style="color: #1A1A1A;">Log in to your account</h1>
                <p class="text-sm mb-8" style="color: #555555;">
                    Don't have an account?
                    <Link :href="route('register')" class="font-semibold underline transition hover:opacity-70" style="color: #1A1A1A;">Sign up free</Link>
                </p>

                <!-- Status message -->
                <div v-if="status" class="mb-6 rounded-lg px-4 py-3 text-sm font-medium" style="background-color: #F5F5F5; color: #1A1A1A;">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5" novalidate>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold mb-1.5" style="color: #1A1A1A;">Email</label>
                        <div class="relative">
                            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color: #AAAAAA;" />
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="username"
                                autofocus
                                required
                                placeholder="you@company.com"
                                class="w-full pl-9 pr-4 py-2.5 rounded-lg text-sm outline-none transition-all duration-200 focus:ring-2"
                                style="border: 1px solid #DDDDDD; color: #1A1A1A; background: #FFFFFF;"
                                :style="form.errors.email ? 'border-color: #EF4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.1)' : ''"
                                @focus="(e) => !form.errors.email && (e.target.style.borderColor = '#F5A000', e.target.style.boxShadow = '0 0 0 3px rgba(245,160,0,0.15)')"
                                @blur="(e) => !form.errors.email && (e.target.style.borderColor = '#DDDDDD', e.target.style.boxShadow = 'none')"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-xs" style="color: #EF4444;">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-sm font-semibold" style="color: #1A1A1A;">Password</label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-xs transition hover:opacity-70 cursor-pointer"
                                style="color: #555555;"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color: #AAAAAA;" />
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                placeholder="••••••••"
                                class="w-full pl-9 pr-10 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                                style="border: 1px solid #DDDDDD; color: #1A1A1A; background: #FFFFFF;"
                                :style="form.errors.password ? 'border-color: #EF4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.1)' : ''"
                                @focus="(e) => !form.errors.password && (e.target.style.borderColor = '#F5A000', e.target.style.boxShadow = '0 0 0 3px rgba(245,160,0,0.15)')"
                                @blur="(e) => !form.errors.password && (e.target.style.borderColor = '#DDDDDD', e.target.style.boxShadow = 'none')"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer transition-opacity hover:opacity-70"
                                style="color: #AAAAAA;"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs" style="color: #EF4444;">{{ form.errors.password }}</p>
                    </div>

                    <!-- Remember me -->
                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="w-4 h-4 rounded cursor-pointer"
                            style="accent-color: #F5A000;"
                        />
                        <span class="text-sm" style="color: #555555;">Remember me for 30 days</span>
                    </label>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 py-3 rounded-lg font-bold text-sm transition-opacity duration-200 cursor-pointer"
                        :class="{ 'opacity-60 cursor-not-allowed': form.processing }"
                        style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                    >
                        <span v-if="form.processing">Signing in…</span>
                        <template v-else>
                            Log in <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </template>
                    </button>

                </form>
            </div>
        </div>

    </div>
</template>
