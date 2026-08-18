<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { MessageSquare, Sparkles, Download, Zap, ArrowRight, Play, Check, CircleHelp, ChevronDown } from '@lucide/vue';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription,
} from '@/Components/ui/dialog';
import AnnouncementBar from '@/Components/AnnouncementBar.vue';
import Footer from '@/Components/Footer.vue';
import PartnerApplyDialog from '@/Components/PartnerApplyDialog.vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    packs: Array,
});

const openFaq = ref(null);
const learnMorePack = ref(null);

// FAQ reveals 4 at a time; "See more" loads the next 4 until all are shown.
const faqVisible = ref(4);
const visibleFaqs = computed(() => faqs.slice(0, faqVisible.value));
const showMoreFaqs = () => { faqVisible.value += 4; };

// "Why this matters" cards clamp their body to 2 lines until expanded.
const whyCards = [
    { title: 'Starting out', bg: '#FFF8EC', body: "Let's face it. There are likely many companies out there offering the exact same services as your new company. Being new is always scary, but the gap has never been easier to close. Consumers now search and discover who they want to work with on the same established platforms, whether a company opened last month or thirty years ago. That opportunity, to stand next to an established competitor and look just as trustworthy, has never been better than it is right now with social media. It comes down to your story. It is the story that portrays your character, and it is your story that engenders trust." },
    { title: 'Holding ground', bg: '#FDEFD6', body: "The big outfits have the relationships and the gravitational pull that comes from scale. The upstarts move fast, work for less, and take risks you learned long ago not to take. Squeezed from both sides, you need something scale cannot buy and hustle cannot fake. The same platforms giving new businesses their opening are giving your established competitors a louder microphone too. Standing still is how ground gets lost. It comes down to your story, the one that already earned trust once and now has to be told loud enough to continue to earn it." },
    { title: 'Staying on top', bg: '#FBE0A8', body: "You built your market share by holding to high standards, delivering on them, and adapting when the ground shifted under you. That is what got you here, when word of mouth reliably turned into growth. Now word of mouth has moved to digital platforms, where a startup or a company a fraction your size can look just as credible as you. Anonymous search engine recommendations are losing trust, and the algorithms replacing them do not care about your tenure. You are not losing customers. You are losing the advantage that used to be free with your legacy. It comes down to your story, the trust you spent years earning, told with the same reach and precision your newest competitors already have, but with the wisdom that only comes from experience." },
];
const expandedCards = ref([false, false, false]);
const toggleCard = (i) => { expandedCards.value[i] = !expandedCards.value[i]; };

// Pay to Play pricing is hidden for now per stakeholder feedback.
const showPayToPlay = false;

// Signup is closed to non-partners for now: every Sign Up entry point opens the
// partner application dialog instead of routing to /register.
const signUpOpen = ref(false);

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

const partnerFeatures = ['enough episode up to year', 'Saves time and lowers costs', 'Story credits never expire', 'Verified partner badge', 'Priority guidance', 'Episodes for every story'];
const payToPlayFeatures = ['Low monthly fees', 'Hands-on onboarding', 'Customizable output', 'Limited commitment', 'Tech support', 'Your story in episodes'];
</script>

<template>
    <Head title="StoryCreator.Bot — Your Story is Your Business" />

    <div class="min-h-screen flex flex-col" style="background: radial-gradient(ellipse at 50% 40%, #FEF9EC 0%, #F5F5F0 60%, #EFEFEA 100%);">

        <AnnouncementBar />

        <!-- Nav -->
        <header class="bg-white flex items-center justify-between px-6 md:px-8 py-2.5">
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
                        <button
                            v-if="canRegister"
                            type="button"
                            @click="signUpOpen = true"
                            class="px-4 py-2 text-sm font-semibold transition hover:opacity-70 cursor-pointer"
                            style="color: #1A1A1A;"
                        >
                            Sign Up
                        </button>
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
            <p class="text-lg font-bold max-w-xl mb-10" style="color: #1A1A1A;">
                Technology Changes. Human Nature Doesn't.
            </p>

            <!-- CTAs -->
            <p class="text-xs font-bold tracking-widest uppercase mb-4" style="color: #555555;">Use StoryCreator.Bot</p>
            <div class="flex flex-wrap items-center justify-center gap-4">
                <Link
                    :href="route('demo')"
                    class="flex items-center gap-2 px-7 py-3.5 rounded-lg font-bold text-base border transition hover:bg-gray-50"
                    style="background-color: #FFFFFF; color: #1A1A1A; border-color: #DDDDDD;"
                >
                    <Play class="w-4 h-4" fill="currentColor" :stroke-width="0" />
                    Try a Live Demo
                </Link>

                <button
                    type="button"
                    @click="signUpOpen = true"
                    class="flex items-center gap-2 px-7 py-3.5 rounded-lg font-bold text-base transition hover:opacity-90 cursor-pointer"
                    style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                >
                    Sign Up
                    <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                </button>
            </div>
        </main>

        <!-- Why This Matters -->
        <section class="px-6 py-20" style="background-color: #FFFFFF;">
            <div class="max-w-6xl mx-auto text-center">

                <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color: #8A8F98;">Because Trust Matters</p>
                <h2 class="text-4xl md:text-5xl font-black mb-16" style="color: #15171C;">
                    Every business has its own
                    <span style="color: #F5A623;">story</span> to tell
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-7 text-left mb-14">
                    <div
                        v-for="(card, i) in whyCards"
                        :key="card.title"
                        class="flex flex-col items-start rounded-2xl p-8 transition-all duration-200 hover:-translate-y-1"
                        :style="{ backgroundColor: card.bg, border: '1px solid #ECEEF1' }"
                    >
                        <h3 class="text-lg font-bold mb-3.5" style="color: #15171C;">{{ card.title }}</h3>
                        <p
                            class="text-[15px] leading-relaxed"
                            style="color: #5B6069;"
                            :class="expandedCards[i] ? '' : 'line-clamp-2'"
                        >
                            {{ card.body }}
                        </p>
                        <button
                            type="button"
                            @click="toggleCard(i)"
                            class="mt-3.5 text-sm font-bold cursor-pointer hover:underline"
                            style="color: #F5A623;"
                        >
                            {{ expandedCards[i] ? 'See less' : 'See more' }}
                        </button>
                    </div>
                </div>

                <div class="rounded-2xl p-9 md:p-10 text-left" style="background: linear-gradient(90deg, #F0951C 0%, #F7C948 100%);">
                    <p class="text-xs font-bold tracking-widest uppercase mb-2.5" style="color: #FFF2DF;">The Tool</p>
                    <p class="text-3xl font-black mb-3.5" style="color: #15171C;">StoryCreator.Bot</p>
                    <p class="text-[15px] leading-relaxed" style="color: #3A2A12;">
                        Whether you are new and building trust, caught in the middle and defending it, or established
                        and protecting a legacy, the answer is the same. Your story is unique. Your story defines your
                        business. Your story is all important. StoryCreator.Bot asks you simple questions in one thirty
                        minute conversation, then turns your answers into content ready to publish, in your own voice,
                        saving you marketing expenditure and, maybe more important, the time you need to devote to
                        running your company, not promoting it.
                    </p>
                </div>

            </div>
        </section>

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

        <!-- Free Content for Verified Business Partners -->
        <section class="flex flex-col justify-center px-6 py-20" style="background-color: #FFFFFF;">
            <div class="max-w-5xl mx-auto w-full">

                <!-- Header -->
                <div class="text-center mb-10">
                    <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color: #555555;">Partner Program</p>
                    <h2 class="text-4xl md:text-5xl font-black" style="color: #1A1A1A;">Free Content for Verified Business Partners</h2>
                </div>

                <!-- Partner Banner -->
                <div class="rounded-2xl p-8 md:p-10 flex flex-col md:flex-row md:items-center gap-8" style="background-color: #1A1A1A;">
                    <!-- Left: logo + info -->
                    <div class="flex items-start gap-5 flex-1">
                        <div class="relative shrink-0">
                            <div class="w-20 h-20 rounded-xl flex items-center justify-center text-white font-black text-sm text-center leading-tight" style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;">
                                BEST<br/>LOCAL
                            </div>
                        </div>
                        <div>
                            <span class="inline-block text-xs font-bold tracking-widest uppercase px-2 py-0.5 rounded mb-1" style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;">StoryCreator.Bot Partnership Program</span>
                            <h3 class="text-3xl md:text-4xl font-black text-white">VBP Pricing Plans</h3>
                            <p class="text-base mb-1" style="color: #888888;">Verified Local Businesses get upto 1 year
                                <span class="font-bold uppercase" style="color: #F5A000;">FREE CONTENT</span>
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-8 gap-y-1.5 mt-4">
                                <span v-for="f in partnerFeatures"
                                    :key="f" class="flex items-center gap-1.5 text-sm" style="color: #AAAAAA;">
                                    <Check class="w-3.5 h-3.5 shrink-0" style="color: #F5A000;" :stroke-width="3" />
                                    {{ f }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Right: CTA -->
                    <div class="flex flex-col items-start md:items-end gap-2 shrink-0">
                        <Link :href="route('login')" class="flex items-center gap-2 px-7 py-3 rounded-lg font-bold text-base transition hover:opacity-90" style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;">
                            Login <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </Link>
                        <Link :href="route('partner')" class="text-sm underline" style="color: #888888;">Learn how to become a verified partner →</Link>
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
                    <p class="text-sm font-bold tracking-wide uppercase" style="color: #2BBDA8;">Renew, Refresh or Get More From Your Plan After Your Free Start-Up Package</p>
                </div>

                <!-- ═══ Verified Business Partners Pricing Plans ═══ -->

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
                <!-- Hidden for now — keep the markup so it's a one-line flip to bring back. -->
                <template v-if="showPayToPlay">

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

                        <button
                            type="button"
                            @click="signUpOpen = true"
                            class="flex items-center justify-center gap-2 w-full py-2.5 rounded-lg font-bold text-sm transition hover:opacity-90 cursor-pointer"
                            style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                        >
                            Sign Up <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </button>
                    </div>
                </div>

                </template>

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
                        v-for="(faq, i) in visibleFaqs"
                        :key="i"
                        :class="i < visibleFaqs.length - 1 ? 'border-b' : ''"
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

                <!-- See more -->
                <div v-if="faqVisible < faqs.length" class="mt-6 text-center">
                    <button
                        type="button"
                        @click="showMoreFaqs"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg font-bold text-sm transition hover:bg-amber-50 cursor-pointer"
                        style="border: 2px solid #F5A000; color: #1A1A1A;"
                    >
                        See more
                        <ChevronDown class="w-4 h-4" :stroke-width="2.5" />
                    </button>
                </div>

            </div>
        </section>

        <!-- Closing CTA -->
        <section class="bg-white flex flex-col items-center justify-center text-center px-6 py-16" style="border-top: 1px solid #DDDDDD;">
            <h2 class="text-3xl md:text-4xl font-black mb-6" style="color: #1A1A1A;">Ready? Let's Get Your Story.</h2>
            <button
                type="button"
                @click="signUpOpen = true"
                class="flex items-center gap-2 px-7 py-3.5 rounded-lg font-bold text-base transition hover:opacity-90 cursor-pointer"
                style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
            >
                Sign Up
                <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
            </button>
        </section>

        <Footer />

        <PartnerApplyDialog v-model:open="signUpOpen" />

    </div>
</template>
