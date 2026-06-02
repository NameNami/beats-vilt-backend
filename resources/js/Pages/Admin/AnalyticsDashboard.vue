<template>
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-2xl font-semibold mb-2 text-gray-900">System-Wide Analytics</h1>
                </div>
                <button @click="exportGlobalReport" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-sm transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Export Global Report
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-6 rounded-xl border border-gray-200 flex flex-col justify-between">
                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Students</p>
                    <p class="text-3xl font-bold text-slate-900">{{ totalStudents }}</p>
                </div>
                <div class="bg-white p-6 rounded-xl border border-gray-200 flex flex-col justify-between">
                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Courses</p>
                    <p class="text-3xl font-bold text-slate-900">{{ totalCourses }}</p>
                </div>
                <div class="bg-white p-6 rounded-xl border border-gray-200 flex flex-col justify-between">
                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Sessions</p>
                    <p class="text-3xl font-bold text-slate-900">{{ totalSessions }}</p>
                </div>
                <div class="p-6 rounded-xl border border-rose-200 relative overflow-hidden">
                    <p class="text-sm font-semibold text-rose-600 uppercase tracking-wider mb-1">Global At-Risk</p>
                    <p class="text-3xl font-bold text-rose-700">{{ atRiskStudents.length }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-white flex items-center gap-3">
                    <div class="w-1.5 h-4 bg-orange-500 rounded-full"></div>
                    <h2 class="text-lg font-bold text-slate-900">University-Wide Intervention Required</h2>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50/50 text-gray-500 text-[11px] uppercase tracking-widest font-bold">
                    <tr>
                        <th class="px-6 py-4">Student Name</th>
                        <th class="px-6 py-4">Student ID</th>
                        <th class="px-6 py-4">Total Missed</th>
                        <th class="px-6 py-4">Overall %</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                    <tr v-for="student in atRiskStudents" :key="student.id" class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4 font-bold text-slate-900">{{ student.name }}</td>
                        <td class="px-6 py-4 text-sky-600 font-medium">{{ student.student_id }}</td>
                        <td class="px-6 py-4 text-rose-600 font-bold">{{ student.missed_total }} sessions</td>
                        <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold tracking-widest border"
                                      :class="student.percentage < 50 ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-orange-50 text-orange-700 border-orange-200'">
                                    {{ student.percentage }}%
                                </span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    totalStudents: Number,
    totalCourses: Number,
    totalSessions: Number,
    arrivalStats: Object,
    atRiskStudents: Array
});

const exportGlobalReport = () => {
    alert("In a production environment, this will trigger the CSV/PDF export download.");
};
</script>
