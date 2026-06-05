<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Sparkles, ShieldCheck, BookOpen, User, LogOut, ChevronDown, UserCheck } from 'lucide-vue-next';

const page  = usePage();
const user  = computed(() => page.props.auth.user);
const isAdmin = computed(() => page.props.auth.user?.roles?.includes('admin') ?? false);
const impersonating = computed(() => page.props.impersonating ?? null);

const menuOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-[#FAFAF8]">

        <!-- Top nav -->
        <nav class="bg-white border-b border-[#DDDDDD] sticky top-0 z-40">
            <div class="max-w-5xl mx-auto px-4 md:px-8">
                <div class="flex items-center justify-between h-14">

                    <!-- Logo -->
                    <Link :href="route('welcome')" class="flex items-center gap-2 cursor-pointer">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#FFC837] to-[#F5A000] flex items-center justify-center">
                            <Sparkles class="w-4 h-4 text-white" />
                        </div>
                        <span class="font-black text-[#1A1A1A] text-base tracking-tight">
                            Story<span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Creator</span>
                        </span>
                    </Link>

                    <!-- Right side -->
                    <div class="flex items-center gap-2">

                        <!-- Admin link -->
                        <Link
                            v-if="isAdmin"
                            :href="route('admin.users.index')"
                            class="hidden md:flex items-center gap-1.5 text-xs font-semibold px-3 h-8 rounded-lg text-[#555555] hover:text-[#1A1A1A] hover:bg-gray-100 transition-colors cursor-pointer"
                        >
                            <ShieldCheck class="w-3.5 h-3.5 text-[#F5A000]" />
                            Admin
                        </Link>

                        <!-- User menu -->
                        <div class="relative">
                            <button
                                type="button"
                                @click="menuOpen = !menuOpen"
                                class="flex items-center gap-2 h-9 px-3 rounded-xl hover:bg-gray-100 transition-colors cursor-pointer"
                            >
                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-[#FFC837] to-[#F5A000] flex items-center justify-center">
                                    <span class="text-white text-xs font-bold">
                                        {{ user?.name?.charAt(0)?.toUpperCase() }}
                                    </span>
                                </div>
                                <span class="hidden md:block text-sm font-semibold text-[#1A1A1A] max-w-[120px] truncate">
                                    {{ user?.name }}
                                </span>
                                <ChevronDown class="w-3.5 h-3.5 text-[#555555]" />
                            </button>

                            <!-- Dropdown -->
                            <div
                                v-if="menuOpen"
                                @click.away="menuOpen = false"
                                class="absolute right-0 top-full mt-1.5 w-48 bg-white rounded-xl border border-[#DDDDDD] shadow-lg py-1 z-50"
                            >
                                <div class="px-3 py-2 border-b border-[#DDDDDD]">
                                    <p class="text-xs font-semibold text-[#1A1A1A] truncate">{{ user?.name }}</p>
                                    <p class="text-xs text-[#555555] truncate">{{ user?.email }}</p>
                                </div>
                                <Link
                                    :href="route('stories.index')"
                                    @click="menuOpen = false"
                                    class="flex items-center gap-2 px-3 py-2 text-sm text-[#555555] hover:text-[#1A1A1A] hover:bg-gray-50 transition-colors cursor-pointer"
                                >
                                    <BookOpen class="w-4 h-4" />
                                    My Stories
                                </Link>
                                <Link
                                    :href="route('profile.edit')"
                                    @click="menuOpen = false"
                                    class="flex items-center gap-2 px-3 py-2 text-sm text-[#555555] hover:text-[#1A1A1A] hover:bg-gray-50 transition-colors cursor-pointer"
                                >
                                    <User class="w-4 h-4" />
                                    Profile
                                </Link>
                                <div class="border-t border-[#DDDDDD] mt-1 pt-1">
                                    <Link
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        @click="menuOpen = false"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-500 hover:bg-red-50 transition-colors cursor-pointer"
                                    >
                                        <LogOut class="w-4 h-4" />
                                        Log Out
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Click-away overlay -->
        <div v-if="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30" />

        <!-- Impersonation banner -->
        <div
            v-if="impersonating"
            class="sticky top-14 z-30 flex items-center justify-between gap-3 px-4 md:px-8 py-2.5"
            style="background: linear-gradient(to right, #FFC837, #F5A000);"
        >
            <div class="flex items-center gap-2 text-sm font-semibold text-[#1A1A1A]">
                <UserCheck class="w-4 h-4 shrink-0" />
                Viewing as <span class="font-black">{{ user?.name }}</span>
                <span class="font-normal opacity-70">— logged in as {{ impersonating.admin_name }}</span>
            </div>
            <Link
                :href="route('impersonate.stop')"
                method="post"
                as="button"
                class="text-xs font-bold px-3 py-1.5 rounded-lg transition hover:opacity-80 cursor-pointer shrink-0"
                style="background: rgba(0,0,0,0.15); color: #1A1A1A;"
            >
                Stop Impersonating
            </Link>
        </div>

        <!-- Page content -->
        <main>
            <slot />
        </main>

    </div>
</template>
