<script setup>
import { onMounted } from 'vue';
import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';

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
        <DialogContent class="max-w-xl sm:max-w-xl w-full p-0 gap-0 overflow-hidden bg-transparent ring-0 shadow-none">
            <!-- The survey itself renders its own branded title, so this is for screen readers only. -->
            <DialogTitle class="sr-only">Send Feedback</DialogTitle>
            <div class="max-h-[85vh] overflow-y-auto">
                <iframe
                    v-if="open"
                    src="https://link.bestofventures.com/widget/survey/Q5TJofZcNEpDYZW2PmBP"
                    style="border:none;width:100%;display:block;"
                    scrolling="no"
                    id="Q5TJofZcNEpDYZW2PmBP"
                    title="survey"
                />
            </div>
        </DialogContent>
    </Dialog>
</template>
