<script setup>
import { Link } from '@inertiajs/vue3';

const year = new Date().getFullYear();
</script>

<template>
    <footer class="bg-white px-6 md:px-10 py-5 flex flex-col md:flex-row items-center justify-between gap-3" style="border-top: 1px solid #DDDDDD;">
        <p class="text-xs text-center md:text-left" style="color: #555555;">
            © {{ year }} StoryCreator.Bot. A Best Of Ventures Technology. All rights reserved.
        </p>
        <nav class="flex items-center gap-6">
            <Link :href="route('terms')" class="text-xs transition hover:opacity-70" style="color: #555555;">Terms</Link>
            <Link :href="route('privacy')" class="text-xs transition hover:opacity-70" style="color: #555555;">Privacy</Link>
        </nav>
    </footer>
</template>
