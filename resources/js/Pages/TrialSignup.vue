<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, Check } from 'lucide-vue-next';

const submitted = ref(false);

const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
});

const fields = [
    { key: 'first_name', label: 'First Name', type: 'text', autocomplete: 'given-name' },
    { key: 'last_name', label: 'Last Name', type: 'text', autocomplete: 'family-name' },
    { key: 'phone', label: 'Phone', type: 'tel', autocomplete: 'tel', hint: 'US format, digits only, e.g. 3478245640' },
    { key: 'email', label: 'Email', type: 'email', autocomplete: 'email' },
];

const onInput = (key, value) => {
    form[key] = key === 'phone' ? value.replace(/\D/g, '').slice(0, 10) : value;
};

const submit = () => {
    form.post(route('trial.signup.submit'), {
        onSuccess: () => { submitted.value = true; },
    });
};
</script>

<template>
    <Head title="Start Your Complementary Trial — StoryCreator.Bot" />

    <div class="min-h-screen flex flex-col items-center justify-center px-6 py-12" style="background: radial-gradient(ellipse at 50% 40%, #FEF9EC 0%, #F5F5F0 60%, #EFEFEA 100%);">
        <Link href="/" class="flex items-center text-xl font-bold tracking-tight mb-10">
            <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
            <span style="color: #1A1A1A;">.Bot</span>
        </Link>

        <div class="w-full max-w-sm bg-white rounded-2xl p-8" style="border: 1px solid #DDDDDD;">

            <template v-if="submitted">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-amber-50 mb-4">
                        <Check class="w-6 h-6" style="color: #F5A000;" />
                    </div>
                    <h1 class="text-xl font-black mb-2" style="color: #1A1A1A;">Check your email</h1>
                    <p class="text-sm" style="color: #555555;">Your Complementary Trial account is ready. We sent you a link to set your password and get started.</p>
                </div>
            </template>

            <template v-else>
                <h1 class="text-xl font-black mb-1" style="color: #1A1A1A;">Start Your Complementary Trial</h1>
                <p class="text-sm mb-6" style="color: #555555;">Create your account and StoryBot will write your first story.</p>

                <form @submit.prevent="submit" class="space-y-4" novalidate>
                    <div v-for="(f, i) in fields" :key="f.key">
                        <label :for="f.key" class="block text-sm font-semibold mb-1.5" style="color: #1A1A1A;">{{ f.label }}</label>
                        <input
                            :id="f.key"
                            :value="form[f.key]"
                            @input="(e) => onInput(f.key, e.target.value)"
                            :type="f.type"
                            :autocomplete="f.autocomplete"
                            :inputmode="f.key === 'phone' ? 'numeric' : undefined"
                            :maxlength="f.key === 'phone' ? 10 : undefined"
                            :autofocus="i === 0"
                            required
                            class="w-full px-3 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                            style="border: 1px solid #DDDDDD; color: #1A1A1A; background: #FFFFFF;"
                            :style="form.errors[f.key] ? 'border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,0.1)' : ''"
                            @focus="(e) => !form.errors[f.key] && (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                            @blur="(e) => !form.errors[f.key] && (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                        />
                        <p v-if="f.hint" class="mt-1.5 text-xs" style="color: #AAAAAA;">{{ f.hint }}</p>
                        <p v-if="form.errors[f.key]" class="mt-1.5 text-xs" style="color: #EF4444;">{{ form.errors[f.key] }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 py-3 rounded-lg font-bold text-sm transition-opacity duration-200 cursor-pointer mt-2"
                        :class="{ 'opacity-60 cursor-not-allowed': form.processing }"
                        style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                    >
                        <span v-if="form.processing">Creating your account…</span>
                        <template v-else>
                            Start My Trial <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </template>
                    </button>
                </form>
            </template>

        </div>
    </div>
</template>
