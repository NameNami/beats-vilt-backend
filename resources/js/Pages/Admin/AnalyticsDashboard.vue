<template>
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex justify-between items-end mb-8">
                <div>
                    <p class="text-xs font-bold tracking-widest text-rose-700 uppercase mb-2">Performance Metrics</p>
                    <h2 class="text-4xl font-black text-slate-900 tracking-tight leading-tight">
                        System-Wide <span class="text-rose-600 italic">Analytics</span>
                    </h2>
                    <p class="text-slate-600 mt-3">Macro-level overview of university attendance and at-risk student intervention tracking.</p>
                </div>
                <button @click="exportGlobalReport" class="bg-amber-700 hover:bg-amber-800 text-white px-6 py-3 rounded-xl font-bold shadow-sm transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Export Global Report
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Total Students</p>
                    <p class="text-3xl font-black text-slate-900 mt-2">{{ totalStudents }}</p>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Total Courses</p>
                    <p class="text-3xl font-black text-sky-600 mt-2">{{ totalCourses }}</p>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Total Sessions</p>
                    <p class="text-3xl font-black text-teal-600 mt-2">{{ totalSessions }}</p>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-rose-100 bg-rose-50/30 flex flex-col justify-between">
                    <p class="text-[10px] text-rose-500 font-bold uppercase tracking-widest">Global At-Risk</p>
                    <p class="text-3xl font-black text-rose-700 mt-2">{{ atRiskStudents.length }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-white flex items-center gap-3">
                    <div class="w-2 h-6 bg-rose-500 rounded-full"></div>
                    <h2 class="text-lg font-bold text-slate-800">University-Wide Intervention Required (< 80%)</h2>
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
                                      :class="student.percentage < 50 ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200'">
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
