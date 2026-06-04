<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Check, ArrowRight, Star, Building2, Users, Zap, ShieldCheck, Calendar } from '@lucide/vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const steps = [
    {
        number: '01',
        title: 'Apply Online',
        description: 'Fill out a short application telling us about your business — what you do, who you serve, and your content goals.',
    },
    {
        number: '02',
        title: 'Verification Review',
        description: 'Our team reviews your application within 2–3 business days. We verify your business is legitimate and locally established.',
    },
    {
        number: '03',
        title: 'Get Approved & Onboarded',
        description: "Once approved, you receive your Verified Partner badge, full account access, and a complimentary six-month trial at no charge.",
    },
    {
        number: '04',
        title: 'Start Publishing',
        description: 'Log in, answer a few questions about your business, and StoryCreator.Bot generates your first story — ready to publish.',
    },
];

const eligibility = [
    'Registered business operating for at least 6 months',
    'Primarily serves a local or regional customer base',
    'Has an active online presence (website, social, or Google Business)',
    'Committed to publishing at least one story per month',
];

const perks = [
    { icon: Star, title: 'Verified Badge', body: 'Display the Best Local Verified Partner badge on your website and social profiles to build trust with local customers.' },
    { icon: Calendar, title: 'Six Months Free', body: 'Your first six months are completely free. No credit card required at signup. Full access from day one.' },
    { icon: Zap, title: '12 Episodes per Story', body: 'Each story your bot generates is broken into 12 shareable episodes — ready for social, email, or your website.' },
    { icon: Users, title: '2 Stories per Month', body: 'Publish two full stories every month, covering different angles, offers, or audiences.' },
    { icon: ShieldCheck, title: 'Credits Accumulate', body: "Unused story and revision credits roll over — they never expire, so you're never left starting from zero." },
    { icon: Building2, title: 'Business-First Content', body: 'Every story is written around your specific business, not generic templates. Your voice, your market, your story.' },
];
</script>

<template>
    <Head title="Become a Verified Business Partner — StoryCreator.Bot" />

    <div class="min-h-screen flex flex-col" style="background-color: #FAFAF8;">

        <!-- Nav -->
        <header class="bg-white flex items-center justify-between px-6 md:px-8 py-4" style="border-bottom: 1px solid #DDDDDD;">
            <Link href="/" class="flex items-center text-xl font-bold tracking-tight">
                <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
                <span style="color: #1A1A1A;">.Bot</span>
            </Link>
            <nav class="flex items-center gap-3">
                <Link
                    v-if="canLogin"
                    :href="route('login')"
                    class="px-4 py-2 rounded-lg border font-semibold text-sm transition hover:bg-gray-50"
                    style="border-color: #DDDDDD; color: #1A1A1A;"
                >
                    Log In
                </Link>
                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg font-bold text-sm transition hover:opacity-90"
                    style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                >
                    Get Started <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                </Link>
            </nav>
        </header>

        <!-- Hero -->
        <section class="px-6 md:px-10 py-20 md:py-28 text-center max-w-3xl mx-auto">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest mb-6" style="background-color: #FEF3D0; color: #B87800;">
                Partner Program
            </div>
            <h1 class="text-4xl md:text-5xl font-black leading-tight mb-5" style="color: #1A1A1A;">
                Become a<br />
                <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Verified Business Partner</span>
            </h1>
            <p class="text-lg leading-relaxed mb-10" style="color: #555555;">
                Local businesses accepted into the StoryCreator.Bot partner program get a verified badge, six months free, and a content engine that tells your story month after month — automatically.
            </p>
            <Link
                :href="route('register')"
                class="inline-flex items-center gap-2 px-8 py-4 rounded-lg font-bold text-base transition hover:opacity-90"
                style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
            >
                Apply Now — It's Free <ArrowRight class="w-5 h-5" :stroke-width="2.5" />
            </Link>
            <p class="text-xs mt-4" style="color: #888888;">No credit card required. 2–3 business day review.</p>
        </section>

        <!-- Perks -->
        <section class="px-6 md:px-10 py-16" style="background-color: #FFFFFF; border-top: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD;">
            <div class="max-w-5xl mx-auto">
                <p class="text-xs font-bold tracking-widest uppercase text-center mb-3" style="color: #555555;">What You Get</p>
                <h2 class="text-2xl md:text-3xl font-black text-center mb-12" style="color: #1A1A1A;">Everything included in the partner plan</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    <div
                        v-for="perk in perks"
                        :key="perk.title"
                        class="rounded-xl p-6"
                        style="border: 1px solid #DDDDDD; background-color: #FAFAF8;"
                    >
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-4" style="background-color: #FEF3D0;">
                            <component :is="perk.icon" class="w-5 h-5" style="color: #F5A000;" :stroke-width="2" />
                        </div>
                        <h3 class="font-bold text-sm mb-1.5" style="color: #1A1A1A;">{{ perk.title }}</h3>
                        <p class="text-sm leading-relaxed" style="color: #555555;">{{ perk.body }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="px-6 md:px-10 py-16 max-w-4xl mx-auto w-full">
            <p class="text-xs font-bold tracking-widest uppercase text-center mb-3" style="color: #555555;">The Process</p>
            <h2 class="text-2xl md:text-3xl font-black text-center mb-12" style="color: #1A1A1A;">How to become a verified partner</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="step in steps"
                    :key="step.number"
                    class="flex gap-5 rounded-xl p-6 bg-white"
                    style="border: 1px solid #DDDDDD;"
                >
                    <span class="text-3xl font-black shrink-0 leading-none mt-0.5" style="color: #F5A000;">{{ step.number }}</span>
                    <div>
                        <h3 class="font-bold text-sm mb-1.5" style="color: #1A1A1A;">{{ step.title }}</h3>
                        <p class="text-sm leading-relaxed" style="color: #555555;">{{ step.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Eligibility -->
        <section class="px-6 md:px-10 py-16" style="background-color: #1A1A1A;">
            <div class="max-w-3xl mx-auto text-center">
                <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color: #888888;">Who Qualifies</p>
                <h2 class="text-2xl md:text-3xl font-black mb-10 text-white">Eligibility requirements</h2>
                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left mb-12">
                    <li
                        v-for="item in eligibility"
                        :key="item"
                        class="flex items-start gap-3 rounded-xl px-5 py-4"
                        style="background-color: #2A2A2A;"
                    >
                        <Check class="w-4 h-4 shrink-0 mt-0.5" style="color: #F5A000;" :stroke-width="3" />
                        <span class="text-sm leading-relaxed" style="color: #CCCCCC;">{{ item }}</span>
                    </li>
                </ul>
                <Link
                    :href="route('register')"
                    class="inline-flex items-center gap-2 px-8 py-4 rounded-lg font-bold text-base transition hover:opacity-90"
                    style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                >
                    Apply for the Partner Program <ArrowRight class="w-5 h-5" :stroke-width="2.5" />
                </Link>
                <p class="text-xs mt-4" style="color: #666666;">Questions? Email us at <a href="mailto:partners@storycreator.bot" class="underline" style="color: #F5A000;">partners@storycreator.bot</a></p>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-white px-6 md:px-10 py-5 flex flex-col md:flex-row items-center justify-between gap-3" style="border-top: 1px solid #DDDDDD;">
            <p class="text-xs" style="color: #555555;">© 2026 StoryCreator.Bot. All rights reserved.</p>
            <nav class="flex items-center gap-6">
<Link :href="route('terms')" class="text-xs transition hover:opacity-70" style="color: #555555;">Terms</Link>
                <Link :href="route('privacy')" class="text-xs transition hover:opacity-70" style="color: #555555;">Privacy</Link>
            </nav>
        </footer>

    </div>
</template>
