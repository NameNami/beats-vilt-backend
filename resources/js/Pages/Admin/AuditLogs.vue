<template>
    <AdminLayout>
        <Head title="System Audit Logs" />
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-2xl font-semibold mb-2 text-gray-900">System Audit Logs</h1>
                <p class="text-slate-600 text-sm font-medium">Track changes, creations, and deletions across the entire system.</p>
            </div>
        </div>

        <!-- Compact Inline Filters -->
        <div class="bg-white rounded-2xl border border-slate-300 p-3 mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 ml-1">
                    <Filter class="w-4 h-4 text-slate-500" />
                </div>

                <!-- Action Dropdown -->
                <div class="relative w-full sm:w-auto" ref="actionDropdownRef">
                    <button
                        @click="isActionDropdownOpen = !isActionDropdownOpen"
                        class="w-full sm:min-w-[160px] inline-flex items-center justify-between text-slate-800 bg-white border border-orange-500 focus:ring-4 focus:ring-orange-500/20 font-medium rounded-xl text-sm px-5 py-2.5 transition-all outline-none cursor-pointer h-[42px]"
                        type="button"
                    >
                        <span class="truncate">{{ selectedActionLabel }}</span>
                        <ChevronDown class="w-4 h-4 ms-2 -me-1 text-slate-400 transition-transform duration-200" :class="{'rotate-180': isActionDropdownOpen}" />
                    </button>

                    <div v-if="isActionDropdownOpen" class="absolute left-0 top-full mt-2 z-30 bg-white border border-slate-300 rounded-xl w-48 overflow-hidden animate-in fade-in zoom-in-95 duration-100">
                        <ul class="p-1.5 text-sm text-slate-700 font-medium max-h-60 overflow-y-auto space-y-1">
                            <li>
                                <button @click="filters.action = ''; applyFilters(); isActionDropdownOpen = false"
                                    class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                    :class="{'text-orange-400 bg-orange-50/50': !filters.action}">
                                    All Actions
                                </button>
                            </li>
                            <li v-for="act in ['created', 'updated', 'deleted']" :key="act">
                                <button @click="filters.action = act; applyFilters(); isActionDropdownOpen = false"
                                    class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                    :class="{'text-orange-400 bg-orange-50/50': filters.action === act}">
                                    {{ act.charAt(0).toUpperCase() + act.slice(1) }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Date Range Dropdown -->
                <div class="relative w-full sm:w-auto" ref="dateRangeDropdownRef">
                    <button
                        @click="isDateRangeDropdownOpen = !isDateRangeDropdownOpen"
                        class="w-full sm:min-w-[160px] inline-flex items-center justify-between text-slate-800 bg-white border border-orange-500 focus:ring-4 focus:ring-orange-500/20 font-medium rounded-xl text-sm px-5 py-2.5 transition-all outline-none cursor-pointer h-[42px]"
                        type="button"
                    >
                        <span class="truncate">{{ selectedDateRangeLabel }}</span>
                        <ChevronDown class="w-4 h-4 ms-2 -me-1 text-slate-400 transition-transform duration-200" :class="{'rotate-180': isDateRangeDropdownOpen}" />
                    </button>

                    <div v-if="isDateRangeDropdownOpen" class="absolute left-0 top-full mt-2 z-30 bg-white border border-slate-300 rounded-xl w-48 overflow-hidden animate-in fade-in zoom-in-95 duration-100">
                        <ul class="p-1.5 text-sm text-slate-700 font-medium max-h-60 overflow-y-auto space-y-1">
                            <li v-for="(label, val) in {'': 'All Time', 'today': 'Today', 'last_7_days': 'Last 7 Days', 'last_30_days': 'Last 30 Days'}" :key="val">
                                <button @click="filters.date_range = val; applyFilters(); isDateRangeDropdownOpen = false"
                                    class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                    :class="{'text-orange-400 bg-orange-50/50': filters.date_range === val}">
                                    {{ label }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <p class="text-slate-500 text-xs font-bold uppercase mx-1"> | </p>

                <!-- Search -->
                <div class="relative flex-1 min-w-[300px]">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input
                        type="text"
                        v-model="filters.search"
                        @input="debouncedSearch"
                        placeholder="Search user, email or model..."
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-orange-500 rounded-xl focus:ring-4 focus:ring-orange-500/20 text-sm outline-none h-[42px] transition-all"
                    >
                </div>

                <button v-if="filters.search || filters.action || filters.date_range" @click="resetFilters" class="text-sm font-bold text-slate-500 hover:text-slate-700 transition cursor-pointer px-2">Reset</button>
            </div>
        </div>

        <div class="bg-white rounded-xl  border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50/50 text-gray-500 text-[11px] uppercase tracking-widest font-bold">
                        <tr>
                            <th class="px-6 py-4 cursor-pointer hover:bg-gray-100 transition group select-none" @click="sortBy('created_at')">
                                <div class="flex items-center gap-1">
                                    Timestamp
                                    <div class="flex flex-col opacity-0 group-hover:opacity-100 transition-opacity" :class="{'opacity-100': filters.sort === 'created_at'}">
                                        <ChevronUp class="w-2.5 h-2.5 -mb-1" :class="{'text-orange-600': filters.sort === 'created_at' && filters.direction === 'asc', 'text-slate-400': filters.sort !== 'created_at' || filters.direction !== 'asc'}" />
                                        <ChevronDown class="w-2.5 h-2.5" :class="{'text-orange-600': filters.sort === 'created_at' && filters.direction === 'desc', 'text-slate-400': filters.sort !== 'created_at' || filters.direction !== 'desc'}" />
                                    </div>
                                </div>
                            </th>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4 cursor-pointer hover:bg-gray-100 transition group select-none" @click="sortBy('action')">
                                <div class="flex items-center gap-1">
                                    Action
                                    <div class="flex flex-col opacity-0 group-hover:opacity-100 transition-opacity" :class="{'opacity-100': filters.sort === 'action'}">
                                        <ChevronUp class="w-2.5 h-2.5 -mb-1" :class="{'text-orange-600': filters.sort === 'action' && filters.direction === 'asc', 'text-slate-400': filters.sort !== 'action' || filters.direction !== 'asc'}" />
                                        <ChevronDown class="w-2.5 h-2.5" :class="{'text-orange-600': filters.sort === 'action' && filters.direction === 'desc', 'text-slate-400': filters.sort !== 'action' || filters.direction !== 'desc'}" />
                                    </div>
                                </div>
                            </th>
                            <th class="px-6 py-4 cursor-pointer hover:bg-gray-100 transition group select-none" @click="sortBy('model_type')">
                                <div class="flex items-center gap-1">
                                    Model
                                    <div class="flex flex-col opacity-0 group-hover:opacity-100 transition-opacity" :class="{'opacity-100': filters.sort === 'model_type'}">
                                        <ChevronUp class="w-2.5 h-2.5 -mb-1" :class="{'text-orange-600': filters.sort === 'model_type' && filters.direction === 'asc', 'text-slate-400': filters.sort !== 'model_type' || filters.direction !== 'asc'}" />
                                        <ChevronDown class="w-2.5 h-2.5" :class="{'text-orange-600': filters.sort === 'model_type' && filters.direction === 'desc', 'text-slate-400': filters.sort !== 'model_type' || filters.direction !== 'desc'}" />
                                    </div>
                                </div>
                            </th>
                            <th class="px-6 py-4">Target ID</th>
                            <th class="px-6 py-4 w-1/3">Changes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-slate-500 font-medium">
                                {{ formatDate(log.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="log.user">
                                    <p class="font-bold text-slate-900">{{ log.user.name }}</p>
                                    <p class="text-xs text-slate-500">{{ log.user.email }}</p>
                                </div>
                                <span v-else class="text-xs font-bold text-slate-400 uppercase tracking-wider">System</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-widest border"
                                    :class="{
                                        'bg-emerald-50 text-emerald-700 border-emerald-200': log.action === 'created',
                                        'bg-blue-50 text-blue-700 border-blue-200': log.action === 'updated',
                                        'bg-rose-50 text-rose-700 border-rose-200': log.action === 'deleted'
                                    }">
                                    {{ log.action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-700 font-medium">
                                {{ formatModel(log.model_type) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-slate-500 text-xs">
                                #{{ log.model_id }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-h-24 overflow-y-auto text-xs space-y-1 pr-2 custom-scrollbar">
                                    <div v-if="log.action === 'updated' && log.new_values" v-for="(val, key) in log.new_values" :key="key" class="flex gap-2 font-mono">
                                        <span class="text-slate-400 font-bold min-w-max">{{ key }}:</span>
                                        <span class="text-rose-500 line-through truncate max-w-[100px]" :title="formatValue(log.old_values?.[key])">{{ formatValue(log.old_values?.[key]) }}</span>
                                        <span class="text-slate-400">→</span>
                                        <span class="text-emerald-600 truncate max-w-[150px]" :title="formatValue(val)">{{ formatValue(val) }}</span>
                                    </div>
                                    <div v-else-if="(log.action === 'created' || log.action === 'deleted') && log.new_values" class="font-mono text-slate-600">
                                        {{ JSON.stringify(log.new_values) }}
                                    </div>
                                    <div v-else-if="log.old_values" class="font-mono text-slate-600">
                                         {{ JSON.stringify(log.old_values) }}
                                    </div>
                                    <div v-else class="text-slate-400 italic">No details</div>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="logs.data.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500 font-medium">No audit logs found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50" v-if="logs.links && logs.links.length > 3">
                <div class="text-sm text-slate-500 font-medium">
                    Showing <span class="font-bold text-slate-900">{{ logs.from || 0 }}</span> to <span class="font-bold text-slate-900">{{ logs.to || 0 }}</span> of <span class="font-bold text-slate-900">{{ logs.total }}</span> logs
                </div>
                <div class="flex gap-1">
                    <template v-for="(link, index) in logs.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="px-3 py-1 text-sm font-medium rounded-md transition-colors"
                            :class="link.active ? 'bg-orange-500 text-white ' : 'text-slate-600 hover:bg-slate-200'"
                            v-html="link.label"
                        />
                        <span v-else class="px-3 py-1 text-sm font-medium text-slate-400 cursor-not-allowed" v-html="link.label"></span>
                    </template>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router, Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue';
import { Filter, ChevronDown, Search, X } from 'lucide-vue-next';

const props = defineProps({
    logs: Object,
    filters: Object,
});

const filters = reactive({
    search: props.filters?.search || '',
    action: props.filters?.action || '',
    date_range: props.filters?.date_range || '',
    sort: props.filters?.sort || 'created_at',
    direction: props.filters?.direction || 'desc',
});

// --- Custom Dropdown State ---
const isActionDropdownOpen = ref(false);
const isDateRangeDropdownOpen = ref(false);
const actionDropdownRef = ref(null);
const dateRangeDropdownRef = ref(null);

const selectedActionLabel = computed(() => {
    if (!filters.action) return 'All Actions';
    return filters.action.charAt(0).toUpperCase() + filters.action.slice(1);
});

const selectedDateRangeLabel = computed(() => {
    const options = {
        '': 'All Time',
        'today': 'Today',
        'last_7_days': 'Last 7 Days',
        'last_30_days': 'Last 30 Days'
    };
    return options[filters.date_range] || 'All Time';
});

const handleClickOutside = (event) => {
    if (actionDropdownRef.value && !actionDropdownRef.value.contains(event.target)) {
        isActionDropdownOpen.value = false;
    }
    if (dateRangeDropdownRef.value && !dateRangeDropdownRef.value.contains(event.target)) {
        isDateRangeDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

const applyFilters = () => {
    router.get('/admin/audit-logs', filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const resetFilters = () => {
    filters.search = '';
    filters.action = '';
    filters.date_range = '';
    applyFilters();
};

const sortBy = (field) => {
    if (filters.sort === field) {
        filters.direction = filters.direction === 'asc' ? 'desc' : 'asc';
    } else {
        filters.sort = field;
        filters.direction = 'desc';
    }
    applyFilters();
};

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(this, args);
        }, wait);
    };
}

const debouncedSearch = debounce(() => {
    applyFilters();
}, 300);

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
};

const formatModel = (modelClass) => {
    if (!modelClass) return '-';
    const parts = modelClass.split('\\');
    return parts[parts.length - 1];
};

const formatValue = (val) => {
    if (val === null || val === undefined) return 'null';
    if (typeof val === 'boolean') return val ? 'true' : 'false';
    if (typeof val === 'object') return '{...}';
    return String(val);
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    height: 4px;
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
</style>
