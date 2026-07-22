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

const submit = () => {
    form.post(route('partner.apply.submit'), {
        onSuccess: () => { submitted.value = true; },
    });
};
</script>

<template>
    <Head title="Become a Verified Partner — StoryCreator.Bot" />

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
                    <h1 class="text-xl font-black mb-2" style="color: #1A1A1A;">Application received</h1>
                    <p class="text-sm" style="color: #555555;">Thanks for applying to become a Verified Business Partner. Our team will be in touch shortly.</p>
                </div>
            </template>

            <template v-else>
                <h1 class="text-xl font-black mb-1" style="color: #1A1A1A;">Become a Verified Partner</h1>
                <p class="text-sm mb-6" style="color: #555555;">Tell us how to reach you and we'll follow up about joining the program.</p>

                <form @submit.prevent="submit" class="space-y-4" novalidate>
                    <div>
                        <label for="first_name" class="block text-sm font-semibold mb-1.5" style="color: #1A1A1A;">First Name</label>
                        <input
                            id="first_name"
                            v-model="form.first_name"
                            type="text"
                            autocomplete="given-name"
                            autofocus
                            required
                            class="w-full px-3 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                            style="border: 1px solid #DDDDDD; color: #1A1A1A; background: #FFFFFF;"
                            :style="form.errors.first_name ? 'border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,0.1)' : ''"
                            @focus="(e) => !form.errors.first_name && (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                            @blur="(e) => !form.errors.first_name && (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                        />
                        <p v-if="form.errors.first_name" class="mt-1.5 text-xs" style="color: #EF4444;">{{ form.errors.first_name }}</p>
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-semibold mb-1.5" style="color: #1A1A1A;">Last Name</label>
                        <input
                            id="last_name"
                            v-model="form.last_name"
                            type="text"
                            autocomplete="family-name"
                            required
                            class="w-full px-3 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                            style="border: 1px solid #DDDDDD; color: #1A1A1A; background: #FFFFFF;"
                            :style="form.errors.last_name ? 'border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,0.1)' : ''"
                            @focus="(e) => !form.errors.last_name && (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                            @blur="(e) => !form.errors.last_name && (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                        />
                        <p v-if="form.errors.last_name" class="mt-1.5 text-xs" style="color: #EF4444;">{{ form.errors.last_name }}</p>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-semibold mb-1.5" style="color: #1A1A1A;">Phone</label>
                        <input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            autocomplete="tel"
                            required
                            class="w-full px-3 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                            style="border: 1px solid #DDDDDD; color: #1A1A1A; background: #FFFFFF;"
                            :style="form.errors.phone ? 'border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,0.1)' : ''"
                            @focus="(e) => !form.errors.phone && (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                            @blur="(e) => !form.errors.phone && (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                        />
                        <p v-if="form.errors.phone" class="mt-1.5 text-xs" style="color: #EF4444;">{{ form.errors.phone }}</p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold mb-1.5" style="color: #1A1A1A;">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            class="w-full px-3 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                            style="border: 1px solid #DDDDDD; color: #1A1A1A; background: #FFFFFF;"
                            :style="form.errors.email ? 'border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,0.1)' : ''"
                            @focus="(e) => !form.errors.email && (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                            @blur="(e) => !form.errors.email && (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                        />
                        <p v-if="form.errors.email" class="mt-1.5 text-xs" style="color: #EF4444;">{{ form.errors.email }}</p>
                    </div>

                    <p v-if="form.errors.form" class="text-xs" style="color: #EF4444;">{{ form.errors.form }}</p>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 py-3 rounded-lg font-bold text-sm transition-opacity duration-200 cursor-pointer mt-2"
                        :class="{ 'opacity-60 cursor-not-allowed': form.processing }"
                        style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                    >
                        <span v-if="form.processing">Submitting…</span>
                        <template v-else>
                            Submit Application <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </template>
                    </button>
                </form>
            </template>

        </div>
    </div>
</template>
