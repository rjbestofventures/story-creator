<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, ChevronLeft, ChevronRight, ChevronDown, Activity, ShieldAlert, AlertTriangle, FileQuestion, RefreshCw } from '@lucide/vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    tab: String,
    filters: Object,
    requests: Object,
    summary: Object,
    topIps: Array,
    errors: Array,
    retentionDays: Number,
});

const filters = ref({
    status: props.filters?.status ?? '',
    method: props.filters?.method ?? '',
    ip: props.filters?.ip ?? '',
    search: props.filters?.search ?? '',
});

const apply = () => {
    const query = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v));
    router.get(route('admin.logs.index'), query, { preserveState: true, preserveScroll: true });
};

const filterByIp = (ip) => {
    filters.value.ip = ip;
    apply();
};

const reset = () => {
    filters.value = { status: '', method: '', ip: '', search: '' };
    apply();
};

const refresh = () => router.reload({ preserveScroll: true });

const goToPage = (url) => { if (url) router.get(url, {}, { preserveScroll: true }); };

const expanded = ref(null);
const toggle = (id) => { expanded.value = expanded.value === id ? null : id; };

const statusClass = (status, blocked) => {
    if (blocked) return 'bg-red-50 text-red-600';
    if (status >= 500) return 'bg-red-100 text-red-700';
    if (status >= 400) return 'bg-amber-50 text-amber-600';
    if (status >= 300) return 'bg-blue-50 text-blue-600';
    return 'bg-green-50 text-green-700';
};

const levelClass = (level) => ({
    emergency: 'bg-red-100 text-red-700',
    alert: 'bg-red-100 text-red-700',
    critical: 'bg-red-100 text-red-700',
    error: 'bg-red-50 text-red-600',
    warning: 'bg-amber-50 text-amber-600',
}[level] ?? 'bg-gray-100 text-gray-600');

const ago = (iso) => {
    const seconds = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 1000));
    if (seconds < 60) return `${seconds}s ago`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    return `${Math.floor(seconds / 86400)}d ago`;
};

const stamp = (iso) => new Date(iso).toLocaleString();

const cards = [
    { key: 'total', label: 'Requests (24h)', icon: Activity, tone: 'text-[#1A1A1A]' },
    { key: 'not_found', label: '404s (24h)', icon: FileQuestion, tone: 'text-amber-600' },
    { key: 'blocked', label: 'Blocked probes (24h)', icon: ShieldAlert, tone: 'text-red-600' },
    { key: 'errors', label: 'Server errors (24h)', icon: AlertTriangle, tone: 'text-red-700' },
];
</script>

<template>
    <AdminLayout>
        <Head title="Logs — Admin" />

        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-lg font-black text-[#1A1A1A]">Logs</h1>
                <p class="text-xs mt-0.5 text-muted-foreground">Every request to the app and recent application errors. Requests are kept for {{ retentionDays }} days.</p>
            </div>
            <button type="button" class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border border-[#DDDDDD] text-[#555555] transition hover:bg-gray-50 cursor-pointer" @click="refresh">
                <RefreshCw class="w-3.5 h-3.5" /> Refresh
            </button>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <div v-for="card in cards" :key="card.key" class="bg-white rounded-xl border border-[#DDDDDD] px-4 py-3">
                <div class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                    <component :is="card.icon" class="w-3.5 h-3.5" /> {{ card.label }}
                </div>
                <p class="text-2xl font-black mt-1" :class="card.tone">{{ summary[card.key].toLocaleString() }}</p>
            </div>
        </div>

        <div v-if="topIps.length" class="flex flex-wrap items-center gap-2 mb-5 text-xs">
            <span class="font-bold text-muted-foreground uppercase tracking-wider">Busiest IPs (24h)</span>
            <button
                v-for="row in topIps"
                :key="row.ip"
                type="button"
                class="px-2 py-1 rounded-md bg-white border border-[#DDDDDD] font-mono text-[#1A1A1A] hover:border-[#F5A000] cursor-pointer"
                @click="filterByIp(row.ip)"
            >
                {{ row.ip }} <span class="text-muted-foreground">· {{ row.hits }}</span>
            </button>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 mb-4 border-b border-[#DDDDDD]">
            <Link
                v-for="t in [{ key: 'requests', label: 'Requests' }, { key: 'errors', label: 'Application errors' }]"
                :key="t.key"
                :href="route('admin.logs.index', t.key === 'errors' ? { tab: 'errors' } : {})"
                class="relative px-4 py-2.5 text-sm font-semibold"
                :class="tab === t.key ? 'text-[#1A1A1A]' : 'text-[#555555]'"
            >
                {{ t.label }}
                <span v-if="tab === t.key" class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-[#FFC837] to-[#F5A000]" />
            </Link>
        </div>

        <template v-if="tab === 'requests'">
            <!-- Filters -->
            <form class="flex flex-wrap gap-2 mb-4" @submit.prevent="apply">
                <div class="relative flex-1 min-w-[200px]">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                    <Input v-model="filters.search" placeholder="Search path…" class="pl-9" />
                </div>
                <Input v-model="filters.ip" placeholder="IP address" class="w-40" />
                <select v-model="filters.status" class="h-9 rounded-md border border-[#DDDDDD] bg-white px-3 text-sm" @change="apply">
                    <option value="">Any status</option>
                    <option value="2xx">2xx</option>
                    <option value="3xx">3xx</option>
                    <option value="4xx">4xx</option>
                    <option value="5xx">5xx</option>
                    <option value="blocked">Blocked</option>
                </select>
                <select v-model="filters.method" class="h-9 rounded-md border border-[#DDDDDD] bg-white px-3 text-sm" @change="apply">
                    <option value="">Any method</option>
                    <option v-for="m in ['GET', 'POST', 'PATCH', 'PUT', 'DELETE']" :key="m" :value="m">{{ m }}</option>
                </select>
                <button type="submit" class="h-9 px-4 rounded-lg text-sm font-bold text-[#1A1A1A] bg-gradient-to-r from-[#FFC837] to-[#F5A000] cursor-pointer">Filter</button>
                <button type="button" class="h-9 px-4 rounded-lg text-sm font-semibold border border-[#DDDDDD] bg-white text-[#555555] cursor-pointer" @click="reset">Reset</button>
            </form>

            <div class="bg-white rounded-2xl overflow-hidden border border-[#DDDDDD]">
                <div v-for="log in requests.data" :key="log.id" class="border-b border-[#F0F0F0] last:border-b-0">
                    <button type="button" class="w-full flex items-center gap-4 px-5 py-3 text-left hover:bg-[#FAFAF8] cursor-pointer" @click="toggle(log.id)">
                        <span class="shrink-0 w-14 text-center px-1.5 py-0.5 rounded text-[11px] font-bold bg-gray-100 text-gray-600">{{ log.method }}</span>
                        <span class="flex-1 min-w-0 truncate text-sm text-[#1A1A1A]" :title="log.path">{{ log.path }}</span>
                        <span class="shrink-0 px-1.5 py-0.5 rounded text-[11px] font-bold" :class="statusClass(log.status, log.blocked)">{{ log.blocked ? 'BLOCKED' : log.status }}</span>
                        <span class="shrink-0 w-14 text-right text-xs text-[#555555] hidden sm:block">{{ log.duration_ms }}ms</span>
                        <span class="shrink-0 w-16 text-right text-xs text-[#555555]" :title="stamp(log.created_at)">{{ ago(log.created_at) }}</span>
                        <ChevronDown class="shrink-0 w-4 h-4 text-[#AAAAAA] transition-transform" :class="expanded === log.id && 'rotate-180'" />
                    </button>
                    <div v-if="expanded === log.id" class="px-5 pb-4 pt-1 grid sm:grid-cols-2 gap-x-6 gap-y-2 text-xs bg-[#FAFAF8]">
                        <p><span class="font-bold text-muted-foreground">Time</span> <span class="text-[#1A1A1A]">{{ stamp(log.created_at) }}</span></p>
                        <p>
                            <span class="font-bold text-muted-foreground">IP</span>
                            <button type="button" class="font-mono text-[#1A1A1A] underline decoration-dotted cursor-pointer" @click="filterByIp(log.ip)">{{ log.ip }}</button>
                        </p>
                        <p><span class="font-bold text-muted-foreground">User</span> <span class="text-[#1A1A1A]">{{ log.user ? `${log.user.name} (${log.user.email})` : 'Guest' }}</span></p>
                        <p><span class="font-bold text-muted-foreground">Status</span> <span class="text-[#1A1A1A]">{{ log.status }}{{ log.blocked ? ' — stopped by the firewall' : '' }}</span></p>
                        <p class="sm:col-span-2 break-all"><span class="font-bold text-muted-foreground">Path</span> <span class="font-mono text-[#1A1A1A]">{{ log.path }}</span></p>
                        <p class="sm:col-span-2 break-all"><span class="font-bold text-muted-foreground">User agent</span> <span class="text-[#1A1A1A]">{{ log.user_agent || '—' }}</span></p>
                    </div>
                </div>

                <div v-if="requests.data.length === 0" class="px-5 py-16 text-center">
                    <Activity class="w-8 h-8 mx-auto mb-3 text-muted-foreground opacity-40" />
                    <p class="text-sm font-semibold text-[#1A1A1A]">No requests found</p>
                    <p class="text-xs mt-1 text-muted-foreground">Try different filters.</p>
                </div>
            </div>

            <div class="flex items-center justify-between mt-3">
                <p class="text-xs text-muted-foreground">{{ requests.total.toLocaleString() }} {{ requests.total === 1 ? 'request' : 'requests' }}</p>
                <div v-if="requests.last_page > 1" class="flex items-center gap-1">
                    <button :disabled="!requests.prev_page_url" class="h-7 w-7 flex items-center justify-center rounded-lg border border-[#DDDDDD] text-xs hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed" @click="goToPage(requests.prev_page_url)">
                        <ChevronLeft class="w-3.5 h-3.5" />
                    </button>
                    <span class="text-xs text-muted-foreground px-2">{{ requests.current_page }} / {{ requests.last_page }}</span>
                    <button :disabled="!requests.next_page_url" class="h-7 w-7 flex items-center justify-center rounded-lg border border-[#DDDDDD] text-xs hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed" @click="goToPage(requests.next_page_url)">
                        <ChevronRight class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="bg-white rounded-2xl overflow-hidden border border-[#DDDDDD]">
                <div v-for="(entry, i) in errors" :key="i" class="border-b border-[#F0F0F0] last:border-b-0">
                    <button type="button" class="w-full flex items-start gap-4 px-5 py-3 text-left hover:bg-[#FAFAF8] cursor-pointer" @click="toggle(i)">
                        <span class="shrink-0 w-16 text-center px-1.5 py-0.5 rounded text-[11px] font-bold uppercase" :class="levelClass(entry.level)">{{ entry.level }}</span>
                        <span class="flex-1 min-w-0 text-sm text-[#1A1A1A] break-words" :class="expanded !== i && 'line-clamp-2'">{{ entry.message }}</span>
                        <span class="shrink-0 text-xs text-[#555555] whitespace-nowrap">{{ entry.time }}</span>
                    </button>
                    <pre v-if="expanded === i && entry.trace" class="px-5 pb-4 text-[11px] leading-relaxed text-[#555555] whitespace-pre-wrap break-all bg-[#FAFAF8] max-h-96 overflow-y-auto">{{ entry.trace }}</pre>
                </div>
                <div v-if="errors.length === 0" class="px-5 py-16 text-center">
                    <AlertTriangle class="w-8 h-8 mx-auto mb-3 text-muted-foreground opacity-40" />
                    <p class="text-sm font-semibold text-[#1A1A1A]">No application errors logged</p>
                </div>
            </div>
        </template>
    </AdminLayout>
</template>
