<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, Lock, User, Eye, EyeOff, ArrowRight, Zap } from '@lucide/vue';

const showPassword        = ref(false);
const showPasswordConfirm = ref(false);

const form = useForm({
    name:                  '',
    email:                 '',
    password:              '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Create account" />

    <div class="min-h-screen flex">

        <!-- Left: Brand panel -->
        <div
            class="hidden lg:flex lg:w-1/2 flex-col justify-between p-12 relative overflow-hidden"
            style="background: radial-gradient(ellipse at 30% 50%, #FEF9EC 0%, #F5F5F0 55%, #EBEBE6 100%);"
        >
            <Link href="/" class="flex items-center text-xl font-bold tracking-tight">
                <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
                <span style="color: #1A1A1A;">.Bot</span>
            </Link>

            <div class="max-w-sm">
                <div
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold mb-6"
                    style="background-color: #F5F5F5; color: #555555; border: 1px solid #DDDDDD;"
                >
                    <Zap class="w-3.5 h-3.5" />
                    AI-powered content engine
                </div>

                <h2 class="text-4xl font-black leading-tight mb-4" style="color: #1A1A1A;">
                    Start telling your
                    <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">story.</span>
                </h2>

                <p class="text-base leading-relaxed mb-10" style="color: #555555;">
                    Join thousands of businesses that create professional content in minutes.
                </p>

                <div class="rounded-2xl p-5" style="background-color: #FFFFFF; border: 1px solid #DDDDDD;">
                    <p class="text-sm leading-relaxed mb-4" style="color: #1A1A1A;">
                        "We went from zero social presence to a full content calendar in one afternoon. StoryCreator.Bot is the real deal."
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold" style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;">
                            SR
                        </div>
                        <div>
                            <p class="text-xs font-bold" style="color: #1A1A1A;">Sarah Reynolds</p>
                            <p class="text-xs" style="color: #555555;">Owner, Reynolds & Co.</p>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-xs" style="color: #555555;">
                Trusted by <span class="font-bold" style="color: #1A1A1A;">10,000+</span> businesses telling their story
            </p>

            <div
                class="absolute -bottom-24 -right-24 w-72 h-72 rounded-full opacity-30 pointer-events-none"
                style="background: radial-gradient(circle, #FFC837, transparent);"
            />
        </div>

        <!-- Right: Register form -->
        <div class="flex-1 flex flex-col items-center justify-center px-6 py-12" style="background-color: #FFFFFF;">

            <Link href="/" class="flex items-center text-xl font-bold tracking-tight mb-10 lg:hidden">
                <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
                <span style="color: #1A1A1A;">.Bot</span>
            </Link>

            <div class="w-full max-w-sm">

                <h1 class="text-2xl font-black mb-1" style="color: #1A1A1A;">Create your account</h1>
                <p class="text-sm mb-8" style="color: #555555;">
                    Already have an account?
                    <Link :href="route('login')" class="font-semibold underline transition hover:opacity-70" style="color: #1A1A1A;">Log in</Link>
                </p>

                <form @submit.prevent="submit" class="space-y-4" novalidate>

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold mb-1.5" style="color: #1A1A1A;">Full name</label>
                        <div class="relative">
                            <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color: #AAAAAA;" />
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                autocomplete="name"
                                autofocus
                                required
                                placeholder="Jane Smith"
                                class="w-full pl-9 pr-4 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                                style="border: 1px solid #DDDDDD; color: #1A1A1A; background: #FFFFFF;"
                                :style="form.errors.name ? 'border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,0.1)' : ''"
                                @focus="(e) => !form.errors.name && (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                                @blur="(e) => !form.errors.name && (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                            />
                        </div>
                        <p v-if="form.errors.name" class="mt-1.5 text-xs" style="color: #EF4444;">{{ form.errors.name }}</p>
                    </div>

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
                                required
                                placeholder="you@company.com"
                                class="w-full pl-9 pr-4 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                                style="border: 1px solid #DDDDDD; color: #1A1A1A; background: #FFFFFF;"
                                :style="form.errors.email ? 'border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,0.1)' : ''"
                                @focus="(e) => !form.errors.email && (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                                @blur="(e) => !form.errors.email && (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-xs" style="color: #EF4444;">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold mb-1.5" style="color: #1A1A1A;">Password</label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color: #AAAAAA;" />
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                required
                                placeholder="Min. 8 characters"
                                class="w-full pl-9 pr-10 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                                style="border: 1px solid #DDDDDD; color: #1A1A1A; background: #FFFFFF;"
                                :style="form.errors.password ? 'border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,0.1)' : ''"
                                @focus="(e) => !form.errors.password && (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                                @blur="(e) => !form.errors.password && (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                            />
                            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer transition-opacity hover:opacity-70" style="color:#AAAAAA;" @click="showPassword = !showPassword">
                                <EyeOff v-if="showPassword" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs" style="color: #EF4444;">{{ form.errors.password }}</p>
                    </div>

                    <!-- Confirm password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold mb-1.5" style="color: #1A1A1A;">Confirm password</label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color: #AAAAAA;" />
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showPasswordConfirm ? 'text' : 'password'"
                                autocomplete="new-password"
                                required
                                placeholder="Repeat your password"
                                class="w-full pl-9 pr-10 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                                style="border: 1px solid #DDDDDD; color: #1A1A1A; background: #FFFFFF;"
                                :style="form.errors.password_confirmation ? 'border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,0.1)' : ''"
                                @focus="(e) => !form.errors.password_confirmation && (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                                @blur="(e) => !form.errors.password_confirmation && (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                            />
                            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer transition-opacity hover:opacity-70" style="color:#AAAAAA;" @click="showPasswordConfirm = !showPasswordConfirm">
                                <EyeOff v-if="showPasswordConfirm" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs" style="color: #EF4444;">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 py-3 rounded-lg font-bold text-sm transition-opacity duration-200 cursor-pointer mt-2"
                        :class="{ 'opacity-60 cursor-not-allowed': form.processing }"
                        style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                    >
                        <span v-if="form.processing">Creating account…</span>
                        <template v-else>
                            Create account <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </template>
                    </button>

                    <p class="text-xs text-center pt-1" style="color: #AAAAAA;">
                        By signing up you agree to our
                        <a href="#" class="underline hover:opacity-70" style="color: #555555;">Terms</a>
                        and
                        <a href="#" class="underline hover:opacity-70" style="color: #555555;">Privacy Policy</a>.
                    </p>

                </form>
            </div>
        </div>

    </div>
</template>
