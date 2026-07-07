<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Check, ArrowRight, Star, Building2, Users, Zap, ShieldCheck, Calendar, TrendingUp, Radio, Award, Mail, Phone } from '@lucide/vue';

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

const stats = [
    { value: '75K+', label: 'Facebook Group Members' },
    { value: '22K+', label: 'Instagram Followers' },
    { value: '21K+', label: 'App Downloads' },
    { value: '1.5M', label: 'Total Monthly Platform Views' },
];

const differentiators = [
    {
        icon: Radio,
        title: 'Less noise. More Signals.',
        body: 'The fastest growing, least cluttered, and most trusted local marketing platform in South Florida. No anonymous accounts, no free advertising, no random content clutter. More engagement means more transactions.',
    },
    {
        icon: TrendingUp,
        title: 'Reach beyond your echo chamber.',
        body: 'Posting only to your own followers is old school. Real collaboration means getting visibility on dynamic platforms, significantly extending your reach beyond the people who already know you.',
    },
    {
        icon: ShieldCheck,
        title: 'Our Trust Network.',
        body: "Unlike crowded marketplaces like Angi, Google, and Yelp where a search triggers a flood of responses, we limit each category to ten businesses. You are identified as one of a vetted few in a trustworthy local community. In a community of 75,000 fellow locals, only Verified Business Partners post under a company handle — every comment and engagement naturally inserts your business into the conversation.",
    },
    {
        icon: Award,
        title: 'Old school ranking.',
        body: 'Ranking first on Google once meant something. Today that position belongs to whoever paid for it, making organic placement a largely meaningless vestige of an earlier internet. The trust ecosystem has moved. The platforms most businesses are still paying for have not moved with it.',
    },
];

const partnerBenefits = [
    'Premium placement on the Best of app and network',
    'Enhanced business profile on the Best of app',
    'Lead generation — service requests delivered privately before posting to the group',
    'Category exclusivity — ten businesses maximum per category',
    'Using our StoryCreator.Bot takes minutes to create authentic episodic content in your own voice',
    'Reach beyond your echo chamber — 75,000 fellow locals, most of them new to you',
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

        <!-- Hero — Choose Your Marketing Approach -->
        <section class="px-6 md:px-10 py-20 md:py-28 text-center max-w-3xl mx-auto">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest mb-6" style="background-color: #FEF3D0; color: #B87800;">
                Partner Program
            </div>
            <h1 class="text-4xl md:text-5xl font-black leading-tight mb-8" style="color: #1A1A1A;">
                Choose Your <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Marketing Approach</span>
            </h1>
            <p class="text-base leading-relaxed mb-4" style="color: #555555;">
                We suggest becoming a Verified Business Partner so you can take advantage of all the exclusive marketing benefits inside our ecosystem, including <strong style="color: #1A1A1A;">StoryCreator.Bot</strong>.
            </p>
            <p class="text-base leading-relaxed mb-6" style="color: #555555;">
                However, you can also get a <strong style="color: #1A1A1A;">StoryCreator.Bot</strong> plan on its own, creating content you can promote anywhere other than Best of Delray Beach or other Best of locations.
            </p>
            <p class="text-base font-bold leading-relaxed mb-8" style="color: #1A1A1A;">
                Either way, your story does the marketing work, while you actually work for your business.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-4">
                <Link
                    :href="route('register')"
                    class="inline-flex items-center gap-2 px-8 py-4 rounded-lg font-bold text-base transition hover:opacity-90"
                    style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                >
                    Become a Verified Business Partner <ArrowRight class="w-5 h-5" :stroke-width="2.5" />
                </Link>
                <a
                    :href="route('welcome') + '#pay-to-play'"
                    class="inline-flex items-center gap-2 px-8 py-4 rounded-lg font-bold text-base border transition hover:bg-gray-50"
                    style="background-color: #FFFFFF; color: #1A1A1A; border-color: #DDDDDD;"
                >
                    Get a StoryCreator.Bot Plan <ArrowRight class="w-5 h-5" :stroke-width="2.5" />
                </a>
            </div>
            <p class="text-xs mt-4" style="color: #888888;">Free for 6 months · no credit card · 2–3 business day review.</p>
        </section>

        <!-- Perks -->
        <section class="px-6 md:px-10 py-16" style="background-color: #FFFFFF; border-top: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD;">
            <div class="max-w-5xl mx-auto">
                <p class="text-xs font-bold tracking-widest uppercase text-center mb-3" style="color: #555555;">What You Get</p>
                <h2 class="text-2xl md:text-3xl font-black text-center mb-12" style="color: #1A1A1A;">Everything included in the Verified Business Partner plan</h2>
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

        <!-- The Business Marketing Channel -->
        <section class="px-6 md:px-10 py-16" style="background-color: #FFFFFF; border-bottom: 1px solid #DDDDDD;">
            <div class="max-w-3xl mx-auto text-center">
                <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color: #F5A000;">Hyperlocal. Authentic. Trusted.</p>
                <h2 class="text-2xl md:text-3xl font-black mb-2" style="color: #1A1A1A;">The Business Marketing Channel</h2>
                <p class="text-lg font-bold mb-8" style="color: #555555;">Trusted by Locals.</p>
                <p class="text-base leading-relaxed mb-6" style="color: #555555;">
                    When you really think about it, every business, every practice, every tradesman, and every professional in your field uses the same tools, provides the same services, and essentially has the same credentials. It's your story that is totally unique.
                </p>
                <p class="text-base leading-relaxed mb-6" style="color: #555555;">
                    Word of mouth always carried that story. Digital social media is changing how that story travels, who hears it, and how fast. What has never changed is that people choose who they know and trust. Best of Delray Beach was built to make sure they know and trust you.
                </p>
                <p class="text-base leading-relaxed" style="color: #555555;">
                    Best of Delray Beach was built for this moment. A platform that encourages, supports, and highlights the stories that reveal character, engender trust, and demonstrate good faith. The story that makes someone say, that's who I'm calling.
                </p>
            </div>
        </section>

        <!-- The landscape has changed -->
        <section class="px-6 md:px-10 py-16" style="background-color: #FAFAF8;">
            <div class="max-w-3xl mx-auto">
                <p class="text-xs font-bold tracking-widest uppercase text-center mb-3" style="color: #555555;">The Landscape Has Changed</p>
                <p class="text-base leading-relaxed text-center" style="color: #555555;">
                    A generation after digital transformed how we buy things, it has come home, literally, to roost. How local service providers are hired, including the caterer, the chiropractor, the lawyer, the roofer, the events planner, the auto detailer, has changed. Small business owners who discount social media recommendations for traditional word of mouth do so at their own risk.
                </p>
            </div>
        </section>

        <!-- Trust network + stats -->
        <section class="px-6 md:px-10 py-16" style="background-color: #1A1A1A;">
            <div class="max-w-4xl mx-auto text-center">
                <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color: #888888;">A Trust Network Built for This Moment</p>
                <p class="text-base leading-relaxed mb-12" style="color: #CCCCCC;">
                    Whether you're the top name in the trade or a new entry, Best of Delray Beach puts you in front of the people who matter most: 75,000 engaged locals, most of them new to you, who actively use our platforms to discover and refer businesses they trust. As a Verified Business Partner you post under your company handle, receive service requests privately before they hit the group, and use StoryCreator.Bot to produce authentic episodic content in your own voice, working alongside a social media team's strategy or entirely on your own. In a community this engaged and this intimate, your character is your most powerful marketing tool. Category exclusivity caps at ten businesses per category.
                </p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div v-for="stat in stats" :key="stat.label">
                        <p class="text-3xl md:text-4xl font-black mb-1" style="color: #F5A000;">{{ stat.value }}</p>
                        <p class="text-xs uppercase tracking-wide" style="color: #888888;">{{ stat.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Built to stay ahead -->
        <section class="px-6 md:px-10 py-16" style="background-color: #FFFFFF; border-bottom: 1px solid #DDDDDD;">
            <div class="max-w-3xl mx-auto">
                <p class="text-xs font-bold tracking-widest uppercase text-center mb-3" style="color: #555555;">Built to Stay Ahead</p>
                <p class="text-base leading-relaxed text-center" style="color: #555555;">
                    Best of Delray Beach was designed and curated to give every business a place to tell its own unique story. No overwhelming noise, like free Facebook Groups. No auctions where your ad spend funds your competitor's placement, think Google and Facebook Ads. No content you would never choose to be associated with, that defines Nextdoor. No leads shared with a hundred other paying subscribers, for example Angi and Thumbtack. What we have built instead is an intimate, highly engaged hyperlocal community where your voice is protected, your presence is curated, and the people listening are already looking.
                </p>
            </div>
        </section>

        <!-- What makes this different -->
        <section class="px-6 md:px-10 py-16" style="background-color: #FAFAF8;">
            <div class="max-w-5xl mx-auto">
                <p class="text-xs font-bold tracking-widest uppercase text-center mb-3" style="color: #F5A000;">Hyperlocal. Authentic. Trusted.</p>
                <h2 class="text-2xl md:text-3xl font-black text-center mb-2" style="color: #1A1A1A;">What makes this different</h2>
                <p class="text-sm text-center mb-12" style="color: #555555;">Clutter and quantity vs. authentic reach that matters</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div
                        v-for="item in differentiators"
                        :key="item.title"
                        class="rounded-xl p-6 bg-white"
                        style="border: 1px solid #DDDDDD;"
                    >
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-4" style="background-color: #FEF3D0;">
                            <component :is="item.icon" class="w-5 h-5" style="color: #F5A000;" :stroke-width="2" />
                        </div>
                        <h3 class="font-bold text-sm mb-1.5" style="color: #1A1A1A;">{{ item.title }}</h3>
                        <p class="text-sm leading-relaxed" style="color: #555555;">{{ item.body }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Verified Business Partner Benefits -->
        <section class="px-6 md:px-10 py-16" style="background-color: #FFFFFF; border-top: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD;">
            <div class="max-w-3xl mx-auto">
                <h2 class="text-2xl md:text-3xl font-black text-center mb-10" style="color: #1A1A1A;">Verified Business Partner Benefits</h2>
                <ul class="flex flex-col gap-3">
                    <li
                        v-for="benefit in partnerBenefits"
                        :key="benefit"
                        class="flex items-start gap-3 rounded-xl px-5 py-4"
                        style="border: 1px solid #DDDDDD; background-color: #FAFAF8;"
                    >
                        <Check class="w-4 h-4 shrink-0 mt-0.5" style="color: #F5A000;" :stroke-width="3" />
                        <span class="text-sm leading-relaxed" style="color: #555555;">{{ benefit }}</span>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="px-6 md:px-10 py-16" style="background-color: #1A1A1A;">
            <div class="max-w-2xl mx-auto text-center">
                <p class="text-base leading-relaxed mb-2" style="color: #CCCCCC;">Great value. Limited supply. Categories are filling. Let us talk before yours closes.</p>
                <h2 class="text-xl md:text-2xl font-black mb-8 text-white">Let's Discuss Your Plans Before Your Competition Does</h2>
                <Link
                    :href="route('register')"
                    class="inline-flex items-center gap-2 px-8 py-4 rounded-lg font-bold text-base transition hover:opacity-90 mb-8"
                    style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                >
                    Apply Now <ArrowRight class="w-5 h-5" :stroke-width="2.5" />
                </Link>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-x-6 gap-y-2 text-sm" style="color: #888888;">
                    <a href="https://www.facebook.com/groups/bestofdelraybeach" target="_blank" rel="noopener" class="underline hover:opacity-80">
                        facebook.com/groups/bestofdelraybeach
                    </a>
                    <a href="mailto:info@bestofdelraybeach.com" class="flex items-center gap-1.5 underline hover:opacity-80">
                        <Mail class="w-3.5 h-3.5" /> info@bestofdelraybeach.com
                    </a>
                    <a href="tel:5614502121" class="flex items-center gap-1.5 underline hover:opacity-80">
                        <Phone class="w-3.5 h-3.5" /> 561.450.2121
                    </a>
                    <a href="https://bestoflocal.app/delray" target="_blank" rel="noopener" class="underline hover:opacity-80">
                        bestoflocal.app/delray
                    </a>
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
