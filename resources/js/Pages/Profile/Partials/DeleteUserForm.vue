<script setup>
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section>
        <div class="mb-5">
            <h2 class="text-base font-bold text-[#1A1A1A]">Delete Account</h2>
            <p class="text-sm text-[#555555] mt-0.5">
                Permanently delete your account and all associated data. This cannot be undone.
            </p>
        </div>

        <button
            type="button"
            @click="confirmUserDeletion"
            class="h-10 px-6 rounded-lg font-bold text-sm text-white bg-red-500 hover:bg-red-600 transition cursor-pointer"
        >
            Delete Account
        </button>

        <!-- Confirmation modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                leave-active-class="transition ease-in duration-150"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="confirmingUserDeletion"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    style="background: rgba(0,0,0,0.4);"
                    @click.self="closeModal"
                >
                    <div class="bg-white rounded-2xl border border-[#DDDDDD] shadow-xl w-full max-w-md p-6">
                        <h3 class="text-lg font-black text-[#1A1A1A] mb-2">Delete your account?</h3>
                        <p class="text-sm text-[#555555] mb-5">
                            All your stories, chapters, and data will be permanently deleted.
                            Enter your password to confirm.
                        </p>

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-[#1A1A1A] mb-1.5">Password</label>
                            <input
                                ref="passwordInput"
                                type="password"
                                v-model="form.password"
                                placeholder="Enter your password"
                                @keyup.enter="deleteUser"
                                class="w-full h-11 px-3 rounded-lg border border-[#DDDDDD] focus:border-red-400 focus:ring-1 focus:ring-red-400 focus:outline-none text-sm text-[#1A1A1A] bg-white transition"
                            />
                            <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-500">{{ form.errors.password }}</p>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                @click="closeModal"
                                class="h-10 px-5 rounded-lg font-semibold text-sm text-[#555555] border border-[#DDDDDD] bg-white hover:bg-gray-50 transition cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                :disabled="form.processing"
                                @click="deleteUser"
                                class="h-10 px-5 rounded-lg font-bold text-sm text-white bg-red-500 hover:bg-red-600 transition cursor-pointer disabled:opacity-40"
                            >
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </section>
</template>
