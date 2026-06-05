<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#1A1A1A]">Profile Information</h2>
            <p class="text-sm text-[#555555] mt-0.5">Update your name and email address.</p>
        </div>

        <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-4">

            <div>
                <label for="name" class="block text-sm font-semibold text-[#1A1A1A] mb-1.5">Name</label>
                <input
                    id="name"
                    type="text"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    class="w-full h-11 px-3 rounded-lg border border-[#DDDDDD] focus:border-[#F5A000] focus:ring-1 focus:ring-[#F5A000] focus:outline-none text-sm text-[#1A1A1A] bg-white transition"
                />
                <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-500">{{ form.errors.name }}</p>
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-[#1A1A1A] mb-1.5">Email</label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    class="w-full h-11 px-3 rounded-lg border border-[#DDDDDD] focus:border-[#F5A000] focus:ring-1 focus:ring-[#F5A000] focus:outline-none text-sm text-[#1A1A1A] bg-white transition"
                />
                <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-500">{{ form.errors.email }}</p>
            </div>

            <!-- Unverified email notice -->
            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                <p class="text-sm text-[#555555]">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="font-semibold text-[#F5A000] underline hover:opacity-80 transition cursor-pointer"
                    >
                        Resend verification email
                    </Link>
                </p>
                <p v-show="status === 'verification-link-sent'" class="mt-1 text-sm font-medium text-green-600">
                    Verification link sent to your email.
                </p>
            </div>

            <div class="flex items-center gap-4 pt-1">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="h-10 px-6 rounded-lg font-bold text-sm text-[#1A1A1A] transition hover:opacity-90 cursor-pointer disabled:opacity-40"
                    style="background: linear-gradient(to right, #FFC837, #F5A000);"
                >
                    Save Changes
                </button>

                <Transition
                    enter-active-class="transition ease-in-out duration-200"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out duration-200"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm font-medium text-green-600">Saved.</p>
                </Transition>
            </div>

        </form>
    </section>
</template>
