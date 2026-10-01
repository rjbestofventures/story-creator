<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { ArrowRight, ArrowLeft } from 'lucide-vue-next';
import {
    Dialog, DialogContent, DialogTitle,
} from '@/Components/ui/dialog';

const open = defineModel('open', { default: false });

const CHECKOUT_URL = 'https://link.bestofventures.com/payment-link/6a6b5cbc7b99151a5404158c';

const isLoggedIn = computed(() => !!usePage().props.auth?.user);

const goTo = (url) => {
    open.value = false;
    if (window.location.pathname === new URL(url, window.location.origin).pathname) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }
    router.visit(url);
};

const notYet = () => {
    if (isLoggedIn.value) {
        open.value = false;
        return;
    }
    goTo(route('welcome'));
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-2xl p-8 gap-5 rounded-3xl">
            <DialogTitle class="text-center text-4xl font-black leading-tight" style="color: #1A1A1A;">
                Ready to <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Get Started?</span>
            </DialogTitle>

            <div class="rounded-2xl overflow-hidden" style="border: 1px solid #DDDDDD;">
                <div class="flex items-center gap-5 p-3" style="background-color: #FFFDF8;">
                    <a
                        :href="CHECKOUT_URL"
                        target="_blank"
                        rel="noopener noreferrer"
                        @click="open = false"
                        class="w-24 h-24 shrink-0 rounded-xl flex flex-col items-center justify-center gap-1 font-bold text-sm transition hover:opacity-90 cursor-pointer"
                        style="background: linear-gradient(to bottom right, #FFC837, #F5A000); color: #1A1A1A;"
                    >
                        I'm In
                        <ArrowRight class="w-7 h-7" :stroke-width="2.5" />
                    </a>
                    <p class="text-base leading-relaxed" style="color: #555555;">We're psyched to get you started! Pick your plan and launch your storybot.</p>
                </div>

                <div class="flex items-center gap-5 p-3 bg-white" style="border-top: 1px solid #DDDDDD;">
                    <button
                        type="button"
                        @click="notYet"
                        class="w-24 h-24 shrink-0 rounded-xl flex flex-col items-center justify-center gap-1 font-bold text-sm text-white transition hover:opacity-90 cursor-pointer"
                        style="background-color: #1A1A1A;"
                    >
                        Not Yet
                        <ArrowLeft class="w-7 h-7" :stroke-width="2.5" />
                    </button>
                    <p class="text-base leading-relaxed" style="color: #555555;">No problem. See everything you need to know before you decide.</p>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
