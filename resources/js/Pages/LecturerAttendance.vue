<script setup>
import { ref, computed, onUnmounted } from 'vue';
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
    AlertTriangle
} from 'lucide-vue-next';
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head} from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    courses: Array,
    sessions: Array,
    atRiskCount: Number,
    atRiskStudents: Array,
    threshold: Number
});

// --- State ---
const expandedClassId = ref(props.courses.length > 0 ? props.courses[0].id : null);
const expandedAtRiskCourses = ref([]);
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

// --- Computed ---
const groupedAtRiskStudents = computed(() => {
    const groups = {};
    props.atRiskStudents.forEach(student => {
        if (!groups[student.course_code]) {
            groups[student.course_code] = {
                code: student.course_code,
                name: student.course_name,
                students: []
            };
        }
        groups[student.course_code].students.push(student);
    });
    return Object.values(groups);
});

const activeSessionsCount = computed(() => {
    return props.sessions.filter(s => s.status === 'active').length;
});

const totalStudentsCount = computed(() => {
    return props.courses.reduce((sum, c) => sum + c.enrolled, 0);
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
const toggleAtRiskCourse = (courseCode) => {
    const index = expandedAtRiskCourses.value.indexOf(courseCode);
    if (index === -1) {
        expandedAtRiskCourses.value.push(courseCode);
    } else {
        expandedAtRiskCourses.value.splice(index, 1);
    }
};

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

const fetchSessionDetails = async (sessionId) => {
    try {
        const response = await axios.get(route('lecturer.sessions.show', sessionId));
        selectedSessionData.value = response.data.session;

        // Preserve pending states during refresh
        const pendingMap = new Map();
        studentsList.value.forEach(s => {
            if (s.isPending) pendingMap.set(s.id, true);
        });

        studentsList.value = response.data.students.map(s => ({
            ...s,
            isPending: pendingMap.has(s.id)
        }));

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
        // Refresh to get confirmed state and updated stats
        await fetchSessionDetails(selectedSessionData.value.id);
    } catch (error) {
        // Revert on error
        const targetStudent = studentsList.value.find(s => s.id === userId) || student;
        targetStudent.status = originalStatus;
        console.error('Error marking attendance:', error);
    } finally {
        const targetStudent = studentsList.value.find(s => s.id === userId) || student;
        targetStudent.isPending = false;
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
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white p-6 rounded-2xl border border-slate-200">
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">My Classes</p>
                            <p class="text-3xl font-bold text-slate-900">{{ courses.length }}</p>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-slate-200 relative overflow-hidden">
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">Active Sessions</p>
                            <p class="text-3xl font-bold text-orange-600">{{ activeSessionsCount }}</p>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-slate-200">
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Students</p>
                            <p class="text-3xl font-bold text-slate-900">{{ totalStudentsCount }}</p>
                        </div>

                        <div class="bg-rose-50 p-6 rounded-2xl border border-rose-200 relative overflow-hidden">
                            <p class="text-sm font-semibold text-rose-600 uppercase tracking-wider mb-1">At-Risk Students</p>
                            <p class="text-3xl font-bold text-rose-700">{{ atRiskCount }}</p>
                            <AlertTriangle class="absolute -right-2 -bottom-2 w-16 h-16 text-rose-100" />
                        </div>
                    </div>

                    <!-- At-Risk Students Section -->
                    <div v-if="atRiskStudents.length > 0">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <AlertTriangle class="w-5 h-5 text-rose-600" />
                                Students at Risk (Below {{ threshold }}%)
                            </h2>
                        </div>

                        <div class="space-y-3">
                            <div v-for="group in groupedAtRiskStudents" :key="group.code"
                                class="bg-white border border-rose-100 rounded-xl overflow-hidden transition-all">

                                <!-- Course Header -->
                                <div @click="toggleAtRiskCourse(group.code)"
                                    class="p-4 flex items-center justify-between cursor-pointer hover:bg-rose-50/30 transition-colors select-none">
                                    <div class="flex items-center gap-4">
                                        <div>
                                            <div class="flex items-center gap-2 mb-0.5">
                                                <span class="px-2 py-0.5 bg-rose-100 text-rose-700 text-[10px] font-bold rounded">
                                                    {{ group.code }}
                                                </span>
                                                <span class="text-[10px] font-bold text-rose-600 uppercase">
                                                    {{ group.students.length }} Students at risk
                                                </span>
                                            </div>
                                            <h3 class="text-sm font-bold text-slate-900">{{ group.name }}</h3>
                                        </div>
                                    </div>
                                    <div class="text-rose-400 p-2">
                                        <ChevronUp v-if="expandedAtRiskCourses.includes(group.code)" class="w-4 h-4" />
                                        <ChevronDown v-else class="w-4 h-4" />
                                    </div>
                                </div>

                                <!-- Students List (Dropdown Body) -->
                                <div v-if="expandedAtRiskCourses.includes(group.code)" class="border-t border-rose-50 bg-rose-50/10">
                                    <div class="divide-y divide-rose-50">
                                        <div v-for="student in group.students" :key="student.id"
                                            class="p-4 flex items-center justify-between hover:bg-rose-50/20 transition-colors">
                                            <div class="flex items-center gap-4">
                                                <div>
                                                    <p class="text-sm font-bold text-slate-900 leading-tight">{{ student.name }}</p>
                                                    <p class="text-[11px] font-medium text-slate-500 mt-1">{{ student.student_id }}</p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="flex items-center gap-2 justify-end">
                                                    <span class="text-xs font-bold text-rose-700">{{ student.attendance_rate }}%</span>
                                                    <span class="text-[10px] text-slate-400 font-medium italic">
                                                        ({{ student.present_count }}/{{ student.total_count }} sessions)
                                                    </span>
                                                </div>
                                                <div class="w-32 h-1.5 bg-rose-100 rounded-full mt-1.5 overflow-hidden">
                                                    <div class="h-full bg-rose-500 rounded-full" :style="{ width: student.attendance_rate + '%' }"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                                                    class="mt-4 px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-xl font-bold text-xs flex items-center gap-2 transition-all shadow-lg shadow-orange-600/20 active:scale-95 cursor-pointer"
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
                                            class="flex items-center gap-2 px-4 py-2 bg-orange-400 hover:bg-orange-600 text-white rounded-lg text-[10px] font-bold uppercase transition-all shadow-sm active:scale-95 disabled:opacity-50 cursor-pointer"
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
</style>
