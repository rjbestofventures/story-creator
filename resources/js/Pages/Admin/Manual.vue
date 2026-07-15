<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ShieldCheck, Users, BookOpen, LogIn, KeyRound, Trash2, Search, FileText, MessageSquare } from '@lucide/vue';
</script>

<template>
    <Head title="Admin Manual — StoryCreator.Bot" />

    <div class="min-h-screen" style="background-color: #FAFAF8;">

        <!-- Top bar -->
        <div class="bg-white border-b px-4 md:px-8 py-4" style="border-color: #DDDDDD;">
            <div class="max-w-3xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;" class="font-black text-lg">StoryCreator</span>
                    <span class="font-black text-lg" style="color: #1A1A1A;">.Bot</span>
                    <span class="text-sm ml-1" style="color: #888888;">Admin Manual</span>
                </div>
                <Link :href="route('admin.users.index')" class="text-xs transition hover:opacity-70" style="color: #555555;">
                    ← Back to Admin
                </Link>
            </div>
        </div>

        <div class="max-w-3xl mx-auto px-4 md:px-8 py-12 space-y-12">

            <!-- Page title -->
            <div>
                <h1 class="text-4xl font-black mb-3" style="color: #1A1A1A;">
                    <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>.Bot Admin Manual
                </h1>
                <p class="text-base leading-relaxed" style="color: #555555;">
                    This guide covers administrative tools and workflows for managing users, stories, and platform settings.
                </p>
            </div>

            <!-- Admin Guide -->
            <section>
                <div class="flex items-center gap-2 mb-5">
                    <ShieldCheck class="w-5 h-5" style="color: #F5A000;" />
                    <h2 class="text-xl font-black" style="color: #1A1A1A;">Admin Guide</h2>
                </div>

                <div class="space-y-4">

                    <!-- Panel Overview -->
                    <div class="bg-white rounded-2xl p-6" style="border: 1px solid #DDDDDD;">
                        <h3 class="font-bold text-base mb-3" style="color: #1A1A1A;">Admin Panel Overview</h3>
                        <p class="text-sm mb-3 leading-relaxed" style="color: #555555;">
                            The Admin Panel is accessible at <code class="px-1.5 py-0.5 rounded text-xs font-mono" style="background: #F5F5F5; color: #1A1A1A;">/admin</code> for users with the <strong>admin</strong> role. It has five tabs:
                        </p>
                        <ul class="space-y-2 text-sm" style="color: #555555;">
                            <li class="flex gap-2"><span class="font-bold shrink-0" style="color: #1A1A1A;">Users & Plans</span> — View, search, create, and manage all users, roles, subscription plans, and credit balances.</li>
                            <li class="flex gap-2"><span class="font-bold shrink-0" style="color: #1A1A1A;">Manage Plans</span> — Create and edit subscription plans, pricing, chapter limits, and story/refine credit allowances.</li>
                            <li class="flex gap-2"><span class="font-bold shrink-0" style="color: #1A1A1A;">All Stories</span> — View every story created on the platform, with status indicators and AI refine usage per story.</li>
                            <li class="flex gap-2"><span class="font-bold shrink-0" style="color: #1A1A1A;">Usage & Billing</span> — Aggregate billing and usage stats across all users.</li>
                            <li class="flex gap-2"><span class="font-bold shrink-0" style="color: #1A1A1A;">Settings</span> — Platform-level configuration including landing page access lock.</li>
                        </ul>
                    </div>

                    <!-- Creating Users -->
                    <div class="bg-white rounded-2xl p-6" style="border: 1px solid #DDDDDD;">
                        <h3 class="font-bold text-base mb-3" style="color: #1A1A1A;">Creating Users</h3>
                        <p class="text-sm mb-3 leading-relaxed" style="color: #555555;">
                            Click <strong>"Add User"</strong> in the Users & Plans tab to create a new account directly. You'll set:
                        </p>
                        <ul class="space-y-1.5 text-sm mb-4" style="color: #555555;">
                            <li>• <strong>Full Name</strong> and <strong>Email</strong></li>
                            <li>• <strong>Role</strong> — User or Admin</li>
                        </ul>
                        <p class="text-sm leading-relaxed" style="color: #555555;">
                            A password is auto-generated and a <strong>secure login link</strong> is sent to the user's email automatically. The account is created with email verified, so the user can set their password and log in immediately.
                        </p>
                    </div>

                    <!-- Managing Users -->
                    <div class="bg-white rounded-2xl p-6" style="border: 1px solid #DDDDDD;">
                        <h3 class="font-bold text-base mb-3" style="color: #1A1A1A;">Managing Users</h3>
                        <p class="text-sm mb-3" style="color: #555555;">From the <strong>Users & Plans</strong> tab, click any user row to expand their management panel. You can:</p>
                        <ul class="space-y-2 text-sm mb-4" style="color: #555555;">
                            <li class="flex items-start gap-2">
                                <Search class="w-3.5 h-3.5 shrink-0 mt-0.5" style="color: #F5A000;" />
                                <span><strong>Search</strong> users by name, email, or role</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <BookOpen class="w-3.5 h-3.5 shrink-0 mt-0.5" style="color: #F5A000;" />
                                <span><strong>Assign a plan</strong> — sets story credits, refine credits, and chapter limits</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <Users class="w-3.5 h-3.5 shrink-0 mt-0.5" style="color: #F5A000;" />
                                <span><strong>Edit profile</strong> — update name, email, and role via the profile icon</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <KeyRound class="w-3.5 h-3.5 shrink-0 mt-0.5" style="color: #F5A000;" />
                                <span><strong>Reset password</strong> — click the key icon to set a new password manually</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <LogIn class="w-3.5 h-3.5 shrink-0 mt-0.5" style="color: #F5A000;" />
                                <span><strong>Impersonate</strong> — click the login icon to view the platform as that user; a gold banner will appear. Click "Switch to your Account" to return to your admin session.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <Trash2 class="w-3.5 h-3.5 shrink-0 mt-0.5" style="color: #F5A000;" />
                                <span><strong>Delete a user</strong> — permanently removes their account and all associated stories</span>
                            </li>
                        </ul>
                        <p class="text-sm" style="color: #888888;">Story credits refresh monthly. Refine credits accumulate and never expire.</p>
                    </div>

                    <!-- All Stories -->
                    <div class="bg-white rounded-2xl p-6" style="border: 1px solid #DDDDDD;">
                        <h3 class="font-bold text-base mb-3" style="color: #1A1A1A;">All Stories</h3>
                        <p class="text-sm mb-3 leading-relaxed" style="color: #555555;">
                            The <strong>All Stories</strong> tab shows every story created on the platform. Each row displays:
                        </p>
                        <ul class="space-y-1.5 text-sm mb-4" style="color: #555555;">
                            <li>• <strong>Story ID</strong> with an <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-600">In Progress</span> badge if the interview is not yet complete</li>
                            <li>• <strong>User Name</strong> and <strong>Email</strong> — click the user name to filter to that user's stories</li>
                            <li>• <strong>Date Generated</strong> — when the story was created</li>
                            <li>• <strong>AI Refine Used</strong> — number of chapter regenerations consumed</li>
                        </ul>
                        <p class="text-sm leading-relaxed" style="color: #555555;">
                            Click any story row to open a read-only view of all its chapters. The table is paginated at 25 stories per page.
                        </p>
                    </div>

                    <!-- Manage Plans -->
                    <div class="bg-white rounded-2xl p-6" style="border: 1px solid #DDDDDD;">
                        <h3 class="font-bold text-base mb-3" style="color: #1A1A1A;">Managing Plans</h3>
                        <p class="text-sm mb-3 leading-relaxed" style="color: #555555;">
                            The <strong>Manage Plans</strong> tab lets you create and edit subscription plans. Each plan defines:
                        </p>
                        <ul class="space-y-1.5 text-sm mb-4" style="color: #555555;">
                            <li>• <strong>Stories per month</strong> — credits granted at the start of each billing cycle</li>
                            <li>• <strong>Chapter limit</strong> — max chapters generated per story</li>
                            <li>• <strong>Refine credits</strong> — monthly allowance added on top of existing balance (accumulates)</li>
                            <li>• <strong>Pricing</strong> — monthly and yearly rates</li>
                            <li>• <strong>Trial months</strong> — free trial period before billing begins</li>
                        </ul>
                        <p class="text-sm" style="color: #888888;">Changes to a plan's credits only apply to new subscriptions and the next renewal cycle — existing balances are not retroactively adjusted.</p>
                    </div>

                </div>
            </section>

            <!-- Administrative Contact -->
            <section>
                <div class="flex items-center gap-2 mb-5">
                    <Users class="w-5 h-5" style="color: #F5A000;" />
                    <h2 class="text-xl font-black" style="color: #1A1A1A;">Administrative Contact</h2>
                </div>

                <div class="rounded-2xl p-6" style="background-color: #FEF9EC; border: 1px solid #F5E4A0;">
                    <p class="text-sm mb-3 leading-relaxed" style="color: #1A1A1A;">
                        <strong>GGC Virtual Assistants</strong> is the administrative team managing the StoryCreator.Bot platform.
                    </p>
                    <p class="text-sm mb-3 leading-relaxed" style="color: #555555;">
                        If you need any assistance with technical support, account issues, platform questions, or feature requests, please reach out via the <strong>Discord group chat</strong> that you're already a part of.
                    </p>
                    <p class="text-xs" style="color: #888888;">Discord is the primary communication channel for all administrative matters.</p>
                </div>
            </section>

        </div>

        <!-- Footer -->
        <footer class="bg-white border-t py-5 text-center" style="border-color: #DDDDDD;">
            <p class="text-xs" style="color: #555555;">© 2026 StoryCreator.Bot. All rights reserved.</p>
        </footer>

    </div>
</template>
