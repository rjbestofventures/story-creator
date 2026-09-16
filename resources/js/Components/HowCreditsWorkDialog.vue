<script setup>
import { Zap, RotateCcw, AlignLeft } from 'lucide-vue-next';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';

const open = defineModel('open', { default: false });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg max-h-[90vh] overflow-y-auto gap-0">
            <DialogHeader>
                <DialogTitle class="text-2xl font-black text-[#1A1A1A]">How credits work</DialogTitle>
            </DialogHeader>

            <div class="space-y-3 mt-5">
                <div class="bg-white rounded-2xl border border-[#DDDDDD] p-4 flex items-start gap-4">
                    <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center bg-amber-50">
                        <Zap class="w-5 h-5 text-[#F5A000]" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-[#1A1A1A]">Generating a story</p>
                        <p class="text-sm text-[#555555] leading-relaxed mt-1">
                            A story is told across 12 episodes. Each episode costs <strong class="font-bold text-[#1A1A1A]">1 credit</strong> to generate, so a complete 12-episode story costs <strong class="font-bold text-[#1A1A1A]">12 credits</strong> total — charged one episode at a time, never all up front.
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-[#DDDDDD] p-4 flex items-start gap-4">
                    <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center bg-amber-50">
                        <RotateCcw class="w-5 h-5 text-[#F5A000]" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-[#1A1A1A]">Refining episodes</p>
                        <p class="text-sm text-[#555555] leading-relaxed mt-1">
                            Refining an episode also costs <strong class="font-bold text-[#1A1A1A]">1 credit</strong>. Fix a single episode for 1 credit — or apply the same change across all 12 episodes for <strong class="font-bold text-[#1A1A1A]">12 credits</strong> (1 credit × 12 episodes).
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-[#DDDDDD] p-4 flex items-start gap-4">
                    <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center bg-amber-50">
                        <AlignLeft class="w-5 h-5 text-[#F5A000]" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-[#1A1A1A]">Your credit pool</p>
                        <p class="text-sm text-[#555555] leading-relaxed mt-1">
                            Credits don't expire per story — whatever's left over rolls over and can be used for your next story or for more edits.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-[#F5F5F5] rounded-2xl p-5 mt-5">
                <p class="text-xs font-bold tracking-wider text-[#AAAAAA] uppercase mb-3">Example</p>
                <div class="flex items-baseline justify-between gap-4">
                    <span class="text-sm text-[#555555]">Starting pool</span>
                    <span class="text-sm font-bold text-[#1A1A1A] shrink-0">48 credits</span>
                </div>
                <div class="flex items-baseline justify-between gap-4 mt-2">
                    <span class="text-sm text-[#555555]">One complete 12-episode story</span>
                    <span class="text-sm font-bold text-[#1A1A1A] shrink-0">−12 credits</span>
                </div>
                <div class="border-t border-[#DDDDDD] mt-3 pt-3 flex items-baseline justify-between gap-4">
                    <span class="text-sm font-bold text-[#1A1A1A]">Left in your pool</span>
                    <span class="text-sm font-bold text-[#F5A000] shrink-0">36 credits</span>
                </div>
                <p class="text-xs text-[#AAAAAA] mt-3">
                    Use those 36 credits toward a new story, or toward refining episodes in this one.
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
