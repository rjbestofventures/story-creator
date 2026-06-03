<script setup>
import { ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { Lock, Eye, EyeOff } from '@lucide/vue';

const props = defineProps({
    landing_lock_enabled:  Boolean,
    landing_lock_password: String,
});

const showPassword = ref(false);

const form = useForm({
    landing_lock_enabled:  props.landing_lock_enabled,
    landing_lock_password: props.landing_lock_password,
});

const saved = ref(false);
const save = () => form.post(route('admin.settings.access.update'));
watch(() => form.recentlySuccessful, (v) => {
    if (v) { saved.value = true; setTimeout(() => saved.value = false, 2500); }
});
</script>

<template>
    <SettingsLayout>
        <Head title="Access — Settings" />

        <div class="space-y-1 mb-6">
            <h1 class="text-xl font-black" style="color:#1A1A1A;">Access</h1>
            <p class="text-sm" style="color:#555555;">Control who can view the landing page.</p>
        </div>

        <div class="bg-white rounded-2xl p-6" style="border:1px solid #DDDDDD;">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#FEF9EC;">
                    <Lock class="w-4 h-4" style="color:#F5A000;" />
                </div>
                <div>
                    <h2 class="text-sm font-black" style="color:#1A1A1A;">Landing Page Lock</h2>
                    <p class="text-xs" style="color:#555555;">Require a password before visitors can view the landing page.</p>
                </div>
            </div>

            <form @submit.prevent="save" class="space-y-5">

                <!-- Toggle -->
                <div class="flex items-center justify-between py-3 px-4 rounded-xl" style="background:#F5F5F5;">
                    <div>
                        <p class="text-sm font-semibold" style="color:#1A1A1A;">Lock enabled</p>
                        <p class="text-xs mt-0.5" style="color:#555555;">
                            {{ form.landing_lock_enabled ? 'Visitors must enter a password.' : 'Landing page is publicly accessible.' }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="relative w-12 h-6 rounded-full transition-colors duration-200 cursor-pointer flex-shrink-0"
                        :style="form.landing_lock_enabled ? 'background:#F5A000' : 'background:#DDDDDD'"
                        @click="form.landing_lock_enabled = !form.landing_lock_enabled"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                            :class="form.landing_lock_enabled ? 'translate-x-6' : 'translate-x-0'"
                        />
                    </button>
                </div>

                <!-- Password field -->
                <div v-if="form.landing_lock_enabled">
                    <label class="block text-sm font-semibold mb-1.5" style="color:#1A1A1A;">Access password</label>
                    <div class="relative">
                        <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color:#AAAAAA;" />
                        <input
                            v-model="form.landing_lock_password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="Set a password for visitors"
                            class="w-full pl-9 pr-10 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                            style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
                            @focus="(e) => (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                            @blur="(e) => (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                        />
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer hover:opacity-70" style="color:#AAAAAA;" @click="showPassword = !showPassword">
                            <EyeOff v-if="showPassword" class="w-4 h-4" />
                            <Eye v-else class="w-4 h-4" />
                        </button>
                    </div>
                    <p v-if="form.errors.landing_lock_password" class="mt-1.5 text-xs" style="color:#EF4444;">{{ form.errors.landing_lock_password }}</p>
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
