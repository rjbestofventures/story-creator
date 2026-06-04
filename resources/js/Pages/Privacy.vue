<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Privacy Policy — StoryCreator.Bot" />

    <div class="min-h-screen flex flex-col" style="background-color: #FAFAF8;">

        <!-- Nav -->
        <header class="bg-white flex items-center justify-between px-6 md:px-8 py-4" style="border-bottom: 1px solid #DDDDDD;">
            <Link href="/" class="flex items-center text-xl font-bold tracking-tight">
                <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
                <span style="color: #1A1A1A;">.Bot</span>
            </Link>
        </header>

        <!-- Content -->
        <main class="flex-1 px-6 md:px-10 py-16 max-w-3xl mx-auto w-full">

            <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color: #888888;">Legal</p>
            <h1 class="text-4xl font-black mb-2" style="color: #1A1A1A;">Privacy Policy</h1>
            <p class="text-sm mb-12" style="color: #888888;">Last updated: June 4, 2026</p>

            <div class="space-y-10 text-sm leading-relaxed" style="color: #555555;">

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">1. What We Collect</h2>
                    <p>We collect information you provide directly — such as your name, email address, business details, and URLs — when you create an account or use the Service. We also collect usage data (pages visited, features used, session duration) and basic device/browser information to improve the Service.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">2. How We Use Your Information</h2>
                    <p>We use your information to provide and improve the Service, process payments, send transactional emails (account confirmations, billing receipts), and occasionally send product updates. We do not sell your personal data to third parties.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">3. AI Processing</h2>
                    <p>Business information and URLs you submit are sent to AI models to generate your story content. This data is used solely to produce your requested output and is not used to train third-party AI models. Generated content is stored in your account and is accessible only by you.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">4. Payments</h2>
                    <p>Payment processing is handled by Stripe. We do not store your card details. Stripe's privacy policy governs the handling of payment information. We receive only a token and basic billing confirmation from Stripe.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">5. Cookies</h2>
                    <p>We use session cookies required for authentication and to keep you logged in. We do not use third-party advertising cookies. You can disable cookies in your browser, but this will prevent you from logging in.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">6. Data Retention</h2>
                    <p>We retain your account data for as long as your account is active. If you delete your account, your personal data and generated content are permanently removed within 30 days, except where we are required to retain records for legal or billing purposes.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">7. Your Rights</h2>
                    <p>You may request access to, correction of, or deletion of your personal data at any time by emailing <a href="mailto:privacy@storycreator.bot" class="underline" style="color: #F5A000;">privacy@storycreator.bot</a>. If you are located in the EU or UK, you have additional rights under GDPR, including the right to data portability and to lodge a complaint with your supervisory authority.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">8. Security</h2>
                    <p>We use industry-standard measures including HTTPS encryption, hashed passwords, and access controls to protect your data. No method of transmission over the internet is 100% secure, and we cannot guarantee absolute security.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">9. Changes to This Policy</h2>
                    <p>We may update this Privacy Policy from time to time. We will notify you by email or in-app notice before material changes take effect. The date at the top of this page reflects the most recent revision.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">10. Contact</h2>
                    <p>Privacy questions or requests? Email us at <a href="mailto:privacy@storycreator.bot" class="underline" style="color: #F5A000;">privacy@storycreator.bot</a>.</p>
                </section>

            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white px-6 md:px-10 py-5 flex flex-col md:flex-row items-center justify-between gap-3" style="border-top: 1px solid #DDDDDD;">
            <p class="text-xs" style="color: #555555;">© 2026 StoryCreator.Bot. All rights reserved.</p>
            <nav class="flex items-center gap-6">
                <Link :href="route('terms')" class="text-xs transition hover:opacity-70" style="color: #555555;">Terms</Link>
                <Link :href="route('privacy')" class="text-xs transition hover:opacity-70" style="color: #1A1A1A; font-weight: 600;">Privacy</Link>
            </nav>
        </footer>

    </div>
</template>
