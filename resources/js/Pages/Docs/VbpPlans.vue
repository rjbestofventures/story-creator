<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Footer from '@/Components/Footer.vue';

const page = usePage();
const plans = computed(() => page.props.vbpPlans ?? []);
const creditsFor = (key) => plans.value.find(p => p.key === key)?.credits ?? 0;
const planKeys = computed(() => plans.value.map(p => `\`${p.key}\``).join(', '));
const tempCredits = computed(() => page.props.temporaryVbpCredits);
const tempEpisodes = computed(() => page.props.temporaryVbpEpisodes);
const tempCost = computed(() => page.props.temporaryVbpStoryCost);

const json = (value) => JSON.stringify(value, null, 4);

const accountTypes = computed(() => [
    {
        name: 'Verified Business Partner on a plan',
        how: 'POST /api/provision/user with vbp_plan, or convert-to-partner',
        credits: plans.value.map(p => `${p.label} ${p.credits}`).join(' · '),
        notes: 'Partner pricing in the shop. No expiry.',
    },
    {
        name: 'Complementary Trial',
        how: 'POST /api/provision/temporary-vbp, or POST /api/provision/user with trial: true',
        credits: `${tempCredits.value}`,
        notes: `One ${tempEpisodes.value}-episode story costing ${tempCost.value} credits, with only 3 episodes readable and the rest locked. The remaining ${tempCredits.value - tempCost.value} credits are for AI Refine. Deactivated after 3 months unless converted.`,
    },
    {
        name: 'Partner pricing only',
        how: 'POST /api/provision/verify-partner',
        credits: 'None granted',
        notes: 'Unlocks partner pack prices. Can record a plan, but grants nothing.',
    },
]);

const endpoints = computed(() => [
    {
        id: 'create-user',
        method: 'POST',
        path: '/api/provision/user',
        auth: 'Provision token',
        title: 'Create a user on a plan',
        planField: 'Optional',
        effect: `Creates the account as a Verified Business Partner on the plan and grants its credits (Other: ${creditsFor('other')}).`,
        rules: [
            'Cannot be combined with trial or pack — the plan grants its own credits. Returns 422.',
            'The user is emailed a link to set their password.',
            'If the email already has an account, it is updated instead of rejected: the plan and any pack are applied, the name is kept, no trial starts and no email is sent. The response is 200 with created: false (new accounts return 201 with created: true).',
        ],
        request: { name: 'Oli Other', email: 'oli.other@example.com', vbp_plan: 'other' },
        status: '201 Created',
        response: {
            created: true,
            user: {
                id: 60, name: 'Oli Other', email: 'oli.other@example.com', is_active: true,
                is_verified_partner: true, vbp_plan: 'other', is_temporary_vbp: false,
                temporary_vbp_expires_at: null, is_trial: false, trial_allowance: 0,
                credits: creditsFor('other'),
            },
            pack: null,
        },
    },
    {
        id: 'convert-to-partner',
        method: 'POST',
        path: '/api/provision/convert-to-partner',
        auth: 'Provision token',
        title: 'Convert a Complementary Trial',
        planField: 'Required',
        effect: 'Makes the account a full partner on the plan and adds the plan\'s credits on top of whatever it already holds.',
        rules: [
            `A Complementary Trial keeps its leftover credits — ${tempCredits.value - tempCost.value} left + ${creditsFor('other')} for Other = ${tempCredits.value - tempCost.value + creditsFor('other')}. Its expiry is cleared and a deactivated account is reopened.`,
            'The trial ends, but the locked episodes stay locked until they spend credits to open them.',
            'Calling it again on someone who is already a full partner only changes the plan. Credits are never granted twice.',
        ],
        request: { email: 'tess@example.com', vbp_plan: 'other' },
        status: '200 OK',
        response: {
            user: {
                id: 51, name: 'Tess Temp', email: 'tess@example.com', is_active: true,
                is_verified_partner: true, vbp_plan: 'other', is_temporary_vbp: false,
                temporary_vbp_expires_at: null, is_trial: false, trial_allowance: 0,
                credits: tempCredits.value - tempEpisodes.value + creditsFor('other'),
            },
        },
    },
    {
        id: 'verify-partner',
        method: 'POST',
        path: '/api/provision/verify-partner',
        auth: 'Provision token',
        title: 'Give partner pricing only',
        planField: 'Optional',
        effect: 'Marks the account a partner and records the plan. No credits are granted and a running trial is left alone.',
        rules: [
            'Use convert-to-partner instead when the member should receive the plan\'s credits.',
            'Safe to call more than once.',
        ],
        request: { email: 'jane@example.com', vbp_plan: 'other' },
        status: '200 OK',
        response: {
            user: {
                id: 43, name: 'Jane Smith', email: 'jane@example.com', is_active: true,
                is_verified_partner: true, vbp_plan: 'other', is_temporary_vbp: false,
                temporary_vbp_expires_at: null, is_trial: true, trial_allowance: 1,
                credits: 0,
            },
        },
    },
    {
        id: 'temporary-vbp',
        method: 'POST',
        path: '/api/provision/temporary-vbp',
        auth: 'Provision token',
        title: 'Create a Complementary Trial',
        planField: 'Not accepted',
        effect: `Creates a Complementary Trial with ${tempCredits.value} credits. Plans do not apply until it is converted.`,
        rules: [
            `Generates one ${tempEpisodes.value}-episode story for ${tempCost.value} credits, with 3 episodes readable and ${tempEpisodes.value - 3} locked; the remaining ${tempCredits.value - tempCost.value} credits are for AI Refine.`,
            'Deactivated 3 months after creation unless converted with convert-to-partner.',
        ],
        request: { name: 'Tess Temp', email: 'tess@example.com' },
        status: '201 Created',
        response: {
            user: {
                id: 51, name: 'Tess Temp', email: 'tess@example.com', is_active: true,
                is_verified_partner: false, vbp_plan: null, is_temporary_vbp: true,
                temporary_vbp_expires_at: '2026-12-29T13:35:50+00:00', is_trial: false,
                trial_allowance: 0, credits: tempCredits.value,
            },
        },
    },
    {
        id: 'api-users',
        method: 'POST',
        path: '/api/users',
        auth: 'Sanctum user token',
        title: 'Create a user (Sanctum API)',
        planField: 'Optional',
        effect: 'Same as create-user: a plan makes the account a partner with the plan\'s credits.',
        rules: [
            'Cannot be combined with a trial_allowance above 0. Returns 422.',
            'Responds with a flat user object rather than one nested under "user".',
        ],
        request: { name: 'Pat Partner', email: 'pat@example.com', vbp_plan: 'other' },
        status: '201 Created',
        response: {
            id: 61, name: 'Pat Partner', email: 'pat@example.com', tier: 'user',
            is_verified_partner: true, vbp_plan: 'other', credits: creditsFor('other'),
            is_trial: false, trial_allowance: 0,
        },
    },
]);

const errors = [
    { status: '401', cause: 'Missing or wrong bearer token.' },
    { status: '404', cause: 'No account with that email (convert-to-partner, verify-partner).' },
    { status: '422', cause: 'vbp_plan is not one of the plan keys, is missing on convert-to-partner, or is combined with trial, pack, or trial_allowance.' },
];
</script>

<template>
    <Head title="VBP Plans & Provisioning API — StoryCreator.Bot">
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <div class="min-h-screen flex flex-col bg-[#FAFAF8]">
        <header class="bg-white flex items-center justify-between px-6 md:px-8 py-4 border-b border-[#DDDDDD]">
            <Link href="/" class="flex items-center text-xl font-bold tracking-tight">
                <span class="bg-gradient-to-r from-[#FFC837] to-[#F5A000] bg-clip-text text-transparent">StoryCreator</span>
                <span class="text-[#1A1A1A]">.Bot</span>
            </Link>
        </header>

        <main class="flex-1 px-4 sm:px-6 md:px-10 py-16 max-w-4xl mx-auto w-full">
            <p class="text-xs font-bold tracking-widest uppercase mb-3 text-[#888888]">Documentation</p>
            <h1 class="text-4xl font-black mb-3 text-[#1A1A1A]">VBP Plans &amp; Provisioning API</h1>
            <p class="text-base leading-relaxed text-[#555555] max-w-2xl">
                How Verified Business Partner plans work, how many credits each one grants, and what every
                provisioning endpoint does with the <code class="font-mono text-sm bg-white border border-[#DDDDDD] rounded px-1">vbp_plan</code> field.
                Credit numbers on this page are read live from the app.
            </p>

            <nav class="mt-8 bg-white rounded-2xl border border-[#DDDDDD] p-5">
                <p class="text-xs font-bold tracking-wider uppercase text-[#AAAAAA] mb-3">On this page</p>
                <ul class="grid sm:grid-cols-2 gap-x-6 gap-y-1.5 text-sm">
                    <li><a href="#plans" class="text-[#1A1A1A] hover:text-[#F5A000]">Plans and credits</a></li>
                    <li><a href="#account-types" class="text-[#1A1A1A] hover:text-[#F5A000]">Account types</a></li>
                    <li><a href="#authentication" class="text-[#1A1A1A] hover:text-[#F5A000]">Authentication</a></li>
                    <li v-for="e in endpoints" :key="e.id">
                        <a :href="`#${e.id}`" class="text-[#1A1A1A] hover:text-[#F5A000]">{{ e.title }}</a>
                    </li>
                    <li><a href="#errors" class="text-[#1A1A1A] hover:text-[#F5A000]">Errors</a></li>
                    <li><a href="#integration-notes" class="text-[#1A1A1A] hover:text-[#F5A000]">Integration notes</a></li>
                </ul>
            </nav>

            <section id="plans" class="mt-14 scroll-mt-8">
                <h2 class="text-2xl font-black text-[#1A1A1A] mb-2">Plans and credits</h2>
                <p class="text-sm text-[#555555] mb-5">A partner is on exactly one plan. The plan's credits are granted once, when the member becomes a partner.</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div v-for="plan in plans" :key="plan.key" class="bg-white rounded-2xl border border-[#DDDDDD] p-5">
                        <p class="text-sm font-bold text-[#1A1A1A]">{{ plan.label }}</p>
                        <p class="mt-1"><span class="text-4xl font-black text-[#1A1A1A]">{{ plan.credits }}</span> <span class="text-sm text-[#555555]">credits</span></p>
                        <p class="mt-3 text-xs text-[#555555]">API value <code class="font-mono bg-[#F5F5F5] rounded px-1 text-[#1A1A1A]">"{{ plan.key }}"</code></p>
                    </div>
                </div>
                <p class="text-xs text-[#888888] mt-3">1 credit generates or refines 1 episode, so a 12-episode story uses 12 credits.</p>
            </section>

            <section id="account-types" class="mt-14 scroll-mt-8">
                <h2 class="text-2xl font-black text-[#1A1A1A] mb-5">Account types</h2>
                <div class="bg-white rounded-2xl border border-[#DDDDDD] overflow-x-auto">
                    <table class="w-full text-sm text-left min-w-[36rem]">
                        <thead class="bg-[#F5F5F5] text-xs uppercase tracking-wider text-[#888888]">
                            <tr>
                                <th class="px-4 py-3 font-bold">Type</th>
                                <th class="px-4 py-3 font-bold">Created with</th>
                                <th class="px-4 py-3 font-bold">Starting credits</th>
                                <th class="px-4 py-3 font-bold">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EEEEEE] text-[#555555]">
                            <tr v-for="t in accountTypes" :key="t.name" class="align-top">
                                <td class="px-4 py-3 font-bold text-[#1A1A1A]">{{ t.name }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ t.how }}</td>
                                <td class="px-4 py-3 font-semibold text-[#1A1A1A]">{{ t.credits }}</td>
                                <td class="px-4 py-3">{{ t.notes }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="authentication" class="mt-14 scroll-mt-8">
                <h2 class="text-2xl font-black text-[#1A1A1A] mb-2">Authentication</h2>
                <p class="text-sm text-[#555555] mb-4">
                    Every <code class="font-mono">/api/provision/*</code> request sends the server's provision token as a bearer token.
                    <code class="font-mono">/api/users</code> instead uses a Sanctum token belonging to an existing user.
                    Send <code class="font-mono">Accept: application/json</code> so validation errors come back as JSON.
                </p>
                <pre class="bg-[#1A1A1A] text-[#F5F5F5] rounded-2xl p-5 text-xs overflow-x-auto"><code>Authorization: Bearer {PROVISION_API_TOKEN}
Content-Type: application/json
Accept: application/json</code></pre>
            </section>

            <section v-for="e in endpoints" :id="e.id" :key="e.id" class="mt-14 scroll-mt-8">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="text-xs font-bold rounded-md px-2 py-1 bg-[#F5A623] text-[#1A1A1A]">{{ e.method }}</span>
                    <code class="font-mono text-sm font-semibold text-[#1A1A1A] break-all">{{ e.path }}</code>
                </div>
                <h2 class="text-2xl font-black text-[#1A1A1A] mb-3">{{ e.title }}</h2>

                <div class="flex flex-wrap gap-2 mb-4 text-xs">
                    <span class="rounded-lg border border-[#DDDDDD] bg-white px-2.5 py-1 text-[#555555]">Auth: <strong class="text-[#1A1A1A]">{{ e.auth }}</strong></span>
                    <span class="rounded-lg border border-[#DDDDDD] bg-white px-2.5 py-1 text-[#555555]"><code class="font-mono">vbp_plan</code>: <strong class="text-[#1A1A1A]">{{ e.planField }}</strong></span>
                </div>

                <p class="text-sm text-[#555555] mb-3">{{ e.effect }}</p>
                <ul class="text-sm text-[#555555] list-disc pl-5 space-y-1 mb-5">
                    <li v-for="rule in e.rules" :key="rule">{{ rule }}</li>
                </ul>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-bold tracking-wider uppercase text-[#AAAAAA] mb-2">Request body</p>
                        <pre class="bg-[#1A1A1A] text-[#F5F5F5] rounded-2xl p-5 text-xs overflow-x-auto"><code>{{ json(e.request) }}</code></pre>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold tracking-wider uppercase text-[#AAAAAA] mb-2">Response · {{ e.status }}</p>
                        <pre class="bg-[#1A1A1A] text-[#F5F5F5] rounded-2xl p-5 text-xs overflow-x-auto"><code>{{ json(e.response) }}</code></pre>
                    </div>
                </div>
            </section>

            <section id="errors" class="mt-14 scroll-mt-8">
                <h2 class="text-2xl font-black text-[#1A1A1A] mb-5">Errors</h2>
                <div class="bg-white rounded-2xl border border-[#DDDDDD] divide-y divide-[#EEEEEE]">
                    <div v-for="err in errors" :key="err.status" class="flex gap-4 px-5 py-3 text-sm">
                        <span class="font-mono font-bold text-[#1A1A1A] shrink-0 w-10">{{ err.status }}</span>
                        <span class="text-[#555555]">{{ err.cause }}</span>
                    </div>
                </div>
                <p class="text-xs text-[#888888] mt-3">A 422 names the failing field under <code class="font-mono">errors</code>, e.g. <code class="font-mono">{"errors": {"vbp_plan": ["The selected vbp plan is invalid."]}}</code>.</p>
            </section>

            <section id="integration-notes" class="mt-14 scroll-mt-8">
                <h2 class="text-2xl font-black text-[#1A1A1A] mb-4">Integration notes</h2>
                <ul class="text-sm text-[#555555] list-disc pl-5 space-y-2">
                    <li>Valid <code class="font-mono">vbp_plan</code> values are {{ planKeys }}. Anything else returns 422.</li>
                    <li>Responses can contain <code class="font-mono">"vbp_plan": "other"</code>. Treat any value from the list above as valid, not only gold and silver.</li>
                    <li>Plan credits were rebalanced (Gold 48 → {{ creditsFor('gold') }}, Silver 36 → {{ creditsFor('silver') }}, Other added at {{ creditsFor('other') }}). Don't hard-code the old amounts when checking <code class="font-mono">credits</code> in a response.</li>
                    <li>Existing partners keep the credits they were given. New amounts apply to conversions from now on.</li>
                    <li>A Postman collection with a request for every plan is in the repository at <code class="font-mono">docs/provision-api.postman_collection.json</code>.</li>
                </ul>
            </section>
        </main>

        <Footer />
    </div>
</template>
