<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#1A1A1A]">Update Password</h2>
            <p class="text-sm text-[#555555] mt-0.5">Use a long, random password to keep your account secure.</p>
        </div>

        <form @submit.prevent="updatePassword" class="space-y-4">

            <div>
                <label for="current_password" class="block text-sm font-semibold text-[#1A1A1A] mb-1.5">Current Password</label>
                <input
                    id="current_password"
                    ref="currentPasswordInput"
                    type="password"
                    v-model="form.current_password"
                    autocomplete="current-password"
                    class="w-full h-11 px-3 rounded-lg border border-[#DDDDDD] focus:border-[#F5A000] focus:ring-1 focus:ring-[#F5A000] focus:outline-none text-sm text-[#1A1A1A] bg-white transition"
                />
                <p v-if="form.errors.current_password" class="mt-1.5 text-xs text-red-500">{{ form.errors.current_password }}</p>
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-[#1A1A1A] mb-1.5">New Password</label>
                <input
                    id="password"
                    ref="passwordInput"
                    type="password"
                    v-model="form.password"
                    autocomplete="new-password"
                    class="w-full h-11 px-3 rounded-lg border border-[#DDDDDD] focus:border-[#F5A000] focus:ring-1 focus:ring-[#F5A000] focus:outline-none text-sm text-[#1A1A1A] bg-white transition"
                />
                <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-500">{{ form.errors.password }}</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-[#1A1A1A] mb-1.5">Confirm Password</label>
                <input
                    id="password_confirmation"
                    type="password"
                    v-model="form.password_confirmation"
                    autocomplete="new-password"
                    class="w-full h-11 px-3 rounded-lg border border-[#DDDDDD] focus:border-[#F5A000] focus:ring-1 focus:ring-[#F5A000] focus:outline-none text-sm text-[#1A1A1A] bg-white transition"
                />
                <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs text-red-500">{{ form.errors.password_confirmation }}</p>
            </div>

            <div class="flex items-center gap-4 pt-1">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="h-10 px-6 rounded-lg font-bold text-sm text-[#1A1A1A] transition hover:opacity-90 cursor-pointer disabled:opacity-40"
                    style="background: linear-gradient(to right, #FFC837, #F5A000);"
                >
                    Update Password
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
