<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Lock, ArrowRight } from '@lucide/vue';

const form = useForm({ password: '' });

const submit = () => form.post(route('landing.unlock.submit'));
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen flex flex-col items-center justify-center px-4" style="background: radial-gradient(ellipse at 30% 50%, #FEF9EC 0%, #F5F5F0 55%, #EBEBE6 100%);">

        <!-- Logo -->
        <Link href="/" class="flex items-center text-xl font-bold tracking-tight mb-12">
            <span style="background: linear-gradient(to right, #FFC837, #F5A000); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">StoryCreator</span>
            <span style="color: #1A1A1A;">.Bot</span>
        </Link>

        <div class="w-full max-w-sm">
            <div class="rounded-2xl p-8 text-center" style="background:#FFFFFF; border:1px solid #DDDDDD; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">

                <!-- Icon -->
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-5" style="background:#FEF9EC;">
                    <Lock class="w-7 h-7" style="color:#F5A000;" />
                </div>

                <h1 class="text-xl font-black mb-1" style="color: #1A1A1A;">This site is private</h1>
                <p class="text-sm leading-relaxed mb-6" style="color: #555555;">
                    Enter the access password to continue.
                </p>

                <form @submit.prevent="submit" class="space-y-4 text-left" novalidate>
                    <div>
                        <label for="password" class="block text-sm font-semibold mb-1.5" style="color:#1A1A1A;">Password</label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color:#AAAAAA;" />
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                autofocus
                                required
                                placeholder="Enter access password"
                                class="w-full pl-9 pr-4 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                                style="border:1px solid #DDDDDD; color:#1A1A1A; background:#FFFFFF;"
                                :style="form.errors.password ? 'border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,0.1)' : ''"
                                @focus="(e) => !form.errors.password && (e.target.style.borderColor='#F5A000', e.target.style.boxShadow='0 0 0 3px rgba(245,160,0,0.15)')"
                                @blur="(e) => !form.errors.password && (e.target.style.borderColor='#DDDDDD', e.target.style.boxShadow='none')"
                            />
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs" style="color:#EF4444;">{{ form.errors.password }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg font-bold text-sm transition-opacity duration-200 cursor-pointer"
                        :class="{ 'opacity-60 cursor-not-allowed': form.processing }"
                        style="background: linear-gradient(to right, #FFC837, #F5A000); color: #1A1A1A;"
                    >
                        <span v-if="form.processing">Verifying…</span>
                        <template v-else>
                            Enter site <ArrowRight class="w-4 h-4" :stroke-width="2.5" />
                        </template>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
