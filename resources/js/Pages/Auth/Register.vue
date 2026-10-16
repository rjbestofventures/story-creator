<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, Check } from '@lucide/vue';
import AnnouncementBar from '@/Components/AnnouncementBar.vue';
import { retailPlans, gradBg } from '@/lib/retailPlans';

const props = defineProps({
    plan: { type: String, default: null },
});

const chosen = computed(() => retailPlans.find((p) => p.key === props.plan) ?? null);

const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
    plan: props.plan,
});

const fields = [
    { key: 'first_name', label: 'First Name', type: 'text', autocomplete: 'given-name' },
    { key: 'last_name', label: 'Last Name', type: 'text', autocomplete: 'family-name' },
    { key: 'phone', label: 'Phone', type: 'tel', autocomplete: 'tel-national', hint: 'US format, digits only, e.g. 3478245640' },
    { key: 'email', label: 'Email', type: 'email', autocomplete: 'email' },
    { key: 'password', label: 'Password', type: 'password', autocomplete: 'new-password' },
    { key: 'password_confirmation', label: 'Confirm Password', type: 'password', autocomplete: 'new-password' },
];

const onInput = (key, value) => {
    form[key] = key === 'phone' ? value.replace(/\D/g, '').slice(0, 10) : value;
};

const emailTaken = computed(() => form.errors.email?.startsWith('You already have an account'));
const loginHref = computed(() => route('login', props.plan ? { plan: props.plan } : {}));

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const inputClass = 'h-12 px-3.5 text-base rounded-[10px] border bg-white outline-none focus:border-[#F59E0B] focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-[#1A1A1A] focus-visible:outline-offset-2';
</script>

<template>
    <Head title="Create Your Account" />

    <div class="min-h-screen bg-[#F8F6F0] text-[#1A1A1A]">
        <AnnouncementBar />

        <header class="bg-white flex items-center px-5 sm:px-7 py-2.5">
            <Link :href="route('signup')"><img src="/images/storycreator-logo.png" width="160" height="40" alt="StoryCreator.Bot" class="block w-40 h-10" /></Link>
        </header>

        <main class="max-w-[520px] mx-auto w-full px-5 py-10">
            <section class="bg-white border border-[#E6E2D6] rounded-[20px] p-6 sm:p-9 shadow-[0_8px_30px_rgba(26,26,26,0.06)]">
                <div v-if="chosen" class="flex items-center justify-between gap-3 mb-6 px-4 py-3 rounded-xl bg-amber-50 border border-[#F59E0B]/40">
                    <div class="flex items-center gap-2 text-sm">
                        <Check class="w-4 h-4 text-[#F59E0B]" :stroke-width="3" />
                        <span>You picked <b>{{ chosen.name }}</b> · {{ chosen.price }} one-time</span>
                    </div>
                    <Link :href="route('signup') + '#pricing'" class="text-xs font-semibold text-[#555555] hover:text-[#1A1A1A] underline">Change</Link>
                </div>

                <h1 class="text-2xl font-extrabold tracking-[-0.01em]">Create Your Account</h1>
                <p class="mt-2 text-base leading-normal text-[#555555]">Sign up, confirm your email, then choose your plan and start your first story.</p>

                <form novalidate @submit.prevent="submit">
                    <div v-for="f in fields" :key="f.key" class="flex flex-col gap-1.5 mt-5">
                        <label :for="f.key" class="text-sm font-bold">{{ f.label }}</label>
                        <input
                            :id="f.key"
                            :value="form[f.key]"
                            :type="f.type"
                            :autocomplete="f.autocomplete"
                            :inputmode="f.key === 'phone' ? 'numeric' : undefined"
                            :maxlength="f.key === 'phone' ? 10 : undefined"
                            :class="[inputClass, form.errors[f.key] ? 'border-[#B42318]' : 'border-[#CFCABB]']"
                            @input="(e) => onInput(f.key, e.target.value)"
                        />
                        <span v-if="f.hint" class="text-[13px] text-[#666666]">{{ f.hint }}</span>
                        <p v-if="form.errors[f.key]" role="alert" class="text-sm font-semibold text-[#B42318]">
                            {{ form.errors[f.key] }}
                            <Link v-if="f.key === 'email' && emailTaken" :href="loginHref" class="underline text-[#1A1A1A]">Log in</Link>
                        </p>
                    </div>

                    <button type="submit" :disabled="form.processing" :class="gradBg" class="mt-6 w-full h-14 rounded-xl text-[17px] font-bold text-[#1A1A1A] flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60">
                        Create My Account <ArrowRight class="w-[18px] h-[18px]" :stroke-width="2.2" />
                    </button>
                </form>

                <p class="mt-5 text-sm text-center text-[#555555]">
                    Already have an account?
                    <Link :href="loginHref" class="font-bold text-[#1A1A1A] underline">Log in</Link>
                </p>
            </section>
        </main>
    </div>
</template>
