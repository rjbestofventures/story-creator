<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Terms of Service — StoryCreator.Bot" />

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
            <h1 class="text-4xl font-black mb-2" style="color: #1A1A1A;">Terms of Service</h1>
            <p class="text-sm mb-12" style="color: #888888;">Last updated: June 4, 2026</p>

            <div class="space-y-10 text-sm leading-relaxed" style="color: #555555;">

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">1. Acceptance of Terms</h2>
                    <p>By accessing or using StoryCreator.Bot ("the Service"), you agree to be bound by these Terms of Service. If you do not agree, do not use the Service.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">2. Description of Service</h2>
                    <p>StoryCreator.Bot is an AI-powered content generation platform that helps businesses create story-based content for publication across digital channels. The Service is provided on a subscription basis with the features and limits described on our pricing page.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">3. Accounts</h2>
                    <p>You must provide accurate information when creating an account. You are responsible for maintaining the security of your account credentials and for all activity that occurs under your account. Notify us immediately at <a href="mailto:support@storycreator.bot" class="underline" style="color: #F5A000;">support@storycreator.bot</a> if you suspect unauthorised access.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">4. Subscriptions & Billing</h2>
                    <p>Subscriptions are billed monthly or annually in advance. Credits included in your plan accumulate and never expire while your subscription is active. You may cancel at any time; access continues until the end of the current billing period. We do not offer refunds for partial periods.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">5. Content Ownership</h2>
                    <p>You retain ownership of all content you provide to the Service (business details, URLs, descriptions). You also own the generated output produced using your inputs. By submitting content, you grant us a limited licence to process it solely for the purpose of delivering the Service.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">6. Acceptable Use</h2>
                    <p>You agree not to use the Service to generate content that is unlawful, harmful, defamatory, or deceptive. We reserve the right to suspend accounts that violate this policy without prior notice.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">7. Disclaimer of Warranties</h2>
                    <p>The Service is provided "as is" without warranties of any kind, express or implied. We do not guarantee that generated content will be accurate, complete, or suitable for any particular purpose. You are responsible for reviewing content before publishing.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">8. Limitation of Liability</h2>
                    <p>To the fullest extent permitted by law, StoryCreator.Bot shall not be liable for any indirect, incidental, or consequential damages arising from your use of the Service. Our total liability to you shall not exceed the amount you paid in the twelve months preceding the claim.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">9. Changes to These Terms</h2>
                    <p>We may update these Terms from time to time. We will notify you by email or in-app notice before material changes take effect. Continued use of the Service after changes constitutes acceptance.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">10. Contact</h2>
                    <p>Questions about these Terms? Email us at <a href="mailto:legal@storycreator.bot" class="underline" style="color: #F5A000;">legal@storycreator.bot</a>.</p>
                </section>

            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white px-6 md:px-10 py-5 flex flex-col md:flex-row items-center justify-between gap-3" style="border-top: 1px solid #DDDDDD;">
            <p class="text-xs" style="color: #555555;">© 2026 StoryCreator.Bot. All rights reserved.</p>
            <nav class="flex items-center gap-6">
                <Link :href="route('terms')" class="text-xs transition hover:opacity-70" style="color: #1A1A1A; font-weight: 600;">Terms</Link>
                <Link :href="route('privacy')" class="text-xs transition hover:opacity-70" style="color: #555555;">Privacy</Link>
            </nav>
        </footer>

    </div>
</template>
