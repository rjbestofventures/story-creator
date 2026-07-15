<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Footer from '@/Components/Footer.vue';
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
            <p class="text-sm mb-12" style="color: #888888;">Last updated: June 15, 2026</p>

            <div class="space-y-10 text-sm leading-relaxed" style="color: #555555;">

                <section>
                    <p>This Privacy Policy describes how Best of Ventures, Inc. ("Company," "we," "us," or "our"), the owner and operator of StoryCreator.Bot ("the Service" or "the Platform"), collects, uses, stores, shares, and protects information about you when you access or use the Service. It also describes your rights with respect to that information and how to exercise them.</p>
                    <p class="mt-3">This Privacy Policy is incorporated by reference into our Terms of Service. By creating an account or using the Service, you acknowledge that you have read and understood this Privacy Policy and consent to the data practices described herein. If you do not agree with this Privacy Policy, do not access or use the Service.</p>
                    <p class="mt-3">This Policy applies solely to information collected through the Service. It does not apply to information collected offline or through any third-party websites, applications, or services, even if they are linked to or integrated with the Service.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">1. Information We Collect</h2>
                    <p><strong style="color: #1A1A1A;">1.1 Information You Provide Directly.</strong> When you register for an account, subscribe to a plan, or use features of the Service, we collect information you provide, which may include:</p>
                    <ul class="list-disc list-outside ml-5 mt-3 space-y-2">
                        <li>Identity and contact information, such as your name, email address, business name, and phone number;</li>
                        <li>Account credentials, including your username and encrypted password;</li>
                        <li>Business content and intake data, including business descriptions, URLs, interview responses, brand voice preferences, and any other materials you submit through the Service's intake workflow;</li>
                        <li>Billing and payment information, such as your billing address and payment method details. Full card numbers are processed exclusively by our payment processor, Stripe, and are not stored by us;</li>
                        <li>Communications you send us, including support requests, feedback, and correspondence.</li>
                    </ul>
                    <p class="mt-3"><strong style="color: #1A1A1A;">1.2 Information We Collect Automatically.</strong> When you access or use the Service, we automatically collect certain technical and usage data, including:</p>
                    <ul class="list-disc list-outside ml-5 mt-3 space-y-2">
                        <li>Log data, such as your IP address, browser type and version, operating system, referring URLs, pages visited, features used, and session duration;</li>
                        <li>Device information, including device type, screen resolution, and general geographic location inferred from IP address;</li>
                        <li>Cookie and tracking data as described in Section 6.</li>
                    </ul>
                    <p class="mt-3"><strong style="color: #1A1A1A;">1.3 Information from Third-Party Integrations.</strong> If you connect third-party services to your account (such as social media platforms or publishing tools), we may receive information from those services in accordance with their privacy policies and your authorization. We use that information only to provide the integration features you have enabled.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">2. How We Use Your Information</h2>
                    <p>We use the information we collect for the following purposes:</p>
                    <ul class="list-disc list-outside ml-5 mt-3 space-y-2">
                        <li>To provide, operate, and maintain the Service, including processing your intake submissions, generating content, and delivering output to your account;</li>
                        <li>To manage your account, authenticate your identity, and enforce our Terms of Service;</li>
                        <li>To process payments, issue receipts, and manage billing;</li>
                        <li>To send transactional communications, including account confirmations, password resets, billing receipts, and service-related notices;</li>
                        <li>To send product updates, feature announcements, or other communications related to the Service, from which you may opt out at any time;</li>
                        <li>To improve and develop the Service, analyze usage patterns, diagnose technical issues, and inform product decisions;</li>
                        <li>To comply with applicable legal obligations, respond to lawful requests from government authorities, and enforce our legal rights;</li>
                        <li>To detect, investigate, and prevent fraudulent transactions, unauthorized access, and other illegal or prohibited activities.</li>
                    </ul>
                    <p class="mt-3">We do not sell, rent, or trade your personal information to third parties for their own marketing purposes.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">3. AI Processing of Your Content</h2>
                    <p><strong style="color: #1A1A1A;">3.1 How Your Content Is Processed.</strong> Business information, interview responses, URLs, and other intake data you submit through the Service are transmitted to artificial intelligence infrastructure for the purpose of generating your story content. This processing is the core function of the Service.</p>
                    <p class="mt-3"><strong style="color: #1A1A1A;">3.2 Scope of AI Use.</strong> Your submitted content is used solely to produce the Generated Output you have requested. We do not use your submitted content or Generated Output to train, fine-tune, or benchmark our own AI models or systems.</p>
                    <p class="mt-3"><strong style="color: #1A1A1A;">3.3 Third-Party AI Infrastructure.</strong> The Service uses third-party artificial intelligence providers as infrastructure components to process requests. While we take reasonable steps to ensure that our agreements with these providers restrict the use of your data to service delivery, we cannot guarantee or control how those providers handle data on their own infrastructure. You are encouraged to review the privacy policies of our AI infrastructure providers independently. We will update this Section if our primary AI provider changes.</p>
                    <p class="mt-3"><strong style="color: #1A1A1A;">3.4 Storage of Generated Content.</strong> Content generated through the Service is stored in your account and is accessible only by you and authorized personnel of the Company acting in the course of support or maintenance. We do not review, publish, or share your Generated Output except as described in this Privacy Policy or as required by law.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">4. How We Share Your Information</h2>
                    <p>We do not sell your personal information. We share your information only in the following circumstances:</p>
                    <p class="mt-3"><strong style="color: #1A1A1A;">4.1 Service Providers and Subprocessors.</strong> We engage third-party vendors and service providers who process data on our behalf in order to deliver the Service. These include, but are not limited to:</p>
                    <ul class="list-disc list-outside ml-5 mt-3 space-y-2">
                        <li>Cloud hosting and infrastructure providers (including DigitalOcean for server hosting and Cloudflare for content delivery and security);</li>
                        <li>Payment processors (Stripe, for billing and subscription management);</li>
                        <li>Email delivery providers (including Mailgun, for transactional and notification emails);</li>
                        <li>Artificial intelligence infrastructure providers, as described in Section 3.</li>
                    </ul>
                    <p class="mt-3">All subprocessors are contractually required to use your information only for the purposes of providing their services to us and to maintain appropriate security standards. We remain responsible for ensuring that each subprocessor handles your data in a manner consistent with this Privacy Policy.</p>
                    <p class="mt-3"><strong style="color: #1A1A1A;">4.2 Legal Requirements.</strong> We may disclose your information if we believe in good faith that disclosure is required by applicable law, regulation, legal process, or enforceable governmental request, or to protect the rights, property, or safety of the Company, our users, or the public.</p>
                    <p class="mt-3"><strong style="color: #1A1A1A;">4.3 Business Transfers.</strong> If the Company is involved in a merger, acquisition, asset sale, or other business combination, your information may be transferred as part of that transaction. We will provide notice before your information is transferred and becomes subject to a different privacy policy.</p>
                    <p class="mt-3"><strong style="color: #1A1A1A;">4.4 With Your Consent.</strong> We may share your information with third parties when you have given us explicit consent to do so.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">5. Payment Processing</h2>
                    <p>All payment transactions are processed by Stripe, Inc., a third-party payment processor. We do not collect, store, or have access to your full credit card or debit card numbers. When you submit payment information, it is transmitted directly to Stripe using encrypted connections.</p>
                    <p class="mt-3">We receive from Stripe only a payment token, the last four digits of your card, card type, expiration date, and basic billing confirmation information necessary to manage your subscription. Stripe's processing of your payment information is governed exclusively by Stripe's Privacy Policy. We encourage you to review it.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">6. Cookies and Tracking</h2>
                    <p><strong style="color: #1A1A1A;">6.1 What We Use.</strong> We use session cookies and similar technologies that are strictly necessary to operate the Service. These cookies authenticate your session, maintain your logged-in state, and enable core platform functionality. We do not use third-party advertising cookies, behavioral tracking cookies, or cross-site tracking technologies.</p>
                    <p class="mt-3"><strong style="color: #1A1A1A;">6.2 Analytics.</strong> We may use limited analytics tools to understand how the Service is used in aggregate. Any analytics data we collect is used solely to improve the Service and is not shared with advertising networks or data brokers.</p>
                    <p class="mt-3"><strong style="color: #1A1A1A;">6.3 Your Choices.</strong> You may configure your browser to refuse or delete cookies. Please note that disabling session cookies will prevent you from logging in and using the Service. We do not respond to browser Do Not Track signals at this time, as there is no uniform standard for interpreting them.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">7. Data Retention</h2>
                    <p>We retain your account data and associated content for as long as your account remains active or as necessary to provide the Service.</p>
                    <p class="mt-3">If you delete your account, we will permanently delete your personal data and Generated Output within 30 days of your deletion request, except where we are required to retain certain records for legal, regulatory, tax, or billing purposes. Data retained for legal purposes will be stored securely, isolated from active processing, and deleted as soon as the applicable retention obligation expires.</p>
                    <p class="mt-3">Aggregated, de-identified usage data that cannot reasonably be used to identify you may be retained indefinitely for service improvement purposes.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">8. Data Security</h2>
                    <p>We implement commercially reasonable administrative, technical, and physical safeguards to protect your information against unauthorized access, disclosure, alteration, and destruction. These measures include HTTPS encryption for all data in transit, hashed and salted password storage, role-based access controls limiting internal access to personal data, and regular review of security practices.</p>
                    <p class="mt-3">No method of transmission over the internet or method of electronic storage is 100% secure. While we work to protect your information, we cannot guarantee absolute security. If you have reason to believe that your account has been compromised, please notify us immediately at <a href="mailto:info@bestofventures.com" class="underline" style="color: #F5A000;">info@bestofventures.com</a>.</p>
                    <p class="mt-3">In the event of a data breach that affects your personal information and that we are required to report under applicable law, we will notify you in accordance with our legal obligations.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">9. Your Privacy Rights</h2>
                    <p><strong style="color: #1A1A1A;">9.1 General Rights.</strong> Regardless of your location, you may contact us at any time to:</p>
                    <ul class="list-disc list-outside ml-5 mt-3 space-y-2">
                        <li>Request access to the personal information we hold about you;</li>
                        <li>Request correction of inaccurate or incomplete personal information;</li>
                        <li>Request deletion of your personal information, subject to our legal retention obligations;</li>
                        <li>Opt out of non-essential marketing communications.</li>
                    </ul>
                    <p class="mt-3">To exercise any of these rights, email us at <a href="mailto:info@bestofventures.com" class="underline" style="color: #F5A000;">info@bestofventures.com</a>. We will respond to verifiable requests within 30 days. We may need to verify your identity before processing your request.</p>
                    <p class="mt-3"><strong style="color: #1A1A1A;">9.2 California Residents (CCPA).</strong> If you are a California resident, you have rights under the California Consumer Privacy Act (CCPA) and the California Privacy Rights Act (CPRA), including the right to know what personal information we collect and how it is used, the right to delete your personal information, the right to correct inaccurate personal information, and the right to opt out of the sale or sharing of your personal information. We do not sell or share your personal information as those terms are defined under California law. To exercise your California rights, contact us at <a href="mailto:info@bestofventures.com" class="underline" style="color: #F5A000;">info@bestofventures.com</a>. We will not discriminate against you for exercising your privacy rights.</p>
                    <p class="mt-3"><strong style="color: #1A1A1A;">9.3 EU and UK Residents (GDPR and UK GDPR).</strong> If you are located in the European Union or the United Kingdom, you have additional rights under the General Data Protection Regulation (GDPR) or UK GDPR, as applicable. Our legal bases for processing your personal information include the performance of our contract with you, compliance with legal obligations, and our legitimate interests in operating and improving the Service. Your rights include access, rectification, erasure, restriction of processing, data portability, and the right to object to processing. You also have the right to lodge a complaint with your relevant supervisory authority. To exercise your EU or UK rights, contact us at <a href="mailto:info@bestofventures.com" class="underline" style="color: #F5A000;">info@bestofventures.com</a>.</p>
                    <p class="mt-3"><strong style="color: #1A1A1A;">9.4 Other Jurisdictions.</strong> We respect the privacy rights of users in all jurisdictions. If you have questions about your rights under the laws of your country or region, please contact us at <a href="mailto:info@bestofventures.com" class="underline" style="color: #F5A000;">info@bestofventures.com</a> and we will respond as required by applicable law.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">10. Children's Privacy</h2>
                    <p>The Service is intended solely for use by individuals who are 18 years of age or older. It is not directed at, marketed to, or designed for use by children under the age of 13. We do not knowingly collect personal information from children under 13. If we become aware that we have inadvertently collected personal information from a child under 13, we will take prompt steps to delete that information from our systems.</p>
                    <p class="mt-3">If you believe we may have collected information from a child under 13, please contact us immediately at <a href="mailto:info@bestofventures.com" class="underline" style="color: #F5A000;">info@bestofventures.com</a>.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">11. International Data Transfers</h2>
                    <p>The Service is operated in the United States. If you access the Service from outside the United States, your information will be transferred to and processed in the United States, where data protection laws may differ from those in your jurisdiction.</p>
                    <p class="mt-3">If you are located in the EU or UK, we rely on appropriate legal mechanisms for international data transfers, including standard contractual clauses approved by the European Commission where applicable. By using the Service, you consent to the transfer of your information to the United States in accordance with this Privacy Policy.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">12. Third-Party Links and Integrations</h2>
                    <p>The Service may contain links to third-party websites, platforms, or services that are not operated by us. This Privacy Policy does not apply to those third-party services. We are not responsible for the privacy practices of any third party and encourage you to review the privacy policies of any third-party service you access through or in connection with the Service.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">13. Changes to This Privacy Policy</h2>
                    <p>We may update this Privacy Policy from time to time to reflect changes in our data practices, legal requirements, or Service features. When we make material changes, we will notify you by email to the address associated with your account or by prominent notice within the Service at least 14 days before the changes take effect.</p>
                    <p class="mt-3">The date at the top of this page reflects the most recent revision. We encourage you to review this Privacy Policy periodically. Your continued use of the Service after the effective date of any revised Privacy Policy constitutes your acceptance of the changes.</p>
                </section>

                <section>
                    <h2 class="text-base font-bold mb-3" style="color: #1A1A1A;">14. Contact</h2>
                    <p>If you have questions, concerns, or requests relating to this Privacy Policy or our data practices, please contact us:</p>
                    <p class="mt-3">
                        Best of Ventures, Inc.<br>
                        Operating: StoryCreator.Bot™<br>
                        Delray Beach, Florida<br>
                        Privacy requests: <a href="mailto:info@bestofventures.com" class="underline" style="color: #F5A000;">info@bestofventures.com</a><br>
                        Legal inquiries: <a href="mailto:info@bestofventures.com" class="underline" style="color: #F5A000;">info@bestofventures.com</a><br>
                        General support: <a href="mailto:info@bestofventures.com" class="underline" style="color: #F5A000;">info@bestofventures.com</a>
                    </p>
                    <p class="mt-4 text-xs italic" style="color: #888888;">StoryCreator.Bot™ is a trademark of Best of Ventures, Inc. All rights reserved.</p>
                </section>

            </div>
        </main>

        <Footer />

    </div>
</template>
