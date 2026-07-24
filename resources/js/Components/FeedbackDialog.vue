<script setup>
import { onMounted } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

const open = defineModel('open', { default: false });

const FORM_EMBED_SRC = 'https://link.bestofventures.com/js/form_embed.js';

onMounted(() => {
    if (document.querySelector(`script[src="${FORM_EMBED_SRC}"]`)) return;
    const script = document.createElement('script');
    script.src = FORM_EMBED_SRC;
    document.body.appendChild(script);
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-lg p-0 overflow-hidden">
            <DialogHeader class="p-4 pb-0">
                <DialogTitle class="text-[#1A1A1A]">Send Feedback</DialogTitle>
            </DialogHeader>
            <iframe
                v-if="open"
                src="https://link.bestofventures.com/widget/survey/Q5TJofZcNEpDYZW2PmBP"
                style="border:none;width:100%;min-height:70vh;"
                scrolling="no"
                id="Q5TJofZcNEpDYZW2PmBP"
                title="survey"
            />
        </DialogContent>
    </Dialog>
</template>
