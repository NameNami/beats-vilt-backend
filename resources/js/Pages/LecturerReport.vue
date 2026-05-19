<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    BarChart3,
    Filter,
    Download,
    Users,
    AlertTriangle,
    CheckCircle2,
    Clock,
    Calendar,
    Search,
    ChevronDown,
    ChevronUp,
    FileText,
    TrendingUp
} from 'lucide-vue-next';

const props = defineProps({
    semesterInfo: Object,
    courses: Array,
    programmes: Array,
    filters: Object,
    stats: Object,
    atRiskStudents: Array,
    allStudents: Array,
    threshold: Number
});

// --- State ---
const form = ref({
    course_id: props.filters.course_id || '',
    programme_id: props.filters.programme_id || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const searchQuery = ref('');
const isExporting = ref(false);

// --- Computed ---
const filteredStudentList = computed(() => {
    if (!searchQuery.value) return props.allStudents;
    const q = searchQuery.value.toLowerCase();
    return props.allStudents.filter(s =>
        s.name.toLowerCase().includes(q) ||
        s.student_id.toLowerCase().includes(q)
    );
});

// --- Handlers ---
const applyFilters = () => {
    router.get(route('lecturer.reports'), form.value, {
        preserveState: true,
        preserveScroll: true,
        only: ['stats', 'atRiskStudents', 'allStudents', 'filters']
    });
};

const resetFilters = () => {
    form.value = {
        course_id: '',
        programme_id: '',
        start_date: '',
        end_date: '',
    };
    applyFilters();
};

const handleExport = () => {
    const params = new URLSearchParams(form.value).toString();
    window.location.href = route('lecturer.reports.export') + '?' + params;
};

const getArrivalPercentage = (count) => {
    if (!props.stats.breakdown.total) return 0;
    return Math.round((count / props.stats.breakdown.total) * 100);
};

</script>

<template>
    <Head title="Attendance Reports" />

    <AppLayout>
        <div class="min-h-screen bg-slate-50/50 pb-12">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                            <BarChart3 class="w-7 h-7 text-orange-600" />
                            Analytical Dashboard
                        </h1>
                        <p class="text-slate-500 text-sm font-medium mt-1">
                            Insights and reporting for academic semester {{ semesterInfo.semester }}
                        </p>
                    </div>

                    <!-- Semester Info Chips -->
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg shadow-sm flex items-center gap-2">
                            <Calendar class="w-4 h-4 text-orange-500" />
                            <span class="text-xs font-bold text-slate-700">Week {{ semesterInfo.current_week }} / {{ semesterInfo.total_weeks }}</span>
                        </div>
                        <div class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg shadow-sm flex items-center gap-2">
                            <Clock class="w-4 h-4 text-orange-500" />
                            <span class="text-xs font-bold text-slate-700">{{ semesterInfo.start_date }} - {{ semesterInfo.end_date }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Filters -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm mb-8">
                <div class="flex items-center gap-2 mb-4">
                    <Filter class="w-4 h-4 text-slate-400" />
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Advanced Filtering</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Course Filter -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase ml-1">Course</label>
                        <select v-model="form.course_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all">
                            <option value="">All Courses</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">
                                {{ course.code }} - {{ course.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Programme Filter -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase ml-1">Programme</label>
                        <select v-model="form.programme_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all">
                            <option value="">All Programmes</option>
                            <option v-for="prog in programmes" :key="prog.id" :value="prog.id">
                                {{ prog.name }} ({{ prog.code }})
                            </option>
                        </select>
                    </div>

                    <!-- Start Date -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase ml-1">From Date</label>
                        <input type="date" v-model="form.start_date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all" />
                    </div>

                    <!-- End Date -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase ml-1">To Date</label>
                        <input type="date" v-model="form.end_date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all" />
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-end gap-2">
                        <button @click="applyFilters" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-bold py-2.5 rounded-xl text-sm shadow-sm transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer">
                            Apply
                        </button>
                        <button @click="handleExport" class="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl border border-slate-200 transition-all cursor-pointer" title="Export CSV">
                            <Download class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Average Attendance -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
                    <div class="relative z-10">
                        <p class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-1">Average Attendance</p>
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-4xl font-black text-slate-900">{{ stats.avgAttendance }}%</h3>
                            <span :class="[
                                'text-xs font-bold px-2 py-0.5 rounded-full',
                                stats.avgAttendance >= threshold ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'
                            ]">
                                {{ stats.avgAttendance >= threshold ? 'Healthy' : 'Below Goal' }}
                            </span>
                        </div>
                    </div>
                    <TrendingUp class="absolute -right-4 -bottom-4 w-24 h-24 text-slate-50 opacity-10" />
                </div>

                <!-- Total Students Evaluated -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <p class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-1">Students Evaluated</p>
                    <h3 class="text-4xl font-black text-slate-900">{{ stats.totalStudents }}</h3>
                    <p class="text-xs font-medium text-slate-400 mt-1 flex items-center gap-1">
                        <Users class="w-3 h-3" /> Based on filtered criteria
                    </p>
                </div>

                <!-- At-Risk Count -->
                <div class="bg-rose-50 p-6 rounded-2xl border border-rose-100 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-sm font-bold text-rose-600 uppercase tracking-wider mb-1">At-Risk Cases</p>
                        <h3 class="text-4xl font-black text-rose-700">{{ stats.atRiskCount }}</h3>
                        <p class="text-xs font-medium text-rose-600/60 mt-1">Requires immediate intervention</p>
                    </div>
                    <AlertTriangle class="absolute -right-4 -bottom-4 w-24 h-24 text-rose-200/50 group-hover:scale-110 transition-transform" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <!-- Arrival Breakdown (Holistic Trends) -->
                <div class="lg:col-span-1 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                        <Clock class="w-5 h-5 text-orange-600" />
                        Arrival Breakdown
                    </h2>
                    
                    <div class="space-y-6">
                        <!-- Early -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-end">
                                <span class="text-sm font-bold text-slate-700">Early Arrival</span>
                                <span class="text-xs font-black text-indigo-600">{{ getArrivalPercentage(stats.breakdown.early) }}%</span>
                            </div>
                            <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full transition-all duration-1000" :style="{ width: getArrivalPercentage(stats.breakdown.early) + '%' }"></div>
                            </div>
                        </div>

                        <!-- On-Time -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-end">
                                <span class="text-sm font-bold text-slate-700">On-Time</span>
                                <span class="text-xs font-black text-emerald-600">{{ getArrivalPercentage(stats.breakdown.onTime) }}%</span>
                            </div>
                            <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full transition-all duration-1000" :style="{ width: getArrivalPercentage(stats.breakdown.onTime) + '%' }"></div>
                            </div>
                        </div>

                        <!-- Late -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-end">
                                <span class="text-sm font-bold text-slate-700">Late Arrival</span>
                                <span class="text-xs font-black text-amber-600">{{ getArrivalPercentage(stats.breakdown.late) }}%</span>
                            </div>
                            <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full transition-all duration-1000" :style="{ width: getArrivalPercentage(stats.breakdown.late) + '%' }"></div>
                            </div>
                        </div>

                        <!-- Absent -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-end">
                                <span class="text-sm font-bold text-slate-700">Absent</span>
                                <span class="text-xs font-black text-rose-600">{{ getArrivalPercentage(stats.breakdown.absent) }}%</span>
                            </div>
                            <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-rose-500 rounded-full transition-all duration-1000" :style="{ width: getArrivalPercentage(stats.breakdown.absent) + '%' }"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-100">
                        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                            <div class="w-10 h-10 bg-white rounded-lg border border-slate-200 flex items-center justify-center text-slate-600">
                                <FileText class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Data Integrity</p>
                                <p class="text-xs font-bold text-slate-700">{{ stats.breakdown.total }} total records analyzed</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Intervention Tracking (At-Risk Students List) -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <AlertTriangle class="w-5 h-5 text-rose-600" />
                            Intervention Tracking
                        </h2>
                        <span class="px-2 py-0.5 bg-rose-100 text-rose-700 text-[10px] font-black rounded uppercase tracking-wider">
                            Threshold: {{ threshold }}%
                        </span>
                    </div>

                    <div class="flex-1 overflow-y-auto max-h-[440px]">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Student Info</th>
                                    <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Sessions</th>
                                    <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Attendance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="student in atRiskStudents" :key="student.id + student.course_code" class="hover:bg-rose-50/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-rose-100 flex items-center justify-center text-rose-700 text-xs font-bold">
                                                {{ student.name.charAt(0) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-900">{{ student.name }}</p>
                                                <p class="text-[11px] font-medium text-slate-500">{{ student.student_id }} • {{ student.course_code }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-xs font-bold text-slate-600">{{ student.present }} / {{ student.total }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="text-sm font-black text-rose-600">{{ student.rate }}%</span>
                                            <div class="w-20 h-1 bg-slate-100 rounded-full mt-1 overflow-hidden">
                                                <div class="h-full bg-rose-500" :style="{ width: student.rate + '%' }"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="atRiskStudents.length === 0">
                                    <td colspan="3" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <CheckCircle2 class="w-10 h-10 text-emerald-200" />
                                            <p class="text-sm font-bold text-slate-400">All students are above threshold.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Full Student Attendance Summary -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <Users class="w-5 h-5 text-orange-600" />
                        All Students Attendance Summary
                    </h2>

                    <!-- Search Box -->
                    <div class="relative w-full md:w-80">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search name or student ID..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all"
                        >
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Student Info</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Programme</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Early</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">On-Time</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Late</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Absent</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Total</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="student in filteredStudentList" :key="student.id + student.course_code" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div :class="[
                                            'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold',
                                            student.is_at_risk ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700'
                                        ]">
                                            {{ student.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 leading-tight">{{ student.name }}</p>
                                            <p class="text-[11px] font-medium text-slate-500 mt-0.5">{{ student.student_id }} • {{ student.course_code }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold text-slate-600">{{ student.programme }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-black text-indigo-600">{{ student.early }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-black text-emerald-600">{{ student.on_time }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-black text-amber-600">{{ student.late }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-black text-rose-600">{{ student.absent }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-bold text-slate-400">{{ student.total }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex flex-col items-end">
                                        <span :class="['text-sm font-black', student.is_at_risk ? 'text-rose-600' : 'text-slate-900']">
                                            {{ student.rate }}%
                                        </span>
                                        <div class="w-16 h-1 bg-slate-100 rounded-full mt-1 overflow-hidden">
                                            <div :class="['h-full', student.is_at_risk ? 'bg-rose-500' : 'bg-emerald-500']" :style="{ width: student.rate + '%' }"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredStudentList.length === 0">
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center gap-3 text-slate-400">
                                        <Search class="w-12 h-12 opacity-20" />
                                        <p class="text-sm font-bold uppercase tracking-widest">No matching students found</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Optional styling */
</style>
