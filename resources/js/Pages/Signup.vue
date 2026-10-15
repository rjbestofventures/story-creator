<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Zap, ArrowRight, Play, MessageSquare, Sparkles, Download, Check, ChevronDown } from '@lucide/vue';
import AnnouncementBar from '@/Components/AnnouncementBar.vue';

const props = defineProps({
    links: { type: Object, default: () => ({}) },
});

const steps = [
    { n: 'STEP 1', title: 'Answer a Few Questions', body: "Share your work history, insights about how you got here, what gets you up in the morning and what you're proud of.", icon: MessageSquare },
    { n: 'STEP 2', title: 'StoryCreator Works its Magic', body: 'Our engineered story engine turns your simple answers into authentic sounding episodic content.', icon: Sparkles },
    { n: 'STEP 3', title: 'Get Episodes of Your Story', body: 'Review, refine, and use each episode of your story across all social media, blogs, and more.', icon: Download },
];

const perks = [
    ['Low monthly fees', 'Limited commitment'],
    ['Hands-on onboarding', 'Tech support'],
    ['Customizable output', 'Your story in chapters'],
];

const tiers = [
    { key: 'silver', name: 'Silver', price: '$180', desc: 'A solid starting point for businesses ready to tell their story.', features: ['12 episode story option', '24 credits'] },
    { key: 'gold', name: 'Gold', price: '$270', desc: 'More flexibility for businesses building a consistent content presence.', features: ['Choose an 18 or 24 episode story option', '36 credits'], popular: true },
];

const faqs = [
    { q: 'How does StoryCreator.Bot work?', a: "It asks you simple questions about your work history, how you got here and what you're proud of. Then it turns your answers into episodes of your story, in your own voice, ready to publish." },
    { q: 'How long does it take?', a: 'One thirty-minute conversation.' },
    { q: "What if I don't think I have a story to tell?", a: "Every business has one. Your story is the character and the trust you've built, and the questions are designed to bring it out." },
    { q: 'Do I need to be a good writer?', a: 'No. You answer simple questions and StoryCreator.Bot does the writing, in your voice.' },
    { q: 'Will my content sound like AI?', a: "It's built to sound like you. The story engine works from your own answers, in your own voice, and you review and refine every episode before you use it." },
    { q: 'Do I need to provide websites or social media links?', a: 'No. Everything starts with your answers in the conversation.' },
    { q: "Can I edit the content after it's generated?", a: 'Yes. Review, refine, and edit each episode of your story before you use it.' },
    { q: 'Do my credits expire?', a: 'Never. Credits for rewrites, refinements, and special promotions remain in your account until you use them. There are no monthly fees and no expiration dates.' },
    { q: "What if I want to create more content after I've used my credits?", a: 'You can purchase additional content credits at any time. There are no subscriptions. You simply pay for what you need, and your credits never expire.' },
    { q: "Can't I just use my own AI to make posts, instead of StoryCreator.Bot?", a: 'A general AI tool starts with a blank page and leaves it to you to know what to ask. StoryCreator.Bot starts with a guided interview, checks that your answers are rich enough, and writes in your voice. That is what keeps your content specific to your business instead of generic.' },
    { q: 'Is my information kept private?', a: 'Yes. What you share is used to create your story and is covered by the StoryCreator.Bot Privacy Policy.' },
    { q: 'Do I still need a Social Media Manager?', a: 'StoryCreator.Bot takes care of the hardest part, your content. Your episodes can be used by anyone who manages your social media. Whether you keep a manager depends on what else you need from them.' },
];

const faqExpanded = ref(false);
const visibleFaqs = computed(() => (faqExpanded.value ? faqs : faqs.slice(0, 3)));
const openFaq = ref(null);
const toggleFaq = (i) => { openFaq.value = openFaq.value === i ? null : i; };

const linkFor = (key) => props.links?.[key] || '#';

const form = useForm({ first_name: '', last_name: '', phone: '', email: '' });
const clientError = ref('');
const submitted = ref(false);

const error = computed(() => clientError.value || Object.values(form.errors)[0] || '');

const onPhone = (e) => { form.phone = e.target.value.replace(/\D/g, '').slice(0, 10); };

const submit = () => {
    const first = form.first_name.trim();
    const last = form.last_name.trim();
    const email = form.email.trim();
    clientError.value = '';
    if (!first || !last) clientError.value = 'Please add your first and last name.';
    else if (!/^\d{10}$/.test(form.phone)) clientError.value = 'Please enter a 10-digit US phone number, digits only.';
    else if (!/.+@.+\..+/.test(email)) clientError.value = 'Please enter a valid email address.';
    if (clientError.value) return;

    form.post(route('trial.signup.submit'), {
        preserveScroll: true,
        onSuccess: () => { submitted.value = true; },
    });
};

const inputClass = 'h-12 px-3.5 text-base rounded-[10px] border border-[#CFCABB] bg-white outline-none focus:border-[#F59E0B] focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-[#1A1A1A] focus-visible:outline-offset-2';
const gradBg = 'bg-gradient-to-br from-[#FFC93C] to-[#F59E0B]';
const gradText = 'bg-gradient-to-br from-[#FFC93C] to-[#F59E0B] bg-clip-text text-transparent';
</script>

<template>
    <Head title="Sign Up" />

    <div class="min-h-screen bg-[#F8F6F0] text-[#1A1A1A]">
        <AnnouncementBar />

        <header class="bg-white flex items-center px-5 sm:px-7 py-2.5">
            <img src="/images/storycreator-logo.png" width="160" height="40" alt="StoryCreator.Bot" class="block w-40 h-10" />
        </header>

        <!-- Hero + signup card -->
        <main class="max-w-[1200px] mx-auto w-full px-5 sm:px-12 flex flex-wrap items-start gap-14 pt-6 pb-[72px]">
            <section class="flex-[1_1_440px] min-w-0 flex flex-col gap-7">
                <div class="self-start flex items-center gap-2 px-4 py-2 rounded-full border border-[#DDD9CE] bg-[#F2F0EA] text-sm text-[#444444]">
                    <Zap class="w-4 h-4" />
                    Social media powered by our intelligence
                </div>
                <h1 class="text-[44px] sm:text-[64px] leading-[1.05] font-extrabold tracking-[-0.02em]">
                    Your Story is Your <span :class="gradText">Business</span>
                </h1>
                <p class="text-xl font-bold">Technology changes. Human nature doesn't.</p>
                <p class="text-lg leading-relaxed text-[#444444] max-w-[520px]">One thirty-minute conversation. StoryCreator.Bot turns your answers into content ready to publish, in your own voice. No membership required.</p>
                <div class="flex flex-wrap gap-3">
                    <a href="#pricing" :class="gradBg" class="flex items-center justify-center gap-2 h-12 px-[22px] rounded-[10px] text-[15px] font-bold text-[#1A1A1A]">
                        See plans <ArrowRight class="w-4 h-4" :stroke-width="2.2" />
                    </a>
                    <a :href="route('demo')" class="flex items-center justify-center gap-2 h-12 px-[22px] rounded-[10px] text-[15px] font-bold text-[#1A1A1A] border-2 border-[#F59E0B] bg-white">
                        <Play class="w-4 h-4" /> Demo story
                    </a>
                </div>
                <div class="flex flex-col gap-[22px] mt-2">
                    <div v-for="step in steps" :key="step.n" class="flex gap-4 items-start">
                        <div :class="gradBg" class="flex-none w-12 h-12 rounded-[14px] flex items-center justify-center">
                            <component :is="step.icon" class="w-[22px] h-[22px]" />
                        </div>
                        <div>
                            <div class="text-xs font-bold tracking-[0.12em] text-[#555555]">{{ step.n }}</div>
                            <div class="text-lg font-bold mt-0.5">{{ step.title }}</div>
                            <div class="text-[15px] leading-normal text-[#555555] mt-1">{{ step.body }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="signup" class="flex-[1_1_400px] min-w-0 max-w-[520px] bg-white border border-[#E6E2D6] rounded-[20px] p-6 sm:p-9 shadow-[0_8px_30px_rgba(26,26,26,0.06)]">
                <div v-if="submitted" class="flex flex-col gap-4 items-start" aria-live="polite">
                    <div :class="gradBg" class="w-14 h-14 rounded-2xl flex items-center justify-center">
                        <Check class="w-[26px] h-[26px]" :stroke-width="2.4" />
                    </div>
                    <h2 class="text-[28px] font-extrabold">You're in.</h2>
                    <p class="text-[17px] leading-relaxed text-[#444444]">Thanks, {{ form.first_name.trim() }}. We'll send your StoryCreator.Bot welcome to {{ form.email.trim() }} so you can start your first story.</p>
                    <p class="text-[17px] leading-relaxed font-bold text-[#1A1A1A]">Your story is your business.</p>
                </div>

                <form v-else novalidate @submit.prevent="submit">
                    <div class="flex flex-wrap gap-3">
                        <a v-for="tier in tiers" :key="tier.key" :href="linkFor(tier.key)" target="_blank" rel="noopener noreferrer" :class="gradBg" class="flex-[1_1_140px] flex items-center justify-center gap-2 h-12 px-3 rounded-[10px] text-[15px] font-bold text-[#1A1A1A]">
                            {{ tier.name }} Signup <ArrowRight class="w-4 h-4" :stroke-width="2.2" />
                        </a>
                    </div>
                    <div class="flex items-center gap-3 mt-6 mb-5 text-sm font-semibold text-[#555555] before:content-[''] before:flex-1 before:h-px before:bg-[#E6E2D6] after:content-[''] after:flex-1 after:h-px after:bg-[#E6E2D6]">
                        Or start your trial first
                    </div>
                    <h2 class="text-2xl font-extrabold tracking-[-0.01em]">Start Your Complimentary Trial</h2>
                    <p class="mt-2 text-base leading-normal text-[#555555]">Create your account and StoryCreator.Bot will write your first story.</p>

                    <div class="flex flex-col gap-1.5 mt-5">
                        <label for="f-first" class="text-sm font-bold">First Name</label>
                        <input id="f-first" v-model="form.first_name" type="text" autocomplete="given-name" :class="inputClass" />
                    </div>
                    <div class="flex flex-col gap-1.5 mt-5">
                        <label for="f-last" class="text-sm font-bold">Last Name</label>
                        <input id="f-last" v-model="form.last_name" type="text" autocomplete="family-name" :class="inputClass" />
                    </div>
                    <div class="flex flex-col gap-1.5 mt-5">
                        <label for="f-phone" class="text-sm font-bold">Phone</label>
                        <input id="f-phone" :value="form.phone" @input="onPhone" type="tel" inputmode="numeric" maxlength="10" autocomplete="tel-national" aria-describedby="phone-hint" :class="inputClass" />
                        <span id="phone-hint" class="text-[13px] text-[#666666]">US format, digits only, e.g. 3478245640</span>
                    </div>
                    <div class="flex flex-col gap-1.5 mt-5">
                        <label for="f-email" class="text-sm font-bold">Email</label>
                        <input id="f-email" v-model="form.email" type="email" autocomplete="email" :class="inputClass" />
                    </div>

                    <p v-if="error" role="alert" class="mt-5 text-sm font-semibold text-[#B42318]">{{ error }}</p>

                    <button type="submit" :disabled="form.processing" :class="gradBg" class="mt-6 w-full h-14 rounded-xl text-[17px] font-bold text-[#1A1A1A] flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60">
                        Start My Trial <ArrowRight class="w-[18px] h-[18px]" :stroke-width="2.2" />
                    </button>
                    <p class="mt-4 text-[13px] leading-normal text-[#555555] text-center">No membership required.</p>
                </form>
            </section>
        </main>

        <!-- Pricing -->
        <section id="pricing" class="max-w-[1200px] mx-auto w-full px-5 sm:px-12 pb-[72px] flex flex-col gap-7 scroll-mt-4">
            <div class="bg-[#1A1A1A] rounded-2xl p-6 sm:px-9 sm:py-8 flex flex-col gap-3">
                <div class="self-start bg-[#FFC93C] text-[#1A1A1A] text-xs font-bold tracking-[0.1em] px-2.5 py-[5px] rounded-md">ALA CARTE PAYMENT PROGRAMS</div>
                <h2 class="text-2xl sm:text-[30px] font-extrabold text-white tracking-[-0.01em]">Pay to Play StoryCreator.Bot Pricing Options</h2>
                <p class="text-[15px] leading-normal text-[#C9C4B8]">Flexible <b class="text-[#FFC93C]">content plans</b> for general social media use. You must be Verified to post on Best of Local.</p>
                <div class="flex flex-wrap gap-x-8 gap-y-2 mt-2">
                    <div v-for="(col, i) in perks" :key="i" class="flex-[1_1_200px] flex flex-col gap-2 text-sm text-[#C9C4B8]">
                        <div v-for="perk in col" :key="perk" class="flex items-center gap-2">
                            <Check class="w-3.5 h-3.5 text-[#FFC93C]" :stroke-width="3" />{{ perk }}
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="text-sm font-bold tracking-[0.08em] text-[#444444]">PAY TO PLAY PRICING PLANS (FOR NON-VERIFIED BUSINESS PARTNERS ONLY)</h3>

            <div class="flex flex-wrap gap-6 items-stretch">
                <div v-for="tier in tiers" :key="tier.key" class="relative flex-[1_1_280px] min-w-0 bg-white border-2 border-[#F59E0B] rounded-2xl p-7 flex flex-col gap-3">
                    <div v-if="tier.popular" :class="gradBg" class="absolute -top-3.5 left-1/2 -translate-x-1/2 text-[#1A1A1A] text-xs font-bold px-3.5 py-1 rounded-full whitespace-nowrap">Most Popular</div>
                    <div class="text-base font-bold">{{ tier.name }}</div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <b class="text-5xl font-extrabold tracking-[-0.02em]">{{ tier.price }}</b>
                        <span class="text-sm text-[#555555]">one-time</span>
                    </div>
                    <div class="text-[13px] italic leading-normal text-[#555555]">{{ tier.desc }}</div>
                    <ul class="mt-1 mb-2 flex flex-col gap-2 flex-1 text-[15px] font-semibold">
                        <li v-for="f in tier.features" :key="f" class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-[#F59E0B]" :stroke-width="3" />{{ f }}
                        </li>
                    </ul>
                    <a :href="linkFor(tier.key)" target="_blank" rel="noopener noreferrer" :class="gradBg" class="flex items-center justify-center gap-2 h-12 px-[22px] rounded-[10px] text-[15px] font-bold text-[#1A1A1A]">
                        Sign Up <ArrowRight class="w-4 h-4" :stroke-width="2.2" />
                    </a>
                </div>
            </div>
        </section>

        <!-- The tool -->
        <section class="max-w-[1200px] mx-auto w-full px-5 sm:px-12">
            <div class="bg-[#111111] rounded-[20px] p-7 sm:p-12 flex flex-col gap-3.5 mb-[72px]">
                <div class="text-xs font-bold tracking-[0.14em] text-[#FFC93C]">THE TOOL</div>
                <h2 class="text-[28px] sm:text-4xl font-extrabold text-white tracking-[-0.01em]">Every business has its own <span :class="gradText">story</span> to tell</h2>
                <p class="text-lg leading-relaxed text-[#E8E4D8] max-w-[760px]">Whether you are new and building trust, holding ground, or established and protecting a legacy, the answer is the same. Your story is unique. Your story defines your business. StoryCreator.Bot saves you marketing expenditure and, maybe more important, the time you need to devote to running your company, not promoting it.</p>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="max-w-[800px] mx-auto px-5 sm:px-12 pb-20 scroll-mt-4">
            <h2 class="mb-2 text-[32px] font-extrabold text-center">Frequently Asked Questions</h2>
            <p class="mb-7 text-base text-[#555555] text-center">Everything you need to know before getting started.</p>
            <div id="faq-box" class="bg-white border border-[#E6E2D6] rounded-2xl overflow-hidden">
                <div v-for="(faq, i) in visibleFaqs" :key="faq.q" class="px-6 py-5 border-b border-[#E6E2D6] last:border-b-0">
                    <button type="button" @click="toggleFaq(i)" :aria-expanded="openFaq === i" class="w-full text-left text-base font-bold cursor-pointer">{{ faq.q }}</button>
                    <p v-if="openFaq === i" class="mt-3 text-[15px] leading-relaxed text-[#555555]">{{ faq.a }}</p>
                </div>
            </div>
            <div class="text-center mt-6">
                <button type="button" @click="faqExpanded = !faqExpanded" :aria-expanded="faqExpanded" aria-controls="faq-box" class="inline-flex items-center gap-2 h-12 px-6 border-2 border-[#F59E0B] rounded-[10px] bg-white text-[#1A1A1A] text-[15px] font-bold cursor-pointer">
                    {{ faqExpanded ? 'See less' : 'See more' }}
                    <ChevronDown class="w-4 h-4 transition-transform duration-200" :class="faqExpanded && 'rotate-180'" :stroke-width="2.2" />
                </button>
            </div>
        </section>

        <footer class="px-5 sm:px-12 pt-6 pb-10 text-center text-[13px] text-[#555555]">&copy; 2026 Best of Ventures, Inc. &middot; Your story is your business.</footer>
    </div>
</template>
