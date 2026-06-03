<template>
    <AdminLayout>
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-2xl font-semibold mb-2 text-gray-900">System Audit Logs</h1>
                <p class="text-slate-600 text-sm font-medium">Track changes, creations, and deletions across the entire system.</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6 p-4">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full relative">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Search User or Model</label>
                    <div class="relative">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input 
                            type="text" 
                            v-model="filters.search"
                            @input="debouncedSearch"
                            placeholder="Search by name, email or model type..." 
                            class="w-full pl-10 pr-4 py-2.5 bg-[#f8fafc] border-gray-200 rounded-lg focus:ring-orange-500 focus:border-orange-500 text-sm"
                        >
                    </div>
                </div>
                
                <div class="w-full md:w-48">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Action</label>
                    <select 
                        v-model="filters.action"
                        @change="applyFilters"
                        class="w-full bg-[#f8fafc] border-gray-200 rounded-lg focus:ring-orange-500 focus:border-orange-500 text-sm py-2.5 font-medium"
                    >
                        <option value="">All Actions</option>
                        <option value="created">Created</option>
                        <option value="updated">Updated</option>
                        <option value="deleted">Deleted</option>
                    </select>
                </div>
                
                <div class="w-full md:w-48">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Date Range</label>
                    <select 
                        v-model="filters.date_range"
                        @change="applyFilters"
                        class="w-full bg-[#f8fafc] border-gray-200 rounded-lg focus:ring-orange-500 focus:border-orange-500 text-sm py-2.5 font-medium"
                    >
                        <option value="">All Time</option>
                        <option value="today">Today</option>
                        <option value="last_7_days">Last 7 Days</option>
                        <option value="last_30_days">Last 30 Days</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50/50 text-gray-500 text-[11px] uppercase tracking-widest font-bold">
                        <tr>
                            <th class="px-6 py-4 cursor-pointer hover:bg-gray-100 transition" @click="sortBy('created_at')">
                                <div class="flex items-center gap-1">
                                    Timestamp
                                    <svg v-if="filters.sort === 'created_at'" class="w-3 h-3" :class="filters.direction === 'asc' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </th>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4 cursor-pointer hover:bg-gray-100 transition" @click="sortBy('action')">
                                <div class="flex items-center gap-1">
                                    Action
                                    <svg v-if="filters.sort === 'action'" class="w-3 h-3" :class="filters.direction === 'asc' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </th>
                            <th class="px-6 py-4 cursor-pointer hover:bg-gray-100 transition" @click="sortBy('model_type')">
                                <div class="flex items-center gap-1">
                                    Model
                                    <svg v-if="filters.sort === 'model_type'" class="w-3 h-3" :class="filters.direction === 'asc' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
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
                            :class="link.active ? 'bg-orange-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200'"
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
import { Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

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

const applyFilters = () => {
    router.get('/admin/audit-logs', filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
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
