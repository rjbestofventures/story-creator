<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { MessageSquare, Sparkles, Download, Zap, ArrowRight, Play, Check, CircleHelp, ChevronDown } from '@lucide/vue';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription,
} from '@/Components/ui/dialog';
import AnnouncementBar from '@/Components/AnnouncementBar.vue';
import Footer from '@/Components/Footer.vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    packs: Array,
});

const openFaq = ref(null);
const learnMorePack = ref(null);

const faqs = [
    { q: 'How does StoryCreator.Bot work?', a: 'Answer a series of simple questions about your business, how you got started, and your goals. StoryCreator.Bot transforms your answers into a series of ready-to-publish posts and content ideas, all based on your unique story.' },
    { q: 'How long does it take?', a: "Most businesses complete their StoryCreator.Bot conversation in under 30 minutes. You can answer the questions by typing or simply dictating your responses using your phone or computer's microphone. From that one conversation, you'll have months of authentic, ready-to-publish content." },
    { q: "What if I don't think I have a story to tell?", a: "Every business owner has a story. Whether it's why you started, the people you serve, the lessons you've learned, or the values that guide your work, StoryCreator.Bot helps uncover the moments customers connect with and remember. Your candor is more important than your performance." },
    { q: 'Do I need to be a good writer?', a: 'Not at all. StoryCreator.Bot does the writing for you. You simply share your professional background, your business, and anything you think might be meaningful to potential customers. StoryCreator.Bot does the rest.' },
    { q: 'Will my content sound like AI?', a: 'No. StoryCreator.Bot is designed to write from your story, your experiences, and your voice, creating content that sounds authentic to your business, not generic AI.' },
    { q: 'Do I need to provide websites or social media links?', a: 'No. But you\'ll get the best results by providing as much information as you have. Websites, social media profiles, published articles, news stories, biographies, videos, and other online content help StoryCreator.Bot better understand your business and create richer, more authentic content. None of it is required, but every bit of context helps.' },
    { q: "Can I edit the content after it's generated?", a: 'Yes. Every post is fully editable. You can refine the content, adjust the tone, or even update your original answers before or after publishing. Revision credits are always available whenever you need to make changes.' },
    { q: 'Do my credits expire?', a: 'Never. Credits for rewrites, refinements, and special promotions remain in your account until you use them. There are no monthly fees and no expiration dates.' },
    { q: "What if I want to create more content after I've used my credits?", a: 'You can purchase additional content credits at any time. There are no subscriptions. You simply pay for what you need, and your credits never expire.' },
    { q: "Can't I just use my own AI to make posts, instead of StoryCreator.Bot?", a: 'You certainly can. But general-purpose AI only knows what you tell it in the moment. StoryCreator.Bot begins with your story, your experience, your values, and what makes your business unique. It then creates authentic, ready-to-publish content designed to help customers get to know your business, build familiarity, and earn trust, without having to reinvent every prompt.' },
    { q: 'Is my information kept private?', a: 'Yes. The information you share is used only to create content for your business. It is never published without your approval.' },
    { q: 'Do I still need a Social Media Manager?', a: "That's entirely up to you. StoryCreator.Bot is designed to solve one of the hardest parts of social media marketing: consistently creating authentic content. A good Social Media Manager can still add tremendous value by selecting visuals, scheduling posts, managing campaigns, and analyzing results. StoryCreator.Bot simply gives them better content to work with." },
];

const priceDollars = (pack) => Math.round(pack.price / 100);

const popularSlugIn = (list) => {
    if (!list?.length) return null;
    const sorted = [...list].sort((a, b) => a.price - b.price);
    return sorted[Math.floor(sorted.length / 2)]?.slug ?? null;
};

const isAddon = (pack) => pack.type === 'addon';

const tierOf = (pack) => {
    const label = pack.label.toLowerCase();
    if (label.includes('professional')) return 'professional';
    if (label.includes('premium')) return 'premium';
    return 'basic';
};

const tierContent = {
    basic: {
        blurb: 'A solid starting point for businesses ready to tell their story.',
        episodes: '12 episodes per story',
        posts: '12 posts, about 6 months of content at 2 posts/month',
    },
    premium: {
        blurb: 'More flexibility for businesses building a consistent content presence.',
        episodes: '12 or 18 episodes per story, you choose',
        posts: 'Up to 18 posts, up to 9 months of content at 2 posts/month',
    },
    professional: {
        blurb: 'Full creative range for businesses running multiple stories at once.',
        episodes: '12, 18, or 24 episodes per story, you choose',
        posts: 'Up to 24 posts, up to 12 months of content at 2 posts/month',
    },
};

// Verified Business Partner renewal plans use different framing than the
// public pay-to-play packs, even though episode counts/credits line up 1:1.
const partnerTierContent = {
    basic: {
        blurb: 'THE BASIC PLAN is what you receive free when you sign up or resubscribe as a Verified Business Partner. Basic Plan StoryBot credits can also be purchased to enhance story and episodic customization.',
        episodes: '12 episodes per story',
        posts: '12 posts, about 6 months of content at 2 posts/month',
    },
    premium: {
        blurb: 'THE PREMIUM PLAN provides 50% more episodes and expanded opportunity for customization. Premium Plan StoryBot credits can be applied to existing episodes or to create new, additional episodes.',
        episodes: '12 or 18 episodes per story, you choose',
        posts: 'Up to 18 posts, up to 9 months of content at 2 posts/month',
    },
    professional: {
        blurb: "THE PROFESSIONAL PLAN is the most flexible option. You get a year's worth of authentic content with ample opportunity to customize your story either all at once or editing individual episodes.",
        episodes: '12, 18, or 24 episodes per story, you choose',
        posts: 'Up to 24 posts, up to 12 months of content at 2 posts/month',
    },
};

// Partner packs carry the "(Verified Business Partner)" designation, but some
// labels already include it. Strip any existing occurrence so it appears once.
const partnerPlanTitle = (pack) => {
    const base = pack.label.replace(/\s*\(Verified Business Partner\)/gi, '').trim();
    return pack.type === 'partner' ? `${base} (Verified Business Partner)` : base;
};

const packBlurb = (pack) => {
    if (isAddon(pack)) return 'Wanna make changes but out of credits? Top up your credits anytime.';
    return pack.type === 'partner' ? partnerTierContent[tierOf(pack)].blurb : tierContent[tierOf(pack)].blurb;
};

const packEpisodes = (pack) =>
    pack.type === 'partner' ? partnerTierContent[tierOf(pack)].episodes : tierContent[tierOf(pack)].episodes;

const packPosts = (pack) =>
    pack.type === 'partner' ? partnerTierContent[tierOf(pack)].posts : tierContent[tierOf(pack)].posts;

// Verified Business Partner renewal plans and public Pay to Play packs are
// presented as two distinct pricing programs, each with its own cards.
const partnerPacks = computed(() =>
    [...(props.packs ?? [])].filter((p) => p.type === 'partner').sort((a, b) => a.price - b.price)
);
const payToPlayPacks = computed(() =>
    [...(props.packs ?? [])]
        .filter((p) => p.type !== 'partner')
        .sort((a, b) => (isAddon(a) === isAddon(b) ? a.price - b.price : isAddon(a) ? 1 : -1))
);
const payToPlayPopularSlug = computed(() => popularSlugIn(payToPlayPacks.value.filter((p) => !isAddon(p))));

const partnerFeatures = ['12 complimentary episodes', 'Saves time and lowers costs', 'Story credits never expire', 'Verified partner badge', 'Priority guidance', 'Episodes for every story'];
const payToPlayFeatures = ['Low monthly fees', 'Hands-on onboarding', 'Customizable output', 'Limited commitment', 'Tech support', 'Your story in episodes'];
</script>

<template>
    <Head title="StoryCreator.Bot — Your Story is Your Business" />

    <div class="min-h-screen flex flex-col" style="background: radial-gradient(ellipse at 50% 40%, #FEF9EC 0%, #F5F5F0 60%, #EFEFEA 100%);">

        <AnnouncementBar />

        <!-- Nav -->
        <header class="bg-white flex items-center justify-between px-6 md:px-8 py-4">
            <a href="/" class="flex items-center text-xl font-bold tracking-tight">
                <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
                <span style="color: #1A1A1A;">.Bot</span>
            </a>

            <nav class="flex items-center gap-3">
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
                            v-if="canRegister"
                            :href="route('register')"
                            class="px-4 py-2 text-sm font-semibold transition hover:opacity-70"
                            style="color: #1A1A1A;"
                        >
                            Sign Up
                        </Link>
                        <Link
                            :href="route('login')"
                            class="px-5 py-2 rounded-lg text-sm font-bold transition hover:opacity-90"
                            style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                        >
                            Log In
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
                Social Media Powered by Our Intelligence.
            </div>

            <!-- Headline -->
            <h1 class="text-5xl md:text-6xl font-black leading-tight max-w-3xl mb-4" style="color: #1A1A1A;">
                Your Story is Your<br />
                <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Business</span>
            </h1>

            <!-- Tagline -->
            <p class="text-lg font-bold max-w-xl mb-6" style="color: #1A1A1A;">
                Technology Changes. Human Nature Doesn't.
            </p>

            <!-- Subheading -->
            <p class="max-w-3xl text-lg leading-relaxed mb-4" style="color: #555555;">
                Word of mouth, always counted on by local service businesses to sustain and
                grow, now moves at the speed of digital on social media. A recommendation that
                once traveled one conversation at a time now reaches thousands of neighbors in
                real time, not over time. At the same time, stars, reviews, and rankings are now
                bought and sold, so they can't be trusted.
            </p>
            <p class="max-w-3xl text-lg leading-relaxed mb-4" style="color: #555555;">
                In this new environment customers still choose the professionals they get to know
                and trust. That's why your own story has become the most important distinction. No
                two companies, even in the same trade, are built on the same values and principles.
                Yours are as unique as your thumbprint, and sharing authenticity is what engages
                customers in the social sphere.
            </p>
            <p class="max-w-3xl text-lg leading-relaxed mb-4" style="color: #555555;">
                Most owners have come to understand this, but the problem is now a practical one.
                Approved content, effective or not, is a huge monthly expense, or requires time and
                attention you don't have.
            </p>
            <p class="max-w-3xl text-lg leading-relaxed font-bold mb-10" style="color: #1A1A1A;">
                We've built <strong>StoryCreator.Bot</strong> to fix both with one 30-minute
                conversation. You dictate honest answers to simple business questions and
                immediately receive ready-to-publish content in your voice, whether you're new and
                building trust or established and protecting the great reputation you spent years
                earning.
            </p>

            <!-- CTAs -->
            <p class="text-xs font-bold tracking-widest uppercase mb-4" style="color: #555555;">Use StoryCreator.Bot</p>
            <div class="flex flex-wrap items-center justify-center gap-4">
                <Link
                    :href="canRegister ? route('register') : route('login')"
                    class="flex items-center gap-2 px-7 py-3.5 rounded-lg font-bold text-base transition hover:opacity-90"
                    style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                >
                    Sign Up
                    <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                </Link>

                <Link
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
                    Create Content That
                    <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Delivers</span><br />
                    in Three Easy Steps
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">

                    <!-- Step 1 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-5" style="background: linear-gradient(to right, #FFC837, #F5A000);">
                            <MessageSquare class="w-7 h-7" color="#1A1A1A" :stroke-width="2" />
                        </div>
                        <p class="text-xs font-bold tracking-widest uppercase mb-2" style="color: #555555;">Step 1</p>
                        <h3 class="text-lg font-bold mb-3" style="color: #1A1A1A;">Answer a Few Questions</h3>
                        <p class="text-sm leading-relaxed" style="color: #555555;">Share your work history, insights about how you got here, what gets you up in the morning and what you're proud of.</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-5" style="background: linear-gradient(to right, #FFC837, #F5A000);">
                            <Sparkles class="w-7 h-7" color="#1A1A1A" :stroke-width="2" />
                        </div>
                        <p class="text-xs font-bold tracking-widest uppercase mb-2" style="color: #555555;">Step 2</p>
                        <h3 class="text-lg font-bold mb-3" style="color: #1A1A1A;">StoryCreator Works its Magic</h3>
                        <p class="text-sm leading-relaxed" style="color: #555555;">Our engineered story engine turns your simple answers into authentic sounding episodic content.</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-5" style="background: linear-gradient(to right, #FFC837, #F5A000);">
                            <Download class="w-7 h-7" color="#1A1A1A" :stroke-width="2" />
                        </div>
                        <p class="text-xs font-bold tracking-widest uppercase mb-2" style="color: #555555;">Step 3</p>
                        <h3 class="text-lg font-bold mb-3" style="color: #1A1A1A;">Get Episodes of Your Story</h3>
                        <p class="text-sm leading-relaxed" style="color: #555555;">Review, refine, and use each episode of your story on your Best of Local network and across all social media, blogs, and more.</p>
                    </div>

                </div>
            </div>
        </section>

        <!-- Pricing -->
        <section class="flex flex-col justify-center px-6 py-20" style="background-color: #FAFAF8;">
            <div class="max-w-5xl mx-auto w-full">

                <!-- Header -->
                <div class="text-center mb-10">
                    <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color: #555555;">Pricing</p>
                    <h2 class="text-4xl md:text-5xl font-black mb-3" style="color: #1A1A1A;">Simple, Transparent Pricing</h2>
                    <p class="text-sm" style="color: #555555;">Partners get six months free, or buy a story package when you need one. No subscription — credits never expire.</p>
                </div>

                <!-- ═══ Verified Business Partners Pricing Plans ═══ -->

                <!-- Partner Banner -->
                <div class="rounded-2xl p-6 mb-6 flex flex-col md:flex-row md:items-center gap-6" style="background-color: #1A1A1A;">
                    <!-- Left: logo + info -->
                    <div class="flex items-start gap-4 flex-1">
                        <div class="relative shrink-0">
                            <div class="w-14 h-14 rounded-xl flex items-center justify-center text-white font-black text-xs text-center leading-tight" style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;">
                                BEST<br/>LOCAL
                            </div>
                        </div>
                        <div>
                            <span class="inline-block text-xs font-bold tracking-widest uppercase px-2 py-0.5 rounded mb-1" style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;">StoryCreator.Bot Partnership Program</span>
                            <h3 class="text-2xl font-black text-white">Verified Business Partners Pricing Plans</h3>
                            <p class="text-sm mb-1" style="color: #888888;">Six months
                                <span class="font-bold uppercase" style="color: #F5A000;">free content</span> for
                                <span class="font-bold text-xl" style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Verified Local Businesses</span>
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-8 gap-y-1 mt-3">
                                <span v-for="f in partnerFeatures"
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

                <p class="text-sm font-bold tracking-wide uppercase mb-4" style="color: #2BBDA8;">Renew, Refresh or Get More From Your Plan After Your Free Start-Up Package</p>

                <!-- Partner Packs -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-14">
                    <div
                        v-for="pack in partnerPacks"
                        :key="pack.slug"
                        class="relative rounded-2xl bg-white p-6 flex flex-col"
                        style="border: 2px solid #F5A000;"
                    >
                        <h3 class="text-base font-bold mb-3 min-h-[3rem]" style="color: #1A1A1A;">{{ partnerPlanTitle(pack) }}</h3>

                        <div class="flex items-baseline gap-1 mb-1">
                            <span class="text-4xl font-black" style="color: #1A1A1A;">${{ priceDollars(pack) }}</span>
                            <span class="text-sm" style="color: #555555;">one-time</span>
                        </div>
                        <p class="text-xs italic mb-5 min-h-[2.5rem]" style="color: #555555;">{{ packBlurb(pack) }}</p>

                        <button
                            type="button"
                            @click="learnMorePack = pack"
                            class="flex items-center justify-center gap-2 w-full py-2.5 rounded-lg font-bold text-sm mb-3 transition hover:bg-amber-50 cursor-pointer"
                            style="border: 2px solid #F5A000; color: #1A1A1A;"
                        >
                            Learn more
                        </button>

                        <Link
                            :href="route('partner')"
                            class="flex items-center justify-center gap-2 w-full py-2.5 rounded-lg font-bold text-sm transition hover:opacity-90"
                            style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                        >
                            Become a Verified Business Partner <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </Link>
                    </div>
                </div>

                <!-- Learn more popup — shared by all pricing cards -->
                <Dialog :open="learnMorePack !== null" @update:open="val => { if (!val) learnMorePack = null; }">
                    <DialogContent v-if="learnMorePack" class="max-w-md">
                        <DialogHeader>
                            <DialogTitle>{{ partnerPlanTitle(learnMorePack) }} — ${{ priceDollars(learnMorePack) }} one-time</DialogTitle>
                            <DialogDescription as="div" class="text-[#555555]">{{ packBlurb(learnMorePack) }}</DialogDescription>
                        </DialogHeader>

                        <!-- Standard pack details -->
                        <div v-if="!isAddon(learnMorePack)" class="flex flex-col gap-5">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide mb-2" style="color: #F5A000;">What you get</p>
                                <p class="text-sm font-bold" style="color: #1A1A1A;">{{ packEpisodes(learnMorePack) }}</p>
                                <p class="text-xs italic mt-0.5" style="color: #555555;">Each episode = 1 ready-to-post piece of content</p>
                                <p class="text-xs mt-1" style="color: #888888;">{{ packPosts(learnMorePack) }}</p>
                            </div>

                            <div class="border-t pt-4" style="border-color: #EEEEEE;">
                                <p class="text-xs font-bold uppercase tracking-wide mb-2" style="color: #F5A000;">Your credit balance</p>
                                <ul class="flex flex-col gap-1.5 text-sm" style="color: #555555;">
                                    <li><span class="font-semibold" style="color: #1A1A1A;">Total StoryBot Credits:</span> {{ learnMorePack.credits }}</li>
                                    <li><span class="font-semibold" style="color: #1A1A1A;">Cost to generate 1 episode:</span> 1 credit</li>
                                    <li><span class="font-semibold" style="color: #1A1A1A;">Cost to manually edit or redo 1 episode:</span> 1 credit</li>
                                </ul>
                            </div>

                            <div class="border-t pt-4" style="border-color: #EEEEEE;">
                                <p class="text-xs font-bold uppercase tracking-wide mb-2" style="color: #F5A000;">Good to know</p>
                                <ul class="flex flex-col gap-2 text-sm" style="color: #555555;">
                                    <li class="flex items-start gap-2"><Check class="w-4 h-4 shrink-0 mt-0.5" style="color: #F5A000;" :stroke-width="2.5" /> Manual edit or redo any episode for 1 credit. No extra fees.</li>
                                    <li class="flex items-start gap-2"><Check class="w-4 h-4 shrink-0 mt-0.5" style="color: #F5A000;" :stroke-width="2.5" /> Unused credits never expire. They carry forward.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Add-on details -->
                        <div v-else class="flex flex-col gap-5">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide mb-2" style="color: #F5A000;">What you get</p>
                                <ul class="flex flex-col gap-1.5 text-sm" style="color: #555555;">
                                    <li><span class="font-semibold" style="color: #1A1A1A;">Credits added to your account:</span> {{ learnMorePack.credits }} StoryBot Credits</li>
                                    <li><span class="font-semibold" style="color: #1A1A1A;">Enough to generate or refine:</span> up to {{ learnMorePack.credits }} episodes</li>
                                </ul>
                            </div>

                            <div class="border-t pt-4" style="border-color: #EEEEEE;">
                                <p class="text-xs font-bold uppercase tracking-wide mb-2" style="color: #F5A000;">Good to know</p>
                                <ul class="flex flex-col gap-2 text-sm" style="color: #555555;">
                                    <li class="flex items-start gap-2"><Check class="w-4 h-4 shrink-0 mt-0.5" style="color: #F5A000;" :stroke-width="2.5" /> Add-on only. Must have an active plan to purchase.</li>
                                    <li class="flex items-start gap-2"><Check class="w-4 h-4 shrink-0 mt-0.5" style="color: #F5A000;" :stroke-width="2.5" /> Credits never expire. Use them whenever you need.</li>
                                </ul>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>

                <!-- ═══ Pay to Play StoryCreator.Bot Pricing Options ═══ -->

                <!-- Pay to Play Banner -->
                <div id="pay-to-play" class="rounded-2xl p-6 mb-6 scroll-mt-24" style="background-color: #1A1A1A;">
                    <span class="inline-block text-xs font-bold tracking-widest uppercase px-2 py-0.5 rounded mb-1" style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;">Ala Carte Payment Programs</span>
                    <h3 class="text-2xl font-black text-white">Pay to Play StoryCreator.Bot Pricing Options</h3>
                    <p class="text-sm mb-1" style="color: #888888;">Flexible
                        <span class="font-bold" style="color: #F5A000;">content plans</span> for general social media use. You must be Verified to post on Best of Local.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-8 gap-y-1 mt-3">
                        <span v-for="f in payToPlayFeatures"
                            :key="f" class="flex items-center gap-1.5 text-xs" style="color: #AAAAAA;">
                            <Check class="w-3 h-3 shrink-0" style="color: #F5A000;" :stroke-width="3" />
                            {{ f }}
                        </span>
                    </div>
                </div>

                <p class="text-sm font-bold tracking-wide uppercase mb-4" style="color: #1BDEAB;">Pay to Play Pricing Plans (For Non-Verified Business Partners Only)</p>

                <!-- Pay to Play Packs -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="pack in payToPlayPacks"
                        :key="pack.slug"
                        class="relative rounded-2xl bg-white p-6 flex flex-col"
                        style="border: 2px solid #F5A000;"
                    >
                        <!-- Most Popular badge -->
                        <div v-if="pack.slug === payToPlayPopularSlug" class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                            <span class="px-3 py-1 rounded-full text-xs font-bold" style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;">Most Popular</span>
                        </div>

                        <h3 class="text-base font-bold mb-3 min-h-[3rem]" style="color: #1A1A1A;">{{ pack.label }}</h3>

                        <div class="flex items-baseline gap-1 mb-1">
                            <span class="text-4xl font-black" style="color: #1A1A1A;">${{ priceDollars(pack) }}</span>
                            <span class="text-sm" style="color: #555555;">one-time</span>
                        </div>
                        <p class="text-xs italic mb-5 min-h-[2.5rem]" style="color: #555555;">{{ packBlurb(pack) }}</p>

                        <button
                            type="button"
                            @click="learnMorePack = pack"
                            class="flex items-center justify-center gap-2 w-full py-2.5 rounded-lg font-bold text-sm mb-3 transition hover:bg-amber-50 cursor-pointer"
                            style="border: 2px solid #F5A000; color: #1A1A1A;"
                        >
                            Learn more
                        </button>

                        <Link
                            :href="route('register')"
                            class="flex items-center justify-center gap-2 w-full py-2.5 rounded-lg font-bold text-sm transition hover:opacity-90"
                            style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                        >
                            Sign Up <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </Link>
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

        <!-- Closing CTA -->
        <section class="bg-white flex flex-col items-center justify-center text-center px-6 py-16" style="border-top: 1px solid #DDDDDD;">
            <h2 class="text-3xl md:text-4xl font-black mb-6" style="color: #1A1A1A;">Ready? Let's Get Your Story.</h2>
            <Link
                :href="canRegister ? route('register') : route('login')"
                class="flex items-center gap-2 px-7 py-3.5 rounded-lg font-bold text-base transition hover:opacity-90"
                style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
            >
                Sign Up
                <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
            </Link>
        </section>

        <Footer />

    </div>
</template>
