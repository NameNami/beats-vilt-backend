<template>
    <Head title="Admin Leave Management" />
    <AdminLayout>
        <div class="max-w-6xl mx-auto space-y-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-semibold mb-2 text-gray-900">Global Leave Management</h1>
                    <p class="text-slate-600 text-sm font-medium">Override lecturer approvals and submit bulk leave applications for students.</p>
                </div>
            </div>

            <div v-if="$page.props.flash?.success" class="p-4 bg-teal-50 text-teal-800 rounded-xl border border-teal-100 font-bold flex items-center gap-3">
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ $page.props.flash.success }}
            </div>

            <div v-if="$page.props.errors?.message" class="p-4 bg-red-50 text-red-800 rounded-xl border border-red-100 font-bold flex items-center gap-3">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                {{ $page.props.errors.message }}
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Apply Leave Card -->
                <div class="lg:col-span-1">
                    <form @submit.prevent="submitLeave" class="bg-white rounded-2xl border border-slate-200 overflow-hidden  sticky top-6">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                Apply Global Leave
                            </h2>
                        </div>
                        <div class="p-6 space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Student Search</label>
                                <div class="flex gap-2">
                                    <input v-model="searchQuery" @keyup.enter="searchStudents" type="text" class="w-full bg-[#f8fafc] border-slate-200 rounded-xl focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5" placeholder="Name or ID...">
                                    <button type="button" @click="searchStudents" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-xl transition cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Select Student</label>
                                <select v-model="form.user_id" class="w-full bg-[#f8fafc] border-slate-200 rounded-xl focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5 font-medium cursor-pointer">
                                    <option value="">Select from results...</option>
                                    <option v-for="student in students" :key="student.id" :value="student.id">
                                        {{ student.name }} ({{ student.student_id }})
                                    </option>
                                </select>
                                <p v-if="form.errors.user_id" class="text-red-500 text-xs mt-1">{{ form.errors.user_id }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Start Date</label>
                                    <input v-model="form.start_date" type="date" class="w-full bg-[#f8fafc] border-slate-200 rounded-xl focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5 cursor-pointer">
                                    <p v-if="form.errors.start_date" class="text-red-500 text-xs mt-1">{{ form.errors.start_date }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">End Date</label>
                                    <input v-model="form.end_date" type="date" class="w-full bg-[#f8fafc] border-slate-200 rounded-xl focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5 cursor-pointer">
                                    <p v-if="form.errors.end_date" class="text-red-500 text-xs mt-1">{{ form.errors.end_date }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Leave Type</label>
                                <select v-model="form.type" class="w-full bg-[#f8fafc] border-slate-200 rounded-xl focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5 font-medium cursor-pointer">
                                    <option value="medical">Medical</option>
                                    <option value="emergency">Emergency</option>
                                    <option value="other">Other</option>
                                </select>
                                <p v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Reason</label>
                                <textarea v-model="form.reason" rows="3" class="w-full bg-[#f8fafc] border-slate-200 rounded-xl focus:ring-orange-600 focus:border-orange-600 text-sm py-2.5 resize-none" placeholder="Provide reason for global override..."></textarea>
                                <p v-if="form.errors.reason" class="text-red-500 text-xs mt-1">{{ form.errors.reason }}</p>
                            </div>

                            <div class="pt-2">
                                <button type="submit" :disabled="form.processing" class="w-full bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-xl font-bold transition disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    {{ form.processing ? 'Applying...' : 'Override & Apply' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- History Log -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden ">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                Recent Overrides
                            </h2>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-100 text-[10px] uppercase tracking-widest text-slate-500 font-bold">
                                        <th class="px-6 py-3">Student</th>
                                        <th class="px-6 py-3">Type</th>
                                        <th class="px-6 py-3">Reason</th>
                                        <th class="px-6 py-3">Date Applied</th>
                                        <th class="px-6 py-3 text-right">Reviewer</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="leave in recentLeaves" :key="leave.id" class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-900 text-sm">{{ leave.student_name }}</div>
                                            <div class="text-[10px] text-slate-500 font-medium">{{ leave.student_id }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider" :class="{
                                                'bg-rose-50 text-rose-600 border border-rose-100': leave.type === 'medical',
                                                'bg-amber-50 text-amber-600 border border-amber-100': leave.type === 'emergency',
                                                'bg-slate-100 text-slate-600 border border-slate-200': leave.type === 'other'
                                            }">
                                                {{ leave.type }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-xs text-slate-600 max-w-[200px] truncate" :title="leave.reason">{{ leave.reason }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-slate-500">
                                            {{ new Date(leave.created_at).toLocaleDateString() }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="text-xs font-bold text-slate-800">{{ leave.reviewer_name }}</div>
                                        </td>
                                    </tr>
                                    <tr v-if="recentLeaves.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-slate-500 font-medium">
                                            No approved leaves found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    students: Array,
    recentLeaves: Array
});

const searchQuery = ref('');

const searchStudents = () => {
    router.get(route('admin.leave.index'), { search: searchQuery.value }, { preserveState: true, preserveScroll: true });
};

const form = useForm({
    user_id: '',
    type: 'medical',
    reason: '',
    start_date: '',
    end_date: '',
});

const submitLeave = () => {
    if (!confirm('Are you sure? This will forcefully override the attendance records for all scheduled classes within this date range.')) {
        return;
    }

    form.post(route('admin.leave.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('reason', 'start_date', 'end_date');
        }
    });
};
</script>
