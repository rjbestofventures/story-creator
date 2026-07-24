<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { ArrowRight, Check } from 'lucide-vue-next';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription,
} from '@/Components/ui/dialog';

const open = defineModel('open', { default: false });

const submitted = ref(false);

const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
});

const submit = () => {
    form.post(route('partner.apply.submit'), {
        preserveScroll: true,
        onSuccess: () => { submitted.value = true; },
    });
};

// Reset to a blank form each time the dialog is reopened.
watch(open, (isOpen) => {
    if (isOpen) {
        submitted.value = false;
        form.reset();
        form.clearErrors();
    }
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-sm">
            <template v-if="submitted">
                <div class="text-center py-4">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-amber-50 mb-4">
                        <Check class="w-6 h-6" style="color: #F5A000;" />
                    </div>
                    <h2 class="text-xl font-black mb-2" style="color: #1A1A1A;">Application received</h2>
                    <p class="text-sm" style="color: #555555;">Thanks for applying to become a Verified Business Partner. Our team will be in touch shortly.</p>
                </div>
            </template>

            <template v-else>
                <DialogHeader>
                    <DialogTitle class="text-[#1A1A1A]">Become a Verified Partner</DialogTitle>
                    <DialogDescription class="text-[#555555]">Tell us how to reach you and we'll follow up about joining the program.</DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submit" class="space-y-4" novalidate>
                    <div>
                        <label for="dlg_first_name" class="block text-sm font-semibold mb-1.5" style="color: #1A1A1A;">First Name</label>
                        <input
                            id="dlg_first_name"
                            v-model="form.first_name"
                            type="text"
                            autocomplete="given-name"
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
                        <label for="dlg_last_name" class="block text-sm font-semibold mb-1.5" style="color: #1A1A1A;">Last Name</label>
                        <input
                            id="dlg_last_name"
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
                        <label for="dlg_phone" class="block text-sm font-semibold mb-1.5" style="color: #1A1A1A;">Phone</label>
                        <input
                            id="dlg_phone"
                            :value="form.phone"
                            @input="(e) => form.phone = e.target.value.replace(/\D/g, '').slice(0, 11)"
                            type="tel"
                            inputmode="numeric"
                            autocomplete="tel"
                            placeholder="13478245640"
                            maxlength="11"
                            required
                            class="w-full px-3 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                            style="border: 1px solid #DDDDDD; color: #1A1A1A; background: #FFFFFF;"
                            :style="form.errors.phone ? 'border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,0.1)' : ''"
                            @focus="(e) => !form.errors.phone && (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                            @blur="(e) => !form.errors.phone && (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                        />
                        <p class="mt-1.5 text-xs" style="color: #AAAAAA;">US format, digits only — e.g. 13478245640</p>
                        <p v-if="form.errors.phone" class="mt-1.5 text-xs" style="color: #EF4444;">{{ form.errors.phone }}</p>
                    </div>

                    <div>
                        <label for="dlg_email" class="block text-sm font-semibold mb-1.5" style="color: #1A1A1A;">Email</label>
                        <input
                            id="dlg_email"
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
        </DialogContent>
    </Dialog>
</template>
