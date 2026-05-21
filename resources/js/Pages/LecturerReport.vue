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
    TrendingUp,
    Sparkles,
    Zap,
    Trophy,
    Award,
    Mail,
    X,
    ClipboardCopy,
    ChevronRight,
    MoreHorizontal
} from 'lucide-vue-next';

const props = defineProps({
    semesterInfo: Object,
    courses: Array,
    programmes: Array,
    filters: Object,
    stats: Object,
    atRiskStudents: Array,
    allStudents: Array,
    threshold: Number,
    gamificationPulse: Object,
    attendanceTrend: Array,
    pendingLeaves: Array,
    gamificationHub: Object
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
const isGeneratingInsights = ref(false);
const showAiModal = ref(false);
const aiContent = ref('');
const activeHubTab = ref('leaderboard');
const expandedRows = ref(new Set());

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

const generateInsights = () => {
    isGeneratingInsights.value = true;
    setTimeout(() => {
        isGeneratingInsights.value = false;
        alert("AI Insight: Attendance has stabilized at 79.6% this week. 5 students have shown a consistent decline in the last 3 weeks. Recommended: Send follow-up emails to the top 3 at-risk students.");
    }, 1500);
};

const draftEmail = (student) => {
    aiContent.value = `Subject: Support for your Academic Progress - ${student.course_code}\n\nDear ${student.name},\n\nI am reaching out because I noticed your attendance in ${student.course_code} has dropped to ${student.rate}%. We value your participation and want to ensure you have the support you need to succeed in this course.\n\nIf you are facing any difficulties, please don't hesitate to discuss them with me. Let's work together to get you back on track.\n\nBest regards,\nDr. Lecturer`;
    showAiModal.value = true;
};

const copyToClipboard = () => {
    navigator.clipboard.writeText(aiContent.value);
    // Simple toast or feedback could be added here
};

const toggleRow = (id) => {
    if (expandedRows.value.has(id)) {
        expandedRows.value.delete(id);
    } else {
        expandedRows.value.add(id);
    }
};

const getArrivalPercentage = (count) => {
    if (!props.stats.breakdown.total) return 0;
    return Math.round((count / props.stats.breakdown.total) * 100);
};

const updateLeaveStatus = (id, status) => {
    router.post(route('lecturer.leave.status', id), { status }, {
        preserveScroll: true,
    });
};

// --- SVG Chart Calculations ---
const chartWidth = 600;
const chartHeight = 160;
const padding = 20;

const trendPoints = computed(() => {
    const data = props.attendanceTrend;
    if (!data || !data.length) return '';

    const xStep = (chartWidth - padding * 2) / (data.length - 1);
    const yScale = (chartHeight - padding * 2) / 100;

    return data.map((d, i) => {
        const x = padding + i * xStep;
        const y = chartHeight - padding - (d.rate * yScale);
        return `${x},${y}`;
    }).join(' ');
});

const areaPoints = computed(() => {
    const points = trendPoints.value;
    if (!points) return '';
    return `${padding},${chartHeight - padding} ${points} ${chartWidth - padding},${chartHeight - padding}`;
});

</script>

<template>
    <Head title="Analytical Dashboard" />

    <AppLayout>
        <div>
            <!-- Header & Top Bar -->
            <div class="py-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 leading-tight">Analytical Dashboard</h1>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-bold rounded-full">Week {{ semesterInfo.current_week }} / {{ semesterInfo.total_weeks }}</span>
                            <span class="text-[11px] text-slate-500 font-medium">{{ semesterInfo.semester }}</span>
                        </div>
                    </div>
                </div>

                <button
                    @click="generateInsights"
                    :disabled="isGeneratingInsights"
                    class="flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-sm transition-all active:scale-95 disabled:opacity-70"
                >
                    <Sparkles v-if="!isGeneratingInsights" class="w-4 h-4 text-orange-400" />
                    <div v-else class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    {{ isGeneratingInsights ? 'Analyzing...' : 'Generate Insights' }}
                </button>
            </div>

            <!-- Compact Inline Filters -->
            <div class="bg-white rounded-2xl border border-slate-200 p-2 shadow-sm mb-6">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="p-2 text-slate-400">
                        <Filter class="w-4 h-4" />
                    </div>

                    <select v-model="form.course_id" @change="applyFilters" class="min-w-[140px] bg-slate-50 border-none rounded-lg px-3 py-1.5 text-xs font-bold text-slate-600 focus:ring-2 focus:ring-orange-500/20 outline-none">
                        <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }}</option>
                    </select>

                    <select v-model="form.programme_id" @change="applyFilters" class="min-w-[140px] bg-slate-50 border-none rounded-lg px-3 py-1.5 text-xs font-bold text-slate-600 focus:ring-2 focus:ring-orange-500/20 outline-none">                        <option value="">All Programmes</option>
                        <option v-for="prog in programmes" :key="prog.id" :value="prog.id">{{ prog.code }}</option>
                    </select>

                    <div class="flex items-center gap-1 bg-slate-50 rounded-lg px-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">From</span>
                        <input type="date" v-model="form.start_date" @change="applyFilters" class="bg-transparent border-none py-1.5 text-xs font-bold text-slate-600 focus:ring-0 outline-none" />
                    </div>

                    <div class="flex items-center gap-1 bg-slate-50 rounded-lg px-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">To</span>
                        <input type="date" v-model="form.end_date" @change="applyFilters" class="bg-transparent border-none py-1.5 text-xs font-bold text-slate-600 focus:ring-0 outline-none" />
                    </div>
                </div>
            </div>
            <!-- KPI Cards & Pulse Bar -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
                <!-- KPI Cards (3 columns in 4-col grid) -->
                <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Avg Attendance</p>
                        <div class="flex items-end justify-between">
                            <h3 class="text-2xl font-black text-slate-900">{{ stats.avgAttendance }}%</h3>
                            <span :class="[
                                'text-[10px] font-bold px-2 py-0.5 rounded-full',
                                stats.avgAttendance >= threshold ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'
                            ]">
                                {{ stats.avgAttendance >= threshold ? 'Healthy' : 'Below Goal' }}
                            </span>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Students Evaluated</p>
                        <div class="flex items-end justify-between">
                            <h3 class="text-2xl font-black text-slate-900">{{ stats.totalStudents }}</h3>
                            <Users class="w-5 h-5 text-slate-200" />
                        </div>
                    </div>

                    <div class="bg-rose-50 p-4 rounded-2xl border border-rose-100 shadow-sm">
                        <p class="text-[10px] font-bold text-rose-500 uppercase tracking-widest mb-1">At-Risk Cases</p>
                        <div class="flex items-end justify-between">
                            <h3 class="text-2xl font-black text-rose-700">{{ stats.atRiskCount }}</h3>
                            <AlertTriangle class="w-5 h-5 text-rose-300" />
                        </div>
                    </div>
                </div>

                <!-- Gamification Pulse Bar -->
                <div class="lg:col-span-1 bg-indigo-900 rounded-2xl p-4 text-white shadow-lg relative overflow-hidden">
                    <div class="relative z-10 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[9px] font-bold text-indigo-300 uppercase">Streaks</p>
                            <div class="flex items-center gap-1">
                                <Zap class="w-3 h-3 text-orange-400" />
                                <span class="text-sm font-black">{{ gamificationPulse.activeStreaks }}</span>
                            </div>
                        </div>
                        <div>
                            <p class="text-[9px] font-bold text-indigo-300 uppercase">Badges</p>
                            <div class="flex items-center gap-1">
                                <Award class="w-3 h-3 text-emerald-400" />
                                <span class="text-sm font-black">{{ gamificationPulse.badgesAwarded }}</span>
                            </div>
                        </div>
                        <div>
                            <p class="text-[9px] font-bold text-indigo-300 uppercase">Level Ups</p>
                            <div class="flex items-center gap-1">
                                <TrendingUp class="w-3 h-3 text-sky-400" />
                                <span class="text-sm font-black">{{ gamificationPulse.levelUps }}</span>
                            </div>
                        </div>
                        <div>
                            <p class="text-[9px] font-bold text-indigo-300 uppercase">Avg Lvl</p>
                            <div class="flex items-center gap-1">
                                <Trophy class="w-3 h-3 text-amber-400" />
                                <span class="text-sm font-black">{{ gamificationPulse.avgStudentLevel }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 opacity-10">
                        <Trophy class="w-20 h-20" />
                    </div>
                </div>
            </div>

            <!-- Visualizations -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Attendance Trend -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-slate-800">Attendance Trend</h2>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Last 8 Weeks</span>
                    </div>
                    <div class="relative h-40 w-full">
                        <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="w-full h-full">
                            <defs>
                                <linearGradient id="trendGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#f97316" stop-opacity="0.2" />
                                    <stop offset="100%" stop-color="#f97316" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            <!-- Grid Lines -->
                            <line v-for="i in 5" :key="i" x1="20" :y1="20 + (i-1) * 30" :x2="580" :y2="20 + (i-1) * 30" stroke="#f1f5f9" stroke-width="1" />

                            <!-- Area -->
                            <polygon :points="areaPoints" fill="url(#trendGradient)" />

                            <!-- Line -->
                            <polyline
                                :points="trendPoints"
                                fill="none"
                                stroke="#f97316"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />

                            <!-- Points -->
                            <circle v-for="(p, i) in attendanceTrend" :key="i"
                                :cx="padding + i * ((chartWidth - padding * 2) / (attendanceTrend.length - 1))"
                                :cy="chartHeight - padding - (p.rate * ((chartHeight - padding * 2) / 100))"
                                r="4"
                                fill="white"
                                stroke="#f97316"
                                stroke-width="2"
                            />
                        </svg>
                        <!-- Labels -->
                        <div class="flex justify-between mt-2 px-4">
                            <span v-for="p in attendanceTrend" :key="p.week" class="text-[10px] font-bold text-slate-400">{{ p.week }}</span>
                        </div>
                    </div>
                </div>

                <!-- Arrival Breakdown -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-800 mb-4">Arrival Breakdown</h2>
                    <div class="space-y-4">
                        <div v-for="(val, key) in { Early: {color: 'indigo', class: 'bg-indigo-500', text: 'text-indigo-600'}, OnTime: {color: 'emerald', class: 'bg-emerald-500', text: 'text-emerald-600'}, Late: {color: 'amber', class: 'bg-amber-500', text: 'text-amber-600'}, Absent: {color: 'rose', class: 'bg-rose-500', text: 'text-rose-600'}, Leave: {color: 'slate', class: 'bg-slate-400', text: 'text-slate-600'} }" :key="key" class="space-y-1">
                            <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-wider">
                                <span class="text-slate-500">{{ key }}</span>
                                <span :class="val.text" class="font-black">{{ getArrivalPercentage(stats.breakdown[key.charAt(0).toLowerCase() + key.slice(1)]) }}%</span>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-1000"
                                    :class="val.class"
                                    :style="{ width: getArrivalPercentage(stats.breakdown[key.charAt(0).toLowerCase() + key.slice(1)]) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Center -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- At-Risk Students -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col h-[400px]">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <AlertTriangle class="w-4 h-4 text-rose-500" />
                            At-Risk Students
                        </h2>
                        <span class="text-[10px] font-black text-rose-600 bg-rose-50 px-2 py-0.5 rounded uppercase">{{ atRiskStudents.length }}</span>
                    </div>
                    <div class="flex-1 overflow-y-auto p-2 space-y-2">
                        <div v-for="student in atRiskStudents" :key="student.id" class="group relative bg-slate-50 hover:bg-rose-50 p-3 rounded-xl border border-transparent hover:border-rose-100 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-rose-200 flex items-center justify-center text-rose-700 text-xs font-bold">
                                    {{ student.name.charAt(0) }}
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs font-bold text-slate-800">{{ student.name }}</p>
                                    <p class="text-[10px] text-slate-500 font-medium">{{ student.course_code }} • {{ student.rate }}%</p>
                                </div>
                                <button
                                    @click="draftEmail(student)"
                                    class="opacity-0 group-hover:opacity-100 flex items-center gap-1 bg-white border border-slate-200 text-indigo-600 px-2 py-1 rounded-lg text-[10px] font-bold shadow-sm transition-all active:scale-95"
                                >
                                    <Sparkles class="w-3 h-3" />
                                    Draft
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Leaves -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col h-[400px]">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <Calendar class="w-4 h-4 text-amber-500" />
                            Pending Leaves
                        </h2>
                        <span class="text-[10px] font-black text-amber-600 bg-amber-50 px-2 py-0.5 rounded uppercase">{{ pendingLeaves.length }}</span>
                    </div>
                    <div class="flex-1 overflow-y-auto p-2 space-y-2">
                        <div v-for="leave in pendingLeaves" :key="leave.id" class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-700 text-xs font-bold">
                                    {{ leave.avatar }}
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs font-bold text-slate-800">{{ leave.name }}</p>
                                    <p class="text-[10px] text-slate-500 font-medium">{{ leave.type }} • {{ leave.date }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button @click="updateLeaveStatus(leave.id, 'approved')" class="bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold py-1.5 rounded-lg transition-colors">Approve</button>
                                <button @click="updateLeaveStatus(leave.id, 'rejected')" class="bg-slate-200 hover:bg-slate-300 text-slate-700 text-[10px] font-bold py-1.5 rounded-lg transition-colors">Reject</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gamification Hub -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col h-[400px]">
                    <div class="p-1 border-b border-slate-100">
                        <div class="flex p-1 bg-slate-50 rounded-xl">
                            <button
                                @click="activeHubTab = 'leaderboard'"
                                :class="[
                                    'flex-1 flex items-center justify-center gap-2 py-1.5 rounded-lg text-[11px] font-bold transition-all',
                                    activeHubTab === 'leaderboard' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500'
                                ]"
                            >
                                <Trophy class="w-3.5 h-3.5" /> Leaderboard
                            </button>
                            <button
                                @click="activeHubTab = 'badges'"
                                :class="[
                                    'flex-1 flex items-center justify-center gap-2 py-1.5 rounded-lg text-[11px] font-bold transition-all',
                                    activeHubTab === 'badges' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500'
                                ]"
                            >
                                <Award class="w-3.5 h-3.5" /> Badges
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-2">
                        <!-- Leaderboard Content -->
                        <div v-if="activeHubTab === 'leaderboard'" class="space-y-1">
                            <div v-for="student in gamificationHub.leaderboard" :key="student.rank" class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-xl transition-colors">
                                <span :class="[
                                    'w-5 text-center text-[10px] font-black',
                                    student.rank === 1 ? 'text-amber-500' : 'text-slate-400'
                                ]">{{ student.rank }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-slate-800 truncate">{{ student.name }}</p>
                                    <div class="flex items-center gap-2 text-[9px] text-slate-500">
                                        <span>Lvl {{ student.level }}</span>
                                        <span class="flex items-center gap-0.5"><Zap class="w-2.5 h-2.5 text-orange-400" /> {{ student.streak }}</span>
                                    </div>
                                </div>
                                <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">{{ student.xp }} XP</span>
                            </div>
                        </div>

                        <!-- Badges Content -->
                        <div v-else class="space-y-3 p-1">
                            <div v-for="badge in gamificationHub.recentBadges" :key="badge.id" class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                                    <Award class="w-5 h-5 text-emerald-600" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-bold text-slate-800 leading-none mb-0.5">{{ badge.name }}</p>
                                    <p class="text-[10px] text-slate-500 truncate">{{ badge.student }}</p>
                                    <p class="text-[9px] text-slate-400">{{ badge.date }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overall Attendance Summary Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h2 class="text-sm font-bold text-slate-800">Overall Attendance Summary</h2>
                    <div class="relative w-full md:w-64">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search students..."
                            class="w-full pl-9 pr-4 py-1.5 bg-slate-50 border-none rounded-lg text-xs focus:ring-2 focus:ring-orange-500/20 outline-none"
                        >
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Student Info</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Early</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">On-Time</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Late</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Absent</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Leave</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Distribution</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template v-for="student in filteredStudentList" :key="student.id + student.course_code">
                                <tr
                                    @click="toggleRow(student.id + student.course_code)"
                                    class="group hover:bg-slate-50 cursor-pointer transition-colors"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="relative">
                                                <div :class="[
                                                    'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold',
                                                    student.is_at_risk ? 'bg-rose-100 text-rose-700' : 'bg-indigo-100 text-indigo-700'
                                                ]">
                                                    {{ student.name.charAt(0) }}
                                                </div>
                                                <div v-if="student.is_at_risk" class="absolute -top-1 -right-1 w-3 h-3 bg-rose-500 border-2 border-white rounded-full animate-pulse"></div>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-1">
                                                    <p class="text-xs font-bold text-slate-800">{{ student.name }}</p>
                                                    <ChevronDown :class="['w-3 h-3 text-slate-400 transition-transform', expandedRows.has(student.id + student.course_code) ? 'rotate-180' : '']" />
                                                </div>
                                                <p class="text-[10px] text-slate-500 font-medium">{{ student.student_id }} • {{ student.programme }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-xs font-bold text-indigo-600">{{ student.early }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-xs font-bold text-emerald-600">{{ student.on_time }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-xs font-bold text-amber-600">{{ student.late }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-xs font-bold text-rose-600">{{ student.absent }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-xs font-bold text-slate-600">{{ student.leave }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="w-32 h-2 flex rounded-full overflow-hidden bg-slate-100">
                                            <div :style="{ width: (student.early / student.total * 100) + '%' }" class="bg-indigo-500 h-full"></div>
                                            <div :style="{ width: (student.on_time / student.total * 100) + '%' }" class="bg-emerald-500 h-full"></div>
                                            <div :style="{ width: (student.late / student.total * 100) + '%' }" class="bg-amber-500 h-full"></div>
                                            <div :style="{ width: (student.absent / student.total * 100) + '%' }" class="bg-rose-500 h-full"></div>
                                            <div :style="{ width: (student.leave / student.total * 100) + '%' }" class="bg-slate-400 h-full"></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span :class="['text-xs font-black', student.is_at_risk ? 'text-rose-600' : 'text-slate-900']">
                                            {{ student.rate }}%
                                        </span>
                                    </td>
                                </tr>
                                <!-- Expandable Breakdown -->
                                <tr v-if="expandedRows.has(student.id + student.course_code)" class="bg-slate-50/50">
                                    <td colspan="8" class="px-6 py-4">
                                        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Course Breakdown</p>
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                <div class="flex items-center justify-between p-2 bg-slate-50 rounded-lg">
                                                    <span class="text-[11px] font-bold text-slate-600">{{ student.course_code }}</span>
                                                    <span class="text-[11px] font-black text-slate-900">{{ student.rate }}%</span>
                                                </div>
                                                <!-- Mock additional courses for demonstration -->
                                                <div class="flex items-center justify-between p-2 bg-slate-50 rounded-lg opacity-60">
                                                    <span class="text-[11px] font-bold text-slate-600">SWE3012</span>
                                                    <span class="text-[11px] font-black text-slate-900">92.4%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- AI Email Draft Modal -->
        <div v-if="showAiModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAiModal = false"></div>
            <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <div class="flex items-center gap-2">
                        <Sparkles class="w-5 h-5 text-indigo-600" />
                        <h3 class="text-lg font-bold text-slate-900">AI Intervention Draft</h3>
                    </div>
                    <button @click="showAiModal = false" class="p-2 hover:bg-slate-200 rounded-full transition-colors">
                        <X class="w-5 h-5 text-slate-400" />
                    </button>
                </div>
                <div class="p-6">
                    <div class="bg-slate-900 text-slate-100 p-5 rounded-2xl font-mono text-sm leading-relaxed whitespace-pre-wrap border border-slate-700 shadow-inner max-h-[400px] overflow-y-auto">
                        {{ aiContent }}
                    </div>
                    <div class="mt-6 flex items-center gap-3">
                        <button
                            @click="copyToClipboard"
                            class="flex-1 flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 rounded-2xl transition-all active:scale-95"
                        >
                            <ClipboardCopy class="w-5 h-5" />
                            Copy to Clipboard
                        </button>
                        <button
                            @click="showAiModal = false"
                            class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-2xl transition-all"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Area chart animation */
polyline {
    stroke-dasharray: 1000;
    stroke-dashoffset: 1000;
    animation: dash 2s ease-out forwards;
}

@keyframes dash {
    to {
        stroke-dashoffset: 0;
    }
}

/* Custom scrollbar for action center */
.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
