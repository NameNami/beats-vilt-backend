<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
    BarChart3,
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
    MoreHorizontal, Filter
} from 'lucide-vue-next';
import DateRangePicker from 'flowbite-datepicker/DateRangePicker';
import VueApexCharts from "vue3-apexcharts";

const props = defineProps({
    semesterInfo: Object,
    courses: Array,
    programmes: Array,
    filters: Object,
    stats: Object,
    atRiskStudents: Array,
    allStudents: Array,
    threshold: Number,
    attendanceTrend: Array,
});

// --- State ---
const form = ref({
    course_id: props.filters?.course_id || '',
    programme_id: props.filters?.programme_id || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

const searchQuery = ref('');
const isExporting = ref(false);

// --- Table State ---
const showOnlyAtRisk = ref(false);
const sortColumn = ref(null);
const sortOrder = ref('desc');

const handleSort = (column) => {
    if (sortColumn.value === column) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortOrder.value = 'desc';
    }
};

// --- Custom Dropdown State ---
const isCourseDropdownOpen = ref(false);
const isProgrammeDropdownOpen = ref(false);
const courseDropdownRef = ref(null);
const programmeDropdownRef = ref(null);

const selectedCourseLabel = computed(() => {
    if (!form.value.course_id) return 'All Courses';
    const course = props.courses.find(c => c.id === form.value.course_id);
    return course ? `${course.code} - ${course.name}` : 'All Courses';
});

const selectedProgrammeLabel = computed(() => {
    if (!form.value.programme_id) return 'All Programmes';
    const prog = props.programmes.find(p => p.id === form.value.programme_id);
    return prog ? prog.code : 'All Programmes';
});

const handleClickOutside = (event) => {
    if (courseDropdownRef.value && !courseDropdownRef.value.contains(event.target)) {
        isCourseDropdownOpen.value = false;
    }
    if (programmeDropdownRef.value && !programmeDropdownRef.value.contains(event.target)) {
        isProgrammeDropdownOpen.value = false;
    }
};

// --- ApexCharts Data ---
const apexAreaOptions = computed(() => ({
    chart: {
        type: 'area',
        height: 180,
        toolbar: { show: false },
        sparkline: { enabled: false },
        animations: { enabled: true }
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.45,
            opacityTo: 0.05,
            stops: [0, 100],
            colorStops: [
                { offset: 0, color: "#f97316", opacity: 0.4 },
                { offset: 100, color: "#ffffff", opacity: 0 }
            ]
        },
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 3, colors: ['#f97316'] },
    xaxis: {
        type: 'category',
        categories: props.attendanceTrend ? props.attendanceTrend.map(d => String(d.week)) : [],
        labels: { show: true, style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 600 } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        show: true,
        min: 0,
        max: 100,
        labels: {
            show: true,
            style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 600 },
            formatter: (val) => Math.round(val) + "%"
        }
    },
    grid: { show: true, borderColor: '#f1f5f9', strokeDashArray: 4 },
    tooltip: { x: { show: true }, y: { formatter: (val) => Math.round(val) + "%" } },
    markers: { size: 4, colors: ['#ffffff'], strokeColors: '#f97316', strokeWidth: 2, hover: { size: 6 } }
}));

const apexAreaSeries = computed(() => [{
    name: 'Attendance',
    data: props.attendanceTrend ? props.attendanceTrend.map(d => d.rate) : []
}]);

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);

    const dateRangePickerEl = document.getElementById('date-range-picker');
    if (dateRangePickerEl) {
        new DateRangePicker(dateRangePickerEl, {
            format: 'yyyy-mm-dd',
            autohide: true,
        });

        const startEl = document.getElementById('datepicker-range-start');
        const endEl = document.getElementById('datepicker-range-end');

        startEl.addEventListener('changeDate', () => {
            form.value.start_date = startEl.value;
            applyFilters();
        });

        endEl.addEventListener('changeDate', () => {
            form.value.end_date = endEl.value;
            applyFilters();
        });
    }
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

// --- Computed ---
const filteredStudentList = computed(() => {
    if(!props.allStudents) return [];
    let list = props.allStudents;

    if (showOnlyAtRisk.value) {
        list = list.filter(s => s.is_at_risk);
    }

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(s =>
            s.name.toLowerCase().includes(q) ||
            s.student_id.toLowerCase().includes(q)
        );
    }

    if (sortColumn.value) {
        list = [...list].sort((a, b) => {
            let valA = a[sortColumn.value];
            let valB = b[sortColumn.value];
            if (sortOrder.value === 'desc') {
                return valB > valA ? 1 : (valB < valA ? -1 : 0);
            }
            return valA > valB ? 1 : (valA < valB ? -1 : 0);
        });
    }

    return list;
});

// --- Handlers ---
const applyFilters = () => {
    router.get(route('admin.analytics'), form.value, {
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

const getArrivalPercentage = (count) => {
    if (!props.stats || !props.stats.breakdown.total) return 0;
    return Math.round((count / props.stats.breakdown.total) * 100);
};

const exportGlobalReport = () => {
    const params = new URLSearchParams(form.value).toString();
    window.location.href = route('admin.analytics.export') + '?' + params;
};

</script>

<template>
    <Head title="Global Analytics Dashboard" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header & Top Bar -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                <h1 class="text-2xl font-semibold text-gray-900">System-Wide Analytics</h1>
                <button @click="exportGlobalReport" class="inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition shadow-sm">
                    <Download class="w-4 h-4" />
                    Export Compliance Report (CSV)
                </button>
            </div>

            <!-- Compact Inline Filters -->
            <div class="bg-white rounded-2xl border border-slate-300 p-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <Filter class="w-4 h-4 text-slate-500" />
                    </div>
                    <!-- Course Dropdown -->
                    <div class="relative w-full sm:w-auto" ref="courseDropdownRef">

                        <button
                            @click="isCourseDropdownOpen = !isCourseDropdownOpen"
                            class="w-full sm:min-w-[260px] inline-flex items-center justify-between text-slate-800 bg-white border border-orange-500 focus:ring-4 focus:ring-orange-500/20 font-medium rounded-xl text-sm px-5 py-2.5 transition-all outline-none cursor-pointer"
                            type="button"
                        >
                            <span class="truncate max-w-[300px]">{{ selectedCourseLabel }}</span>
                            <ChevronDown class="w-4 h-4 ms-2 -me-1 text-slate-400 transition-transform duration-200" :class="{'rotate-180': isCourseDropdownOpen}" />
                        </button>

                        <div v-if="isCourseDropdownOpen" class="absolute left-0 top-full mt-2 z-30 bg-white border border-slate-300 rounded-xl w-80 overflow-hidden animate-in fade-in zoom-in-95 duration-100">
                            <ul class="p-1.5 text-sm text-slate-700 font-medium max-h-60 overflow-y-auto space-y-1">
                                <li>
                                    <button @click="form.course_id = ''; applyFilters(); isCourseDropdownOpen = false"
                                        class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                        :class="{'text-orange-400 bg-orange-50/50': !form.course_id}">
                                        All Courses
                                    </button>
                                </li>
                                <li v-for="course in courses" :key="course.id">
                                    <button @click="form.course_id = course.id; applyFilters(); isCourseDropdownOpen = false"
                                        class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                        :class="{'text-orange-400 bg-orange-50/50': form.course_id === course.id}">
                                        {{ course.code }} - {{ course.name }}
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Programme Dropdown -->
                    <div class="relative w-full sm:w-auto" ref="programmeDropdownRef">
                        <button
                            @click="isProgrammeDropdownOpen = !isProgrammeDropdownOpen"
                            class="w-full sm:min-w-[180px] inline-flex items-center justify-between text-slate-800 bg-white border border-orange-500 focus:ring-4 focus:ring-orange-500/20 font-medium rounded-xl text-sm px-5 py-2.5 transition-all outline-none cursor-pointer"
                            type="button"
                        >
                            <span class="truncate max-w-[150px]">{{ selectedProgrammeLabel }}</span>
                            <ChevronDown class="w-4 h-4 ms-2 -me-1 text-slate-400 transition-transform duration-200" :class="{'rotate-180': isProgrammeDropdownOpen}" />
                        </button>

                        <div v-if="isProgrammeDropdownOpen" class="absolute left-0 top-full mt-2 z-30 bg-white border border-slate-300 rounded-xl w-64 overflow-hidden animate-in fade-in zoom-in-95 duration-100">
                            <ul class="p-1.5 text-sm text-slate-700 font-medium max-h-60 overflow-y-auto space-y-1">
                                <li>
                                    <button @click="form.programme_id = ''; applyFilters(); isProgrammeDropdownOpen = false"
                                        class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                        :class="{'text-orange-400 bg-orange-50/50': !form.programme_id}">
                                        All Programmes
                                    </button>
                                </li>
                                <li v-for="prog in programmes" :key="prog.id">
                                    <button @click="form.programme_id = prog.id; applyFilters(); isProgrammeDropdownOpen = false"
                                        class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                        :class="{'text-orange-400 bg-orange-50/50': form.programme_id === prog.id}">
                                        {{ prog.code }}
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <p class="text-slate-500 text-xs font-bold uppercase"> | </p>

                    <!-- Flowbite Date Range Picker -->
                    <div id="date-range-picker" date-rangepicker class="flex items-center gap-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-orange-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                            </div>
                            <input
                                id="datepicker-range-start"
                                name="start"
                                type="text"
                                :value="form.start_date"
                                class="block w-full ps-10 pe-3 py-2.5 bg-white border border-orange-500 text-slate-800 text-sm rounded-xl focus:ring-orange-500/20 focus:border-orange-500 transition-all outline-none cursor-pointer h-[42px]"
                                placeholder="Select date start"
                            >
                        </div>
                        <span class="text-slate-500 text-xs font-bold uppercase">to</span>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-orange-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                            </div>
                            <input
                                id="datepicker-range-end"
                                name="end"
                                type="text"
                                :value="form.end_date"
                                class="block w-full ps-10 pe-3 py-2.5 bg-white border border-orange-500 text-slate-800 text-sm rounded-xl focus:ring-orange-500/20 focus:border-orange-500 transition-all outline-none cursor-pointer h-[42px]"
                                placeholder="Select date end"
                            >
                        </div>
                    </div>
                    
                    <button v-if="form.course_id || form.programme_id || form.start_date || form.end_date" @click="resetFilters" class="text-sm font-bold text-slate-500 hover:text-slate-700 transition">Reset</button>
                </div>
            </div>

            <!-- KPI Cards -->
            <div v-if="stats" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-2xl border border-slate-300">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Avg Attendance</p>
                    <div class="flex items-end justify-between">
                        <h3 class="text-2xl font-black text-slate-900">{{ stats.avgAttendance }}%</h3>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-300">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Students Tracked</p>
                    <div class="flex items-end justify-between">
                        <h3 class="text-2xl font-black text-slate-900">{{ stats.totalStudents }}</h3>
                    </div>
                </div>

                <div class="p-6 rounded-2xl border border-rose-400">
                    <p class="text-[10px] font-bold text-rose-500 uppercase tracking-widest mb-1">Global At-Risk Cases</p>
                    <div class="flex items-end justify-between">
                        <h3 class="text-2xl font-black text-rose-700">{{ stats.atRiskCount }}</h3>
                    </div>
                </div>
            </div>

            <!-- Visualizations -->
            <div v-if="stats" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Attendance Trend (Area Chart) -->
                <div class="lg:col-span-2 bg-white border border-slate-300 rounded-2xl p-4 md:p-6 transition-all">
                    <div class="mb-4">
                        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-widest">Global Attendance Trend</h2>
                    </div>

                    <apexchart
                        id="area-chart"
                        type="area"
                        height="210"
                        :options="apexAreaOptions"
                        :series="apexAreaSeries"
                    />
                </div>

                <!-- Arrival Breakdown (Progress Bars Reverted) -->
                <div class="lg:col-span-1 bg-white border border-slate-300 rounded-2xl p-5 transition-all">
                    <h2 class="text-sm font-bold text-slate-800 mb-4">Arrival Breakdown</h2>
                    <div class="space-y-4">
                        <div v-for="(val, key) in { OnTime: {color: 'emerald', class: 'bg-emerald-500', text: 'text-emerald-600'}, Late: {color: 'amber', class: 'bg-amber-500', text: 'text-amber-600'}, Absent: {color: 'rose', class: 'bg-rose-500', text: 'text-rose-600'}, Leave: {color: 'slate', class: 'bg-slate-400', text: 'text-slate-600'} }" :key="key" class="space-y-1">
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

            <!-- Overall Attendance Summary Table -->
            <div class="bg-white rounded-2xl border border-slate-300  overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h2 class="text-sm font-bold text-slate-800">Global Attendance Summary</h2>
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <button
                            @click="showOnlyAtRisk = !showOnlyAtRisk"
                            :class="['px-3 py-1.5 rounded-lg text-xs font-bold border transition-colors flex items-center gap-1.5 cursor-pointer', showOnlyAtRisk ? 'bg-rose-50 border-rose-200 text-rose-600' : 'bg-white border-rose-300 text-slate-500 hover:bg-slate-50']"
                        >
                            <AlertTriangle class="w-3.5 h-3.5" />
                            At-Risk
                        </button>
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
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Student Info</th>
                                <th @click="handleSort('on_time')" class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center cursor-pointer hover:bg-slate-100/50 transition-colors group select-none">
                                    <div class="flex items-center justify-center gap-1">
                                        On-Time
                                        <div class="flex flex-col opacity-0 group-hover:opacity-100 transition-opacity" :class="{'opacity-100': sortColumn === 'on_time'}">
                                            <ChevronUp class="w-2.5 h-2.5 -mb-1" :class="{'text-indigo-600': sortColumn === 'on_time' && sortOrder === 'asc', 'text-slate-400': sortColumn !== 'on_time' || sortOrder !== 'asc'}" />
                                            <ChevronDown class="w-2.5 h-2.5" :class="{'text-indigo-600': sortColumn === 'on_time' && sortOrder === 'desc', 'text-slate-400': sortColumn !== 'on_time' || sortOrder !== 'desc'}" />
                                        </div>
                                    </div>
                                </th>
                                <th @click="handleSort('late')" class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center cursor-pointer hover:bg-slate-100/50 transition-colors group select-none">
                                    <div class="flex items-center justify-center gap-1">
                                        Late
                                        <div class="flex flex-col opacity-0 group-hover:opacity-100 transition-opacity" :class="{'opacity-100': sortColumn === 'late'}">
                                            <ChevronUp class="w-2.5 h-2.5 -mb-1" :class="{'text-indigo-600': sortColumn === 'late' && sortOrder === 'asc', 'text-slate-400': sortColumn !== 'late' || sortOrder !== 'asc'}" />
                                            <ChevronDown class="w-2.5 h-2.5" :class="{'text-indigo-600': sortColumn === 'late' && sortOrder === 'desc', 'text-slate-400': sortColumn !== 'late' || sortOrder !== 'desc'}" />
                                        </div>
                                    </div>
                                </th>
                                <th @click="handleSort('absent')" class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center cursor-pointer hover:bg-slate-100/50 transition-colors group select-none">
                                    <div class="flex items-center justify-center gap-1">
                                        Absent
                                        <div class="flex flex-col opacity-0 group-hover:opacity-100 transition-opacity" :class="{'opacity-100': sortColumn === 'absent'}">
                                            <ChevronUp class="w-2.5 h-2.5 -mb-1" :class="{'text-indigo-600': sortColumn === 'absent' && sortOrder === 'asc', 'text-slate-400': sortColumn !== 'absent' || sortOrder !== 'asc'}" />
                                            <ChevronDown class="w-2.5 h-2.5" :class="{'text-indigo-600': sortColumn === 'absent' && sortOrder === 'desc', 'text-slate-400': sortColumn !== 'absent' || sortOrder !== 'desc'}" />
                                        </div>
                                    </div>
                                </th>
                                <th @click="handleSort('leave')" class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center cursor-pointer hover:bg-slate-100/50 transition-colors group select-none">
                                    <div class="flex items-center justify-center gap-1">
                                        Leave
                                        <div class="flex flex-col opacity-0 group-hover:opacity-100 transition-opacity" :class="{'opacity-100': sortColumn === 'leave'}">
                                            <ChevronUp class="w-2.5 h-2.5 -mb-1" :class="{'text-indigo-600': sortColumn === 'leave' && sortOrder === 'asc', 'text-slate-400': sortColumn !== 'leave' || sortOrder !== 'asc'}" />
                                            <ChevronDown class="w-2.5 h-2.5" :class="{'text-indigo-600': sortColumn === 'leave' && sortOrder === 'desc', 'text-slate-400': sortColumn !== 'leave' || sortOrder !== 'desc'}" />
                                        </div>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Distribution</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template v-for="student in filteredStudentList" :key="student.id + student.course_code">
                                <tr
                                    class="group hover:bg-slate-50 transition-colors"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div>
                                                <div class="flex items-center gap-1">
                                                    <p class="text-xs font-bold text-slate-800">{{ student.name }}</p>

                                                </div>
                                                <p class="text-[10px] text-slate-500 font-medium">{{ student.student_id }} • {{ student.programme }} <br /> <span class="font-bold text-orange-500">{{ student.course_code }}</span></p>
                                            </div>
                                        </div>
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
                                            <div :style="{ width: ((student.on_time / student.total) * 100) + '%' }" class="bg-emerald-500 h-full"></div>
                                            <div :style="{ width: ((student.late / student.total) * 100) + '%' }" class="bg-amber-500 h-full"></div>
                                            <div :style="{ width: ((student.absent / student.total) * 100) + '%' }" class="bg-rose-500 h-full"></div>
                                            <div :style="{ width: ((student.leave / student.total) * 100) + '%' }" class="bg-slate-400 h-full"></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span :class="['text-xs font-black', student.is_at_risk ? 'text-rose-600' : 'text-slate-900']">
                                            {{ student.rate }}%
                                        </span>
                                    </td>
                                </tr>
                                </template>
                        </tbody>
                    </table>
                    <div v-if="filteredStudentList.length === 0" class="p-8 text-center text-slate-500 font-medium">
                        No attendance data available for the selected filters.
                    </div>
                </div>
            </div>
        </div>

    </AdminLayout>
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
