<script setup>
import { ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { CreditCard, Eye, EyeOff, CircleCheck, CircleDot } from '@lucide/vue';

const props = defineProps({
    stripe_key:            String,
    stripe_secret:         String,
    stripe_webhook_secret: String,
    env_key_set:           Boolean,
    env_secret_set:        Boolean,
    env_webhook_set:       Boolean,
});

const show = ref({ key: false, secret: false, webhook: false });

const form = useForm({
    stripe_key:            props.stripe_key,
    stripe_secret:         props.stripe_secret,
    stripe_webhook_secret: props.stripe_webhook_secret,
});

const saved = ref(false);
const save = () => form.post(route('admin.settings.stripe.update'));
watch(() => form.recentlySuccessful, (v) => {
    if (v) { saved.value = true; setTimeout(() => saved.value = false, 2500); }
});

const fields = [
    {
        key:         'stripe_key',
        showKey:     'key',
        label:       'Publishable Key',
        hint:        'Used on the frontend. Starts with pk_live_ or pk_test_',
        placeholder: 'pk_live_...',
        envSet:      props.env_key_set,
    },
    {
        key:         'stripe_secret',
        showKey:     'secret',
        label:       'Secret Key',
        hint:        'Used server-side only. Starts with sk_live_ or sk_test_',
        placeholder: 'sk_live_...',
        envSet:      props.env_secret_set,
    },
    {
        key:         'stripe_webhook_secret',
        showKey:     'webhook',
        label:       'Webhook Secret',
        hint:        'Found in the Stripe dashboard under Webhooks. Starts with whsec_',
        placeholder: 'whsec_...',
        envSet:      props.env_webhook_set,
    },
];
</script>

<template>
    <SettingsLayout>
        <Head title="Stripe — Settings" />

        <div class="space-y-1 mb-6">
            <h1 class="text-xl font-black" style="color:#1A1A1A;">Stripe</h1>
            <p class="text-sm" style="color:#555555;">
                Override the Stripe keys stored in <code class="text-xs px-1 py-0.5 rounded" style="background:#F5F5F5; color:#1A1A1A;">.env</code> without touching the server.
                Database values take priority over <code class="text-xs px-1 py-0.5 rounded" style="background:#F5F5F5; color:#1A1A1A;">.env</code>.
            </p>
        </div>

        <div class="bg-white rounded-2xl p-6" style="border:1px solid #DDDDDD;">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#FEF9EC;">
                    <CreditCard class="w-4 h-4" style="color:#F5A000;" />
                </div>
                <div>
                    <h2 class="text-sm font-black" style="color:#1A1A1A;">API Keys</h2>
                    <p class="text-xs" style="color:#555555;">Leave a field blank to keep the current value unchanged.</p>
                </div>
            </div>

            <form @submit.prevent="save" class="space-y-6">

                <div v-for="f in fields" :key="f.key" class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-bold" style="color:#1A1A1A;">{{ f.label }}</label>
                        <!-- Source badge -->
                        <span
                            class="flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                            :style="form[f.key]
                                ? 'background:#F0FDF4; color:#16A34A;'
                                : f.envSet
                                    ? 'background:#F5F5F5; color:#555555;'
                                    : 'background:#FEF2F2; color:#DC2626;'"
                        >
                            <CircleCheck v-if="form[f.key]" class="w-3 h-3" />
                            <CircleDot v-else class="w-3 h-3" />
                            {{ form[f.key] ? 'Database override' : f.envSet ? 'Using .env' : 'Not set' }}
                        </span>
                    </div>
                    <p class="text-xs" style="color:#AAAAAA;">{{ f.hint }}</p>
                    <div class="relative">
                        <input
                            v-model="form[f.key]"
                            :type="show[f.showKey] ? 'text' : 'password'"
                            :placeholder="f.placeholder"
                            class="w-full pr-10 pl-3 py-2.5 rounded-lg text-sm font-mono outline-none transition-all duration-200"
                            style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
                            @focus="(e) => (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                            @blur="(e) => (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                        />
                        <button
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer hover:opacity-70 transition-opacity"
                            style="color:#AAAAAA;"
                            @click="show[f.showKey] = !show[f.showKey]"
                        >
                            <EyeOff v-if="show[f.showKey]" class="w-4 h-4" />
                            <Eye v-else class="w-4 h-4" />
                        </button>
                    </div>
                    <p v-if="form.errors[f.key]" class="text-xs" style="color:#EF4444;">{{ form.errors[f.key] }}</p>
                </div>

                <!-- Save -->
                <div class="flex items-center gap-3 pt-1">
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

        <!-- Info box -->
        <div class="mt-4 rounded-xl px-4 py-3 text-xs leading-relaxed" style="background:#FFFBF0; border:1px solid #FEE4A0; color:#92600A;">
            <strong>Live vs Test keys:</strong> Use <code class="font-mono">pk_test_</code> / <code class="font-mono">sk_test_</code> for development and <code class="font-mono">pk_live_</code> / <code class="font-mono">sk_live_</code> for production. Webhook secrets are environment-specific — make sure the webhook in your Stripe dashboard points to this server's URL.
        </div>

    </SettingsLayout>
</template>
