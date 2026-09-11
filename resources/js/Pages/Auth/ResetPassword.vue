<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Lock, Eye, EyeOff, ArrowRight } from '@lucide/vue';
import Footer from '@/Components/Footer.vue';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const showPassword        = ref(false);
const showPasswordConfirm = ref(false);

const form = useForm({
    token:                 props.token,
    email:                 props.email,
    password:              '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset password" />

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
                    <Lock class="w-7 h-7" style="color:#F5A000;" />
                </div>

                <h1 class="text-xl font-black mb-1" style="color: #1A1A1A;">Set a new password</h1>
                <p class="text-sm mb-6" style="color: #555555;">Choose a strong password for your account.</p>

                <form @submit.prevent="submit" class="space-y-4" novalidate>

                    <!-- Email (hidden, for password managers) -->
                    <div>
                        <label for="email" class="block text-sm font-semibold mb-1.5" style="color:#1A1A1A;">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="username"
                            required
                            class="w-full px-4 py-2.5 rounded-lg text-sm outline-none"
                            style="border:1px solid #DDDDDD; color:#1A1A1A; background:#F5F5F5;"
                            readonly
                        />
                        <p v-if="form.errors.email" class="mt-1.5 text-xs" style="color:#EF4444;">{{ form.errors.email }}</p>
                    </div>

                    <!-- New password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold mb-1.5" style="color:#1A1A1A;">New password</label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color:#AAAAAA;" />
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                autofocus
                                required
                                placeholder="Min. 8 characters"
                                class="w-full pl-9 pr-10 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                                style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
                                :style="form.errors.password ? 'border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,0.1)' : ''"
                                @focus="(e) => !form.errors.password && (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                                @blur="(e) => !form.errors.password && (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                            />
                            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer transition-opacity hover:opacity-70" style="color:#AAAAAA;" @click="showPassword = !showPassword">
                                <EyeOff v-if="showPassword" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs" style="color:#EF4444;">{{ form.errors.password }}</p>
                    </div>

                    <!-- Confirm password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold mb-1.5" style="color:#1A1A1A;">Confirm new password</label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color:#AAAAAA;" />
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showPasswordConfirm ? 'text' : 'password'"
                                autocomplete="new-password"
                                required
                                placeholder="Repeat your password"
                                class="w-full pl-9 pr-10 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                                style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
                                :style="form.errors.password_confirmation ? 'border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,0.1)' : ''"
                                @focus="(e) => !form.errors.password_confirmation && (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                                @blur="(e) => !form.errors.password_confirmation && (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                            />
                            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer transition-opacity hover:opacity-70" style="color:#AAAAAA;" @click="showPasswordConfirm = !showPasswordConfirm">
                                <EyeOff v-if="showPasswordConfirm" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs" style="color:#EF4444;">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg font-bold text-sm transition-opacity duration-200 cursor-pointer mt-2"
                        :class="{ 'opacity-60 cursor-not-allowed': form.processing }"
                        style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                    >
                        <span v-if="form.processing">Saving…</span>
                        <template v-else>
                            Reset password <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </template>
                    </button>
                </form>
            </div>

            <p class="text-center mt-6 text-sm" style="color:#555555;">
                Remember your password?
                <Link :href="route('login')" class="font-semibold underline transition hover:opacity-70" style="color:#1A1A1A;">Log in</Link>
            </p>
        </div>
      </div>
      <Footer />
    </div>
</template>
