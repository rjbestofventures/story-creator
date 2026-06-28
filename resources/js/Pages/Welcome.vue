<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { MessageSquare, Sparkles, Download, Zap, ArrowRight, Play, Check, CircleHelp, ChevronDown } from '@lucide/vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    packs: Array,
});

const openFaq = ref(null);

const faqs = [
    { q: 'How does StoryCreator.Bot work?', a: 'Answer a few questions about your business, audience, and goals. Our engine turns your answers into a series of ready-to-publish content episodes.' },
    { q: 'Do I need to be a good writer?', a: 'Not at all. StoryCreator.Bot does the writing for you. You just provide the details about your business and we handle the rest.' },
    { q: "Can I edit the episodes after they're generated?", a: 'Yes. Every episode is fully editable. Refining or redoing an episode costs 1 credit, so you stay in full control of the final content.' },
    { q: 'How do credits work?', a: 'Everything runs on StoryBot credits. 1 credit generates 1 episode, and 1 credit refines or redoes an episode. You choose 12, 18, or 24 episodes per story. Credits never expire.' },
    { q: 'Do my credits expire?', a: 'Never. Credit packs are a one-time purchase — your credits stay in your account until you use them, with no monthly fees or expiry.' },
    { q: 'Do I need all three URLs (website, LinkedIn, social)?', a: 'No. You can provide as many or as few as you have. More context helps us generate better content, but none are required.' },
    { q: 'What if I run low on credits?', a: 'Top up anytime with a Credit Boost add-on, or buy another pack. There\'s no subscription — you only pay for what you need.' },
];

const priceDollars = (pack) => Math.round(pack.price / 100);

const popularSlug = computed(() => {
    if (!props.packs?.length) return null;
    const sorted = [...props.packs].sort((a, b) => a.price - b.price);
    return sorted[Math.floor(sorted.length / 2)]?.slug ?? null;
});

const packFeatures = (pack) => [
    `${pack.credits} StoryBot credits`,
    'Choose 12, 18 or 24 episodes per story',
    '1 credit = generate or refine 1 episode',
    'Credits never expire',
    pack.credits >= 96 ? 'Priority support' : 'Standard support',
];
</script>

<template>
    <Head title="StoryCreator.Bot — Turn Your Business Into a Story Worth Sharing" />

    <div class="min-h-screen flex flex-col" style="background: radial-gradient(ellipse at 50% 40%, #FEF9EC 0%, #F5F5F0 60%, #EFEFEA 100%);">

        <!-- Nav -->
        <header class="bg-white flex items-center justify-between px-6 md:px-8 py-4">
            <a href="/" class="flex items-center text-xl font-bold tracking-tight">
                <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
                <span style="color: #1A1A1A;">.Bot</span>
            </a>

            <nav class="flex items-center gap-3">
                <!-- Desktop only -->
                <Link
                    v-if="!$page.props.auth.user"
                    :href="route('demo')"
                    class="hidden md:flex items-center gap-2 px-4 py-2 rounded-lg border font-semibold text-sm transition hover:bg-gray-50"
                    style="border-color: #DDDDDD; color: #1A1A1A;"
                >
                    <Play class="w-4 h-4" :stroke-width="2" fill="currentColor" />
                    Test Drive the Bot
                </Link>

                <template v-if="canLogin">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="px-4 py-2 text-sm font-semibold transition"
                        style="color: #1A1A1A;"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="px-4 py-2 text-sm font-semibold transition hover:opacity-70"
                            style="color: #1A1A1A;"
                        >
                            Log In
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="px-5 py-2 rounded-lg text-sm font-bold transition hover:opacity-90"
                            style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                        >
                            Get Started
                        </Link>
                    </template>
                </template>
            </nav>
        </header>

        <!-- Hero -->
        <main class="min-h-screen flex flex-col items-center justify-center text-center px-6 py-20">

            <!-- Badge -->
            <div
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium mb-8"
                style="background-color: #F5F5F5; color: #555555; border: 1px solid #DDDDDD;"
            >
                <Zap class="w-4 h-4" />
                Your story. Powered by our intelligence.
            </div>

            <!-- Headline -->
            <h1 class="text-5xl md:text-6xl font-black leading-tight max-w-3xl mb-6" style="color: #1A1A1A;">
                Turn Your Business Into a<br />
                <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Story Worth Sharing</span>
            </h1>

            <!-- Subheading -->
            <p class="max-w-xl text-lg leading-relaxed mb-10" style="color: #555555;">
                Answer a few questions about your business and watch our story engine
                generate a series of ready-to-publish content episodes including social posts,
                blog ideas, and more, all tailored to your brand.
            </p>

            <!-- CTAs -->
            <div class="flex flex-wrap items-center justify-center gap-4">
                <Link
                    :href="canRegister ? route('register') : route('login')"
                    class="flex items-center gap-2 px-7 py-3.5 rounded-lg font-bold text-base transition hover:opacity-90"
                    style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                >
                    Get Started
                    <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                </Link>

                <Link
                    v-if="!$page.props.auth.user"
                    :href="route('demo')"
                    class="flex items-center gap-2 px-7 py-3.5 rounded-lg font-bold text-base border transition hover:bg-gray-50"
                    style="background-color: #FFFFFF; color: #1A1A1A; border-color: #DDDDDD;"
                >
                    <Play class="w-4 h-4" fill="currentColor" :stroke-width="0" />
                    Try a Live Demo
                </Link>
            </div>
        </main>

        <!-- How It Works -->
        <section class="bg-white min-h-screen flex items-center px-6">
            <div class="max-w-5xl mx-auto text-center">

                <p class="text-xs font-bold tracking-widest uppercase mb-4" style="color: #555555;">How It Works</p>

                <h2 class="text-4xl md:text-5xl font-black mb-16" style="color: #1A1A1A;">
                    Three Steps to Create Content That
                    <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Converts</span>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">

                    <!-- Step 1 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-5" style="background: linear-gradient(to right, #FFC837, #F5A000);">
                            <MessageSquare class="w-7 h-7" color="#1A1A1A" :stroke-width="2" />
                        </div>
                        <p class="text-xs font-bold tracking-widest uppercase mb-2" style="color: #555555;">Step 1</p>
                        <h3 class="text-lg font-bold mb-3" style="color: #1A1A1A;">Answer a Few Questions</h3>
                        <p class="text-sm leading-relaxed" style="color: #555555;">Tell us about your business, your audience, and what makes you unique.</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-5" style="background: linear-gradient(to right, #FFC837, #F5A000);">
                            <Sparkles class="w-7 h-7" color="#1A1A1A" :stroke-width="2" />
                        </div>
                        <p class="text-xs font-bold tracking-widest uppercase mb-2" style="color: #555555;">Step 2</p>
                        <h3 class="text-lg font-bold mb-3" style="color: #1A1A1A;">StoryCreator Works its Magic</h3>
                        <p class="text-sm leading-relaxed" style="color: #555555;">Our engine turns your answers into a series of compelling content episodes.</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-5" style="background: linear-gradient(to right, #FFC837, #F5A000);">
                            <Download class="w-7 h-7" color="#1A1A1A" :stroke-width="2" />
                        </div>
                        <p class="text-xs font-bold tracking-widest uppercase mb-2" style="color: #555555;">Step 3</p>
                        <h3 class="text-lg font-bold mb-3" style="color: #1A1A1A;">Get Your Episodes</h3>
                        <p class="text-sm leading-relaxed" style="color: #555555;">Review, refine, and use your episodes across social media, blogs, and more.</p>
                    </div>

                </div>
            </div>
        </section>

        <!-- Pricing -->
        <section class="min-h-screen flex flex-col justify-center px-6 py-20" style="background-color: #FAFAF8;">
            <div class="max-w-5xl mx-auto w-full">

                <!-- Header -->
                <div class="text-center mb-10">
                    <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color: #555555;">Pricing</p>
                    <h2 class="text-4xl md:text-5xl font-black mb-3" style="color: #1A1A1A;">Simple, Transparent Pricing</h2>
                    <p class="text-sm" style="color: #555555;">Buy a story pack when you need one. No subscription — credits never expire.</p>
                </div>

                <!-- Partner Banner -->
                <div class="rounded-2xl p-6 mb-8 flex flex-col md:flex-row md:items-center gap-6" style="background-color: #1A1A1A;">
                    <!-- Left: logo + info -->
                    <div class="flex items-start gap-4 flex-1">
                        <div class="relative shrink-0">
                            <div class="w-14 h-14 rounded-xl flex items-center justify-center text-white font-black text-xs text-center leading-tight" style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;">
                                BEST<br/>LOCAL
                            </div>
                        </div>
                        <div>
                            <span class="inline-block text-xs font-bold tracking-widest uppercase px-2 py-0.5 rounded mb-1" style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;">Partner Program</span>
                            <h3 class="text-2xl font-black text-white">Verified Business Partner</h3>
                            <p class="text-sm mb-1" style="color: #888888;">Complimentary StoryBot credits for
                                <span class="font-bold text-2xl" style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Verified Local Businesses</span>
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-8 gap-y-1 mt-3">
                                <span v-for="f in ['Complimentary story packs', 'Credits never expire', 'Priority support', 'Hands-on onboarding', 'Verified partner badge', 'Episodes for every story']"
                                    :key="f" class="flex items-center gap-1.5 text-xs" style="color: #AAAAAA;">
                                    <Check class="w-3 h-3 shrink-0" style="color: #F5A000;" :stroke-width="3" />
                                    {{ f }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Right: CTA -->
                    <div class="flex flex-col items-start md:items-end gap-2 shrink-0">
                        <Link :href="route('login')" class="flex items-center gap-2 px-6 py-2.5 rounded-lg font-bold text-sm transition hover:opacity-90" style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;">
                            Login <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </Link>
                        <Link :href="route('partner')" class="text-xs underline" style="color: #888888;">Learn how to become a verified partner →</Link>
                    </div>
                </div>

                <!-- Packs -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="pack in packs"
                        :key="pack.slug"
                        class="relative rounded-2xl bg-white p-6 flex flex-col"
                        style="border: 2px solid #F5A000;"
                    >
                        <!-- Most Popular badge -->
                        <div v-if="pack.slug === popularSlug" class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                            <span class="px-3 py-1 rounded-full text-xs font-bold" style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;">Most Popular</span>
                        </div>

                        <h3 class="text-base font-bold mb-3" style="color: #1A1A1A;">{{ pack.label }}</h3>

                        <div class="flex items-baseline gap-1 mb-1">
                            <span class="text-4xl font-black" style="color: #1A1A1A;">${{ priceDollars(pack) }}</span>
                            <span class="text-sm" style="color: #555555;">one-time</span>
                        </div>
                        <p class="text-xs mb-5" style="color: #555555;">{{ pack.credits }} credits · 12, 18 or 24 episodes per story</p>

                        <Link
                            v-if="pack.type !== 'partner'"
                            :href="route('register')"
                            class="flex items-center justify-center gap-2 w-full py-2.5 rounded-lg font-bold text-sm mb-6 transition hover:opacity-90"
                            style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                        >
                            Get Started <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </Link>
                        <Link
                            v-else
                            :href="route('partner')"
                            class="flex items-center justify-center gap-2 w-full py-2.5 rounded-lg font-bold text-sm mb-6 transition hover:bg-amber-50"
                            style="border: 2px solid #F5A000; color: #1A1A1A;"
                        >
                            For verified partners <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </Link>

                        <ul class="flex flex-col gap-2.5">
                            <li v-for="f in packFeatures(pack)" :key="f" class="flex items-center gap-2 text-sm" style="color: #555555;">
                                <Check class="w-4 h-4 shrink-0" style="color: #F5A000;" :stroke-width="2.5" />
                                {{ f }}
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </section>

        <!-- FAQ -->
        <section class="min-h-screen flex flex-col justify-center px-6 py-20" style="background-color: #FAFAF8;">
            <div class="max-w-2xl mx-auto w-full">

                <!-- Header -->
                <div class="text-center mb-10">
                    <div class="flex items-center justify-center gap-2 mb-2">
                        <CircleHelp class="w-6 h-6" style="color: #F5A000;" />
                        <h2 class="text-3xl font-black" style="color: #1A1A1A;">Frequently Asked Questions</h2>
                    </div>
                    <p class="text-sm" style="color: #555555;">Everything you need to know before getting started.</p>
                </div>

                <!-- Accordion -->
                <div class="bg-white rounded-2xl overflow-hidden" style="border: 1px solid #DDDDDD;">
                    <div
                        v-for="(faq, i) in faqs"
                        :key="i"
                        :class="i < faqs.length - 1 ? 'border-b' : ''"
                        style="border-color: #DDDDDD;"
                    >
                        <button
                            class="w-full flex items-center justify-between px-6 py-5 text-left transition hover:bg-gray-50"
                            @click="openFaq = openFaq === i ? null : i"
                        >
                            <span class="text-sm font-semibold" style="color: #1A1A1A;">{{ faq.q }}</span>
                            <ChevronDown
                                class="w-4 h-4 shrink-0 ml-4 transition-transform duration-200"
                                :style="{ color: '#555555', transform: openFaq === i ? 'rotate(180deg)' : 'rotate(0deg)' }"
                            />
                        </button>
                        <div v-if="openFaq === i" class="px-6 pb-5 text-sm leading-relaxed" style="color: #555555;">
                            {{ faq.a }}
                        </div>
                    </div>
                </div>

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
