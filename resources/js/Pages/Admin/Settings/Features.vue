<script setup>
import { ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { ShoppingBag } from '@lucide/vue';

const props = defineProps({
    buy_credits_button_enabled: Boolean,
});

const form = useForm({
    buy_credits_button_enabled: props.buy_credits_button_enabled,
});

const saved = ref(false);
const save = () => form.post(route('admin.settings.features.update'));
watch(() => form.recentlySuccessful, (v) => {
    if (v) { saved.value = true; setTimeout(() => saved.value = false, 2500); }
});
</script>

<template>
    <SettingsLayout>
        <Head title="Features — Settings" />

        <div class="space-y-1 mb-6">
            <h1 class="text-xl font-black" style="color:#1A1A1A;">Features</h1>
            <p class="text-sm" style="color:#555555;">Toggle optional UI elements on or off.</p>
        </div>

        <div class="bg-white rounded-2xl p-6" style="border:1px solid #DDDDDD;">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#FEF9EC;">
                    <ShoppingBag class="w-4 h-4" style="color:#F5A000;" />
                </div>
                <div>
                    <h2 class="text-sm font-black" style="color:#1A1A1A;">Buy Credits Button</h2>
                    <p class="text-xs" style="color:#555555;">Show the "Buy StoryBot Credits" button on the story library page.</p>
                </div>
            </div>

            <form @submit.prevent="save" class="space-y-5">

                <!-- Toggle -->
                <div class="flex items-center justify-between py-3 px-4 rounded-xl" style="background:#F5F5F5;">
                    <div>
                        <p class="text-sm font-semibold" style="color:#1A1A1A;">Button visible</p>
                        <p class="text-xs mt-0.5" style="color:#555555;">
                            {{ form.buy_credits_button_enabled ? 'Users can see the button and buy credits.' : 'Button is hidden from all users.' }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="relative w-12 h-6 rounded-full transition-colors duration-200 cursor-pointer flex-shrink-0"
                        :style="form.buy_credits_button_enabled ? 'background:#F5A000' : 'background:#DDDDDD'"
                        @click="form.buy_credits_button_enabled = !form.buy_credits_button_enabled"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                            :class="form.buy_credits_button_enabled ? 'translate-x-6' : 'translate-x-0'"
                        />
                    </button>
                </div>

                <!-- Save -->
                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing || !form.isDirty"
                        class="px-5 py-2.5 rounded-lg font-bold text-sm transition-opacity duration-200 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                        style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                    >
                        {{ form.processing ? 'Saving…' : 'Save changes' }}
                    </button>
                    <span v-if="saved" class="text-sm font-medium" style="color:#16A34A;">Saved!</span>
                </div>

            </form>
        </div>

    </SettingsLayout>
</template>
