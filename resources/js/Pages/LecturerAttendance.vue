<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {
    ChevronDown,
    ChevronUp,
    Users,
    Calendar,
    Clock,
    MapPin,
    X,
    QrCode,
    Activity,
    CheckCircle2,
    RefreshCw,
    Search,
    AlertTriangle,
    Filter,
    Inbox
} from 'lucide-vue-next';
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head} from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    courses: Array,
    sessions: Array,
    atRiskCount: Number,
    criticalCount: Number,
    warningCount: Number,
    atRiskStudents: Array,
    uniqueSubjects: Array,
    threshold: Number,
    totalUniqueStudents: Number
});

// --- State ---
const expandedClassId = ref(props.courses.length > 0 ? props.courses[0].id : null);
const subjectFilter = ref('all');
const atRiskSearchQuery = ref('');
const isSubjectDropdownOpen = ref(false);
const subjectDropdownRef = ref(null);

const showModal = ref(false);
const selectedSessionData = ref(null);
const studentsList = ref([]);
const searchQuery = ref('');
const sessionStats = ref({
    present: 0,
    absent: 0,
    leave: 0,
    total: 0
});
const pollingInterval = ref(null);
const isProcessing = ref(false);
const isQrGenerated = ref(false);

const handleClickOutside = (event) => {
    if (subjectDropdownRef.value && !subjectDropdownRef.value.contains(event.target)) {
        isSubjectDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

// --- Computed ---
const selectedSubjectLabel = computed(() => {
    if (subjectFilter.value === 'all') return 'All Subjects';
    const subject = props.uniqueSubjects.find(s => s.code === subjectFilter.value);
    return subject ? `${subject.name} | ${subject.risk_count}` : subjectFilter.value;
});

const filteredAtRiskStudents = computed(() => {
    let result = [...props.atRiskStudents];

    // 1. Filter by subject (Only show if at risk in THIS subject)
    if (subjectFilter.value !== 'all') {
        result = result.filter(s => 
            s.courses.hasOwnProperty(subjectFilter.value) && 
            s.courses[subjectFilter.value] < props.threshold
        );
        
        // When filtered by subject, sort by that specific subject's rate (lowest first)
        result.sort((a, b) => a.courses[subjectFilter.value] - b.courses[subjectFilter.value]);
    } else {
        // When "all" is selected, sort by overall min_rate (lowest first)
        result.sort((a, b) => a.min_rate - b.min_rate);
    }

    // 2. Filter by search query
    if (atRiskSearchQuery.value) {
        const q = atRiskSearchQuery.value.toLowerCase();
        result = result.filter(s =>
            s.name.toLowerCase().includes(q) ||
            s.student_id.toLowerCase().includes(q)
        );
    }

    return result;
});

const activeSessionsCount = computed(() => {
    return props.sessions.filter(s => s.status === 'active').length;
});

const filteredStudents = computed(() => {
    if (!searchQuery.value) return studentsList.value;
    const q = searchQuery.value.toLowerCase();
    return studentsList.value.filter(s =>
        s.name.toLowerCase().includes(q) ||
        s.student_id.toLowerCase().includes(q)
    );
});

// --- Handlers ---
const toggleClass = (classId) => {
    expandedClassId.value = expandedClassId.value === classId ? null : classId;
};

const hasActiveSession = (courseId) => {
    return props.sessions.some(s => s.course_id === courseId && s.status === 'active');
};

const getSessionsByLab = (courseId) => {
    const sessions = props.sessions.filter(s => s.course_id === courseId);
    if (sessions.length === 0) return [];

    const groups = {};

    sessions.forEach(session => {
        const labName = session.lab || 'Lecture';
        if (!groups[labName]) {
            groups[labName] = [];
        }
        groups[labName].push(session);
    });

    const order = { 'active': 1, 'upcoming': 2, 'completed': 3, 'cancelled': 4 };

    return Object.keys(groups).sort((a, b) => {
        if (a === 'Lecture') return -1;
        if (b === 'Lecture') return 1;
        return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
    }).map(labName => ({
        name: labName,
        sessions: groups[labName].sort((a, b) => order[a.status] - order[b.status])
    }));
};

const fetchSessionDetails = async (sessionId, excludeFromPendingId = null) => {
    try {
        const response = await axios.get(route('lecturer.sessions.show', sessionId));
        selectedSessionData.value = response.data.session;

        // Map server data and handle pending state synchronization
        studentsList.value = response.data.students.map(serverStudent => {
            const localStudent = studentsList.value.find(s => s.id === serverStudent.id);

            // If the student was pending locally
            if (localStudent && localStudent.isPending && serverStudent.id !== excludeFromPendingId) {
                // If server now matches our optimistic status, we can stop being "pending"
                if (serverStudent.status === localStudent.status) {
                    return { ...serverStudent, isPending: false };
                }
                // Otherwise, keep the optimistic status and gray color
                return { ...serverStudent, isPending: true, status: localStudent.status };
            }

            return { ...serverStudent, isPending: false };
        });

        sessionStats.value = response.data.stats;
    } catch (error) {
        console.error('Error fetching session details:', error);
    }
};

const openAttendanceWindow = async (session) => {
    await fetchSessionDetails(session.id);
    showModal.value = true;

    // Set is_display to true
    await axios.post(route('lecturer.sessions.toggle-display', session.id), { is_display: true });

    // Start polling
    pollingInterval.value = setInterval(() => {
        fetchSessionDetails(session.id);
    }, 5000);
};

const closeAttendanceWindow = async () => {
    if (selectedSessionData.value) {
        // Set is_display to false
        await axios.post(route('lecturer.sessions.toggle-display', selectedSessionData.value.id), { is_display: false });
    }

    showModal.value = false;
    if (pollingInterval.value) clearInterval(pollingInterval.value);
    selectedSessionData.value = null;
    studentsList.value = [];
    searchQuery.value = '';
    isQrGenerated.value = false;
};

const generateQr = async () => {
    if (!selectedSessionData.value) return;

    try {
        const response = await axios.post(route('lecturer.sessions.generate-qr', selectedSessionData.value.id));
        selectedSessionData.value.qr_token = response.data.token;
        isQrGenerated.value = true;
    } catch (error) {
        console.error('Error generating QR token:', error);
    }
};

const handleMarkAttendance = async (userId, status) => {
    if (!selectedSessionData.value) return;

    const student = studentsList.value.find(s => s.id === userId);
    if (!student) return;

    const originalStatus = student.status;

    // Optimistic UI update
    student.status = status;
    student.isPending = true;

    try {
        await axios.post(route('lecturer.sessions.mark-attendance', selectedSessionData.value.id), {
            user_id: userId,
            status: status
        });
        // Refresh to get confirmed state and updated stats, excluding this student from the "pending map"
        // so that the server's truth and the clearing of the gray state happen in the same cycle.
        await fetchSessionDetails(selectedSessionData.value.id, userId);
    } catch (error) {
        // Revert on error
        const targetStudent = studentsList.value.find(s => s.id === userId) || student;
        targetStudent.status = originalStatus;
        console.error('Error marking attendance:', error);
    } finally {
        const targetStudent = studentsList.value.find(s => s.id === userId) || student;
        if (targetStudent) targetStudent.isPending = false;
    }
};

const handleMarkAllPresent = async () => {
    if (!selectedSessionData.value || !confirm('Mark all students as present?')) return;

    isProcessing.value = true;
    try {
        await axios.post(route('lecturer.sessions.mark-all-present', selectedSessionData.value.id));
        await fetchSessionDetails(selectedSessionData.value.id);
    } catch (error) {
        console.error('Error marking all present:', error);
    } finally {
        isProcessing.value = false;
    }
};

const handleResetAttendance = async () => {
    if (!selectedSessionData.value || !confirm('Clear all attendance records for this session?')) return;

    isProcessing.value = true;
    try {
        await axios.post(route('lecturer.sessions.reset-attendance', selectedSessionData.value.id));
        await fetchSessionDetails(selectedSessionData.value.id);
    } catch (error) {
        console.error('Error resetting attendance:', error);
    } finally {
        isProcessing.value = false;
    }
};

onUnmounted(() => {
    if (pollingInterval.value) clearInterval(pollingInterval.value);
});
</script>

<template>
    <Head>
        <title>Attendance</title>
    </Head>
    <AppLayout>
        <div class="min-h-screen bg-white text-slate-900 font-sans p-0">
            <div class="w-full">

                <!-- Global Header -->
                <div class="flex items-center gap-4 mb-8">
                    <div>
                        <h1 class="text-2xl font-semibold mb-2 text-gray-900">Attendance Portal</h1>
                    </div>
                </div>

                <!-- Main Dashboard -->
                <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-300">

                    <!-- Stats Row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">My Classes</p>
                            <p class="text-3xl font-bold text-slate-900">{{ courses.length }}</p>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Students</p>
                            <p class="text-3xl font-bold text-slate-900">{{ totalUniqueStudents }}</p>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-orange-200 relative overflow-hidden shadow-sm">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-orange-50 rounded-bl-full -z-10"></div>
                            <p class="text-xs font-semibold text-orange-600 uppercase tracking-wider mb-1">Total At-Risk</p>
                            <p class="text-3xl font-bold text-orange-700">{{ atRiskCount }}</p>
                        </div>
                    </div>

                    <!-- At-Risk Matrix Section -->
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                        <!-- Merged Header & Filters -->
                        <div class="p-5 border-b border-slate-100 bg-white">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center shrink-0">
                                        <AlertTriangle class="w-5 h-5 text-rose-600" />
                                    </div>
                                    <div>
                                        <h2 class="text-sm font-bold text-slate-800 leading-tight">Students at Risk (Below {{ threshold }}%)</h2>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Total unique: {{ atRiskCount }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row items-center gap-2">
                                    <!-- Search Box -->
                                    <div class="relative w-full sm:w-64">
                                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                                        <input
                                            v-model="atRiskSearchQuery"
                                            type="text"
                                            placeholder="Search student..."
                                            class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all"
                                        >
                                    </div>

                                    <!-- Subject Dropdown (Beside Search) -->
                                    <div class="relative w-full sm:w-auto" ref="subjectDropdownRef">
                                        <button
                                            @click="isSubjectDropdownOpen = !isSubjectDropdownOpen"
                                            class="w-full sm:w-48 inline-flex items-center justify-between text-white bg-orange-500 hover:bg-orange-600 focus:ring-4 focus:ring-orange-500/20 shadow-sm font-bold rounded-xl text-[11px] px-4 py-2 transition-all outline-none cursor-pointer"
                                            type="button"
                                        >
                                            <div class="flex items-center gap-2">
                                                <Filter class="w-3.5 h-3.5" />
                                                <span class="truncate">{{ selectedSubjectLabel }}</span>
                                            </div>
                                            <ChevronDown class="w-3.5 h-3.5 ms-2 transition-transform duration-200" :class="{'rotate-180': isSubjectDropdownOpen}" />
                                        </button>

                                        <div v-if="isSubjectDropdownOpen" class="absolute right-0 top-full mt-2 z-30 bg-white border border-slate-200 rounded-xl shadow-xl w-64 overflow-hidden animate-in fade-in zoom-in-95 duration-100">
                                            <ul class="p-1.5 text-xs text-slate-700 font-medium max-h-60 overflow-y-auto space-y-1">
                                                <li>
                                                    <button @click="subjectFilter = 'all'; isSubjectDropdownOpen = false"
                                                        class="flex items-center justify-between w-full p-2 hover:bg-orange-50 hover:text-orange-700 rounded-lg transition-colors text-left cursor-pointer"
                                                        :class="{'text-orange-600 bg-orange-50/50': subjectFilter === 'all'}">
                                                        <span>All Subjects</span>
                                                        <span class="text-[10px] font-bold bg-slate-100 px-1.5 py-0.5 rounded">{{ atRiskCount }}</span>
                                                    </button>
                                                </li>
                                                <li v-for="subject in uniqueSubjects" :key="subject.code">
                                                    <button @click="subjectFilter = subject.code; isSubjectDropdownOpen = false"
                                                        class="flex items-center justify-between w-full p-2 hover:bg-orange-50 hover:text-orange-700 rounded-lg transition-colors text-left cursor-pointer"
                                                        :class="{'text-orange-600 bg-orange-50/50': subjectFilter === subject.code}">
                                                        <span class="truncate pr-4 font-bold">{{ subject.name }}</span>
                                                        <span class="text-[10px] font-bold bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded">{{ subject.risk_count }}</span>
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- At-Risk Table -->
                        <div class="overflow-x-auto scrollbar-thin">
                            <table class="w-full text-left border-collapse min-w-[800px]">
                                <thead>
                                    <tr class="bg-slate-50/50">
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest sticky left-0 bg-slate-50/50 z-10 border-b border-slate-100">Student Info</th>
                                        <th v-for="subject in uniqueSubjects" :key="'head-'+subject.code" 
                                            class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center border-b border-slate-100 whitespace-nowrap">
                                            {{ subject.name }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-if="filteredAtRiskStudents.length === 0">
                                        <td :colspan="uniqueSubjects.length + 1" class="px-6 py-12 text-center text-slate-400">
                                            <Inbox class="w-12 h-12 opacity-10 mx-auto mb-4" />
                                            <p class="text-xs font-bold uppercase tracking-widest">No students match your filters</p>
                                        </td>
                                    </tr>
                                    <tr v-else v-for="student in filteredAtRiskStudents" :key="student.id" class="hover:bg-slate-50 transition-colors group">
                                        <td class="px-6 py-3 sticky left-0 bg-white group-hover:bg-slate-50 z-10 border-r border-slate-50 shadow-[4px_0_10px_-4px_rgba(0,0,0,0.05)]">
                                            <div class="flex items-center gap-3">
                                                <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold', student.min_rate < 50 ? 'bg-rose-100 text-rose-700' : 'bg-orange-100 text-orange-700']">
                                                    {{ student.name.charAt(0) }}
                                                </div>
                                                <div>
                                                    <p class="text-xs font-bold text-slate-800 leading-tight">{{ student.name }}</p>
                                                    <p class="text-[10px] text-slate-500 font-medium">{{ student.student_id }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td v-for="subject in uniqueSubjects" :key="'cell-'+student.id+'-'+subject.code" class="px-6 py-3 text-center">
                                            <!-- Fix falsy 0 bug by checking property existence -->
                                            <span v-if="student.courses.hasOwnProperty(subject.code) && student.courses[subject.code] < threshold" 
                                                class="text-xs font-bold text-rose-600">
                                                {{ student.courses[subject.code] }}%
                                            </span>
                                            <span v-else class="text-slate-300 text-[10px] font-bold opacity-40">●</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Classes Accordion List -->
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 mb-4">Course Schedules</h2>
                        <div class="space-y-4">
                            <div
                                v-for="course in courses"
                                :key="course.id"
                                class="bg-white border border-slate-200 rounded-2xl overflow-hidden transition-all"
                            >
                                <!-- Accordion Header -->
                                <div
                                    @click="toggleClass(course.id)"
                                    class="p-5 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition-colors select-none"
                                >
                                    <div class="flex items-center gap-4">
                                        <div>
                                            <div class="flex items-center gap-2 mb-0.5">
                                                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-xs font-bold rounded">
                                                    {{ course.code }}
                                                </span>
                                                <span class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                                                    <Users class="w-3 h-3" /> {{ course.enrolled }} Students
                                                </span>
                                            </div>
                                            <h3 class="text-base font-bold text-slate-900">{{ course.name }}</h3>
                                        </div>
                                    </div>
                                    <div class="text-slate-400 p-2 flex items-center gap-3">
                                        <span v-if="hasActiveSession(course.id)" class="px-2 py-0.5 text-[10px] uppercase font-bold tracking-wider text-white bg-orange-400 rounded">
                                            On Going
                                        </span>
                                        <ChevronUp v-if="expandedClassId === course.id" class="w-5 h-5" />
                                        <ChevronDown v-else class="w-5 h-5" />
                                    </div>
                                </div>

                                <!-- Accordion Body (Sessions) -->
                                <div v-if="expandedClassId === course.id" class="border-t border-slate-100 bg-slate-50/50 p-6 space-y-8">
                                    <p v-if="getSessionsByLab(course.id).length === 0" class="text-sm text-slate-500 text-center py-4 font-medium">
                                        No sessions scheduled.
                                    </p>
                                    <div v-else v-for="labGroup in getSessionsByLab(course.id)" :key="labGroup.name" class="space-y-3">
                                        <div class="flex items-center gap-2">
                                            <div class="h-4 w-1 bg-orange-500 rounded-full"></div>
                                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-widest">{{ labGroup.name }}</h4>
                                        </div>
                                        <div class="flex overflow-x-auto gap-4 pb-2 snap-x snap-mandatory hide-scrollbar">
                                            <div
                                                v-for="session in labGroup.sessions"
                                                :key="session.id"
                                                @click="openAttendanceWindow(session)"
                                                class="w-44 h-33 shrink-0 snap-start flex flex-col p-4 bg-white border rounded-2xl transition-all duration-200 hover:shadow-md cursor-pointer select-none group"
                                                :class="[
                                                    session.status === 'completed'
                                                        ? 'border-rose-100 bg-rose-50/5 hover:border-rose-300'
                                                        : session.status === 'active'
                                                        ? 'border-orange-400 bg-orange-50/5 hover:border-orange-400 ring-1 ring-orange-100 shadow-sm shadow-orange-500/10'
                                                        : session.status === 'upcoming'
                                                        ? 'border-blue-100 hover:border-blue-400'
                                                        : 'border-slate-100 opacity-60 grayscale hover:opacity-100 hover:grayscale-0'
                                                ]"
                                            >
                                                <!-- Top Status -->
                                                <div class="flex justify-between items-start mb-auto">
                                                    <span :class="[
                                                        'text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded',
                                                        session.status === 'completed' ? 'bg-rose-100 text-rose-600' :
                                                        session.status === 'active' ? 'bg-orange-400 text-white' :
                                                        session.status === 'upcoming' ? 'bg-blue-100 text-blue-600' :
                                                        'bg-slate-200 text-slate-600'
                                                    ]">
                                                        {{ session.status === 'active' ? 'On Going' : (session.status === 'completed' ? 'Passed' : session.status) }}
                                                    </span>
                                                    <span class="text-[10px] font-bold text-slate-800 uppercase tracking-tighter">
                                                        W{{ session.week }}
                                                    </span>
                                                </div>

                                                <!-- Middle: Date -->
                                                <div class="flex-1 flex flex-col items-center justify-center text-center">
                                                    <p class="text-sm font-bold text-slate-800 leading-tight">{{ session.date }}</p>
                                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5xzc">{{ session.day }}</p>
                                                    <p class="text-[10px] font-bold text-slate-800 uppercase tracking-widest mt-0.5xzc">{{ session.time }}</p>
                                                    <p class="text-[10px] font-bold text-slate-800 uppercase tracking-widest mt-0.5xzc">{{ session.location }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Attendance Modal -->
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeAttendanceWindow"></div>

                    <!-- Modal Content -->
                    <div class="relative bg-white w-full max-w-6xl h-[90vh] rounded-xl shadow-2xl flex flex-col overflow-hidden animate-in zoom-in-95 duration-300">

                        <!-- Modal Header -->
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-white sticky top-0 z-10">
                            <div>
                                <div class="flex items-center gap-3 mb-0.5">
                                    <h2 class="text-xl font-bold text-slate-900">{{ selectedSessionData?.course_name }}</h2>
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-bold rounded">{{ selectedSessionData?.course_code }}</span>
                                </div>
                                <div class="flex items-center gap-4 text-xs font-medium text-slate-500">
                                    <span class="flex items-center gap-1.5"><Clock class="w-3.5 h-3.5" /> {{ selectedSessionData?.start_time }} - {{ selectedSessionData?.end_time }}</span>
                                    <span class="flex items-center gap-1.5"><Users class="w-3.5 h-3.5" /> {{ selectedSessionData?.lab }}</span>
                                    <span class="flex items-center gap-1.5"><MapPin class="w-3.5 h-3.5" /> {{ selectedSessionData?.location }}</span>
                                    <span class="flex items-center gap-1.5 capitalize"><Activity class="w-3.5 h-3.5" /> Mode: {{ selectedSessionData?.mode }}</span>
                                </div>
                            </div>
                            <button @click="closeAttendanceWindow" class="p-2 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors cursor-pointer">
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="flex-1 flex overflow-hidden">

                            <!-- Left: Session Controls & QR (Fixed Layout) -->
                            <div class="w-full md:w-1/2 p-10 border-r border-slate-100 flex flex-col items-center justify-center gap-8 bg-white">

                                <!-- QR Display -->
                                <div class="flex flex-col items-center w-full">
                                    <div class="p-6 bg-white border border-slate-200 rounded-xl shadow-2xl transition-all duration-500">
                                        <div class="w-80 h-80 bg-slate-50 rounded-lg flex items-center justify-center overflow-hidden border border-slate-100 relative">
                                            <!-- Revealed State -->
                                            <div v-if="isQrGenerated" class="w-full h-full flex items-center justify-center">
                                                <img
                                                    v-if="selectedSessionData?.qr_token"
                                                    :src="`https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${selectedSessionData.qr_token}`"
                                                    alt="Attendance QR"
                                                    class="w-full h-full p-2"
                                                >
                                                <div v-else class="flex flex-col items-center gap-3 text-slate-400">
                                                    <QrCode class="w-10 h-10 opacity-20 animate-pulse" />
                                                    <p class="text-[10px] font-bold uppercase tracking-widest">Generating Session QR...</p>
                                                </div>
                                            </div>

                                            <!-- Protected/Initial State -->
                                            <div v-else class="flex flex-col items-center gap-6 p-8 text-center w-full h-full justify-center">
                                                <div class="flex flex-col items-center gap-4 text-slate-400">
                                                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center">
                                                        <QrCode class="w-8 h-8 opacity-40" />
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-bold text-slate-600 mb-1">QR Code Protected</p>
                                                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-medium">Generate to reveal session code</p>
                                                    </div>
                                                </div>

                                                <button
                                                    @click="generateQr"
                                                    class="mt-4 px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-bold text-xs flex items-center gap-2 transition-all shadow-lg shadow-orange-500/20 active:scale-95 cursor-pointer"
                                                >
                                                    <QrCode class="w-4 h-4" />
                                                    Generate QR
                                                </button>
                                            </div>
                                        </div>                                    </div>
                                </div>

                                <!-- Live Stats -->
                                <div class="flex flex-col items-center justify-center text-center mt-2">
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-8xl leading-none font-black text-orange-600 tracking-tighter">{{ sessionStats.present || 0 }}</span>
                                        <span class="text-4xl font-bold text-orange-300">/</span>
                                        <span class="text-4xl font-bold text-orange-400">{{ sessionStats.total || 0 }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Student List -->
                            <div class="flex-1 bg-white flex flex-col">
                                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-white sticky top-0 z-20 gap-4">
                                    <!-- Search Box -->
                                    <div class="relative flex-1">
                                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <input
                                            v-model="searchQuery"
                                            type="text"
                                            placeholder="Search student name or ID..."
                                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all"
                                        >
                                    </div>

                                    <!-- Bulk Actions -->
                                    <div class="flex gap-2">
                                        <button
                                            @click="handleMarkAllPresent"
                                            :disabled="isProcessing"
                                            class="flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-[10px] font-bold uppercase transition-all shadow-sm active:scale-95 disabled:opacity-50 cursor-pointer"
                                        >
                                            <CheckCircle2 class="w-4 h-4" />
                                            Mark All Present
                                        </button>
                                        <button
                                            @click="handleResetAttendance"
                                            :disabled="isProcessing"
                                            class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-lg transition-all active:scale-95 disabled:opacity-50 cursor-pointer"
                                            title="Reset Attendance"
                                        >
                                            <RefreshCw class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- List Sub-header -->
                                <div class="px-6 py-2 bg-slate-800 text-[10px] font-bold text-slate-300 uppercase tracking-widest flex justify-between items-center sticky top-[73px] z-20">
                                    <span>Student Details</span>
                                    <span class="mr-24">Status</span>
                                </div>

                                <div class="flex-1 overflow-y-auto">
                                    <div
                                        v-for="student in filteredStudents"
                                        :key="student.id"
                                        class="px-6 py-4 border-b border-slate-50 flex items-center justify-between hover:bg-slate-50/50 transition-colors"
                                        :class="{'bg-rose-50/50': student.is_at_risk}"
                                    >
                                        <div class="flex items-center gap-4">
                                            <div>
                                                <p class="font-bold text-slate-900 text-sm leading-tight">{{ student.name }}</p>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <p class="text-[11px] font-medium text-slate-400">{{ student.student_id }}</p>
                                                    <span v-if="student.is_at_risk" class="text-[10px] font-bold text-rose-600 uppercase">{{ student.attendance_rate }}%</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Manual Attendance Toggle -->
                                        <div class="relative bg-slate-100 p-1 rounded-lg flex items-center w-[240px] h-11 overflow-hidden">
                                            <!-- Sliding Indicator -->
                                            <div
                                                class="absolute h-9 rounded-md transition-all duration-300 ease-in-out shadow-sm"
                                                :class="[
                                                    student.isPending ? 'bg-slate-400' : (
                                                        student.status === 'present' ? 'bg-emerald-500' :
                                                        student.status === 'absent' ? 'bg-rose-500' :
                                                        'bg-amber-500'
                                                    ),
                                                    student.status === 'present' ? 'left-1 w-[76px]' :
                                                    student.status === 'absent' ? 'left-[81.5px] w-[76px]' :
                                                    'left-[162px] w-[76px]'
                                                ]"
                                            ></div>

                                            <!-- Buttons -->
                                            <button
                                                @click="handleMarkAttendance(student.id, 'present')"
                                                class="relative z-10 flex-1 h-full text-xs font-bold transition-colors cursor-pointer"
                                                :class="student.status === 'present' ? 'text-white' : 'text-slate-500 hover:text-slate-800'"
                                            >
                                                Attend
                                            </button>
                                            <button
                                                @click="handleMarkAttendance(student.id, 'absent')"
                                                class="relative z-10 flex-1 h-full text-xs font-bold transition-colors cursor-pointer"
                                                :class="student.status === 'absent' ? 'text-white' : 'text-slate-400 hover:text-slate-800'"
                                            >
                                                Absent
                                            </button>
                                            <button
                                                @click="handleMarkAttendance(student.id, 'leave')"
                                                class="relative z-10 flex-1 h-full text-xs font-bold transition-colors cursor-pointer"
                                                :class="student.status === 'leave' ? 'text-white' : 'text-slate-400 hover:text-slate-800'"
                                            >
                                                Leave
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="filteredStudents.length === 0" class="flex flex-col items-center justify-center py-32 text-slate-400">
                                        <Users class="w-12 h-12 opacity-10 mb-4" />
                                        <p class="text-xs font-bold uppercase tracking-widest">No matching students</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.zoom-in-95 {
    transform: scale(0.95);
}

.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}

/* Custom scrollbar for the Matrix table */
.scrollbar-thin::-webkit-scrollbar {
    height: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: #f8fafc;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
