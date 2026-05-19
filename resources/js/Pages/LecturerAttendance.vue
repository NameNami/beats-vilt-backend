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
    Search
} from 'lucide-vue-next';
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    courses: Array,
    sessions: Array
});

// --- State ---
const expandedClassId = ref(props.courses.length > 0 ? props.courses[0].id : null);
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
const toggleClass = (classId) => {
    expandedClassId.value = expandedClassId.value === classId ? null : classId;
};

const hasActiveSession = (courseId) => {
    return props.sessions.some(s => s.course_id === courseId && s.status === 'active');
};

const getSortedSessions = (courseId) => {
    const sessions = props.sessions.filter(s => s.course_id === courseId);
    const order = { 'active': 1, 'upcoming': 2, 'completed': 3, 'cancelled': 4 };
    return sessions.sort((a, b) => order[a.status] - order[b.status]);
};

const fetchSessionDetails = async (sessionId) => {
    try {
        const response = await axios.get(route('lecturer.sessions.show', sessionId));
        selectedSessionData.value = response.data.session;
        studentsList.value = response.data.students;
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

const generateQr = () => {
    isQrGenerated.value = true;
};

const handleMarkAttendance = async (userId, status) => {
    if (!selectedSessionData.value) return;

    try {
        await axios.post(route('lecturer.sessions.mark-attendance', selectedSessionData.value.id), {
            user_id: userId,
            status: status
        });
        // Refresh data immediately
        await fetchSessionDetails(selectedSessionData.value.id);
    } catch (error) {
        console.error('Error marking attendance:', error);
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
        <title>Attendance Portal</title>
    </Head>
    <AppLayout>
        <div class="min-h-screen bg-white text-slate-900 font-sans p-0">
            <div class="w-full">

                <!-- Global Header -->
                <div class="flex items-center gap-4 mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-[#0f172a]">Attendance Portal</h1>
                        <p class="text-sm font-medium text-slate-500 mt-1 flex items-center gap-2">Manage your classes and check-in sessions.</p>
                    </div>
                </div>

                <!-- Main Dashboard -->
                <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-300">

                    <!-- Stats Row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">My Classes</p>
                            <p class="text-3xl font-bold text-slate-900">{{ courses.length }}</p>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">Active Sessions</p>
                            <p class="text-3xl font-bold text-orange-600">{{ activeSessionsCount }}</p>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Students</p>
                            <p class="text-3xl font-bold text-slate-900">{{ totalStudentsCount }}</p>
                        </div>
                    </div>

                    <!-- Classes Accordion List -->
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 mb-4">Course Schedules</h2>
                        <div class="space-y-4">
                            <div
                                v-for="course in courses"
                                :key="course.id"
                                class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm transition-all"
                            >
                                <!-- Accordion Header -->
                                <div
                                    @click="toggleClass(course.id)"
                                    class="p-5 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition-colors select-none"
                                >
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700 font-bold">
                                            {{ course.code.slice(0, 3) }}
                                        </div>
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
                                        <span v-if="hasActiveSession(course.id)" class="px-2 py-0.5 text-[10px] uppercase font-bold tracking-wider text-white bg-orange-600 rounded">
                                            Active
                                        </span>
                                        <ChevronUp v-if="expandedClassId === course.id" class="w-5 h-5" />
                                        <ChevronDown v-else class="w-5 h-5" />
                                    </div>
                                </div>

                                <!-- Accordion Body (Sessions) -->
                                <div v-if="expandedClassId === course.id" class="border-t border-slate-100 bg-slate-50/50 p-5">
                                    <p v-if="getSortedSessions(course.id).length === 0" class="text-sm text-slate-500 text-center py-4">
                                        No sessions scheduled.
                                    </p>
                                    <div v-else class="space-y-3">
                                        <div
                                            v-for="session in getSortedSessions(course.id)"
                                            :key="session.id"
                                            :class="[
                                                'flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 bg-white border rounded-xl transition-all',
                                                session.status === 'completed'
                                                    ? 'border-rose-200 bg-rose-50/10'
                                                    : session.status === 'cancelled'
                                                    ? 'border-slate-200 opacity-75'
                                                    : session.status === 'active'
                                                    ? 'border-orange-600 shadow-sm ring-1 ring-orange-100'
                                                    : 'border-slate-200 hover:border-slate-300 hover:shadow-sm'
                                            ]"
                                        >
                                            <!-- Session Info -->
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <span v-if="session.status === 'active'" class="px-2 py-0.5 text-[10px] uppercase font-bold tracking-wider text-white bg-orange-600 rounded">
                                                        On Going
                                                    </span>
                                                    <span v-if="session.status === 'upcoming'" class="px-2 py-0.5 text-[10px] uppercase font-bold tracking-wider text-blue-700 bg-blue-50 border border-blue-200 rounded">
                                                        Incoming
                                                    </span>
                                                    <span v-if="session.status === 'completed'" class="px-2 py-0.5 text-[10px] uppercase font-bold tracking-wider text-rose-600 bg-rose-50 border border-rose-200 rounded">
                                                        Passed
                                                    </span>
                                                    <span v-if="session.status === 'cancelled'" class="px-2 py-0.5 text-[10px] uppercase font-bold tracking-wider text-slate-600 bg-slate-100 border border-slate-200 rounded">
                                                        Cancelled
                                                    </span>

                                                    <span v-if="session.lab" class="px-2 py-0.5 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 rounded">
                                                        {{ session.lab }}
                                                    </span>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-4 text-sm text-slate-600 font-medium">
                                                    <span class="flex items-center gap-1.5"><Calendar class="w-4 h-4 text-slate-400" /> {{ session.date }}</span>
                                                    <span class="flex items-center gap-1.5"><Clock class="w-4 h-4 text-slate-400" /> {{ session.time }}</span>
                                                    <span class="flex items-center gap-1.5"><MapPin class="w-4 h-4 text-slate-400" /> {{ session.location }}</span>
                                                </div>
                                            </div>

                                            <!-- Action Button -->
                                            <div class="mt-4 sm:mt-0 w-full sm:w-auto shrink-0 pl-0 sm:pl-4">
                                                <button
                                                    @click="openAttendanceWindow(session)"
                                                    :class="[
                                                        'w-full sm:w-auto px-5 py-2 rounded-lg text-sm font-semibold transition-colors cursor-pointer',
                                                        session.status === 'completed'
                                                            ? 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'
                                                            : session.status === 'cancelled'
                                                            ? 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                                            : session.status === 'active'
                                                            ? 'bg-orange-600 text-white hover:bg-orange-700 shadow-sm shadow-orange-500/20'
                                                            : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50'
                                                    ]"
                                                    :disabled="session.status === 'cancelled'"
                                                >
                                                    {{ session.status === 'completed' ? 'View Report' : (session.status === 'active' ? 'Attendance Window' : 'Open Window') }}
                                                </button>
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
                                            class="flex items-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-[10px] font-bold uppercase transition-all shadow-sm active:scale-95 disabled:opacity-50 cursor-pointer"
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
                                    >
                                        <div class="flex items-center gap-4">
                                            <div>
                                                <p class="font-bold text-slate-900 text-sm leading-tight">{{ student.name }}</p>
                                                <p class="text-[11px] font-medium text-slate-400 mt-0.5">{{ student.student_id }}</p>
                                            </div>
                                        </div>

                                        <!-- Manual Attendance Toggle -->
                                        <div class="relative bg-slate-100 p-1 rounded-lg flex items-center w-[240px] h-11 overflow-hidden">
                                            <!-- Sliding Indicator -->
                                            <div
                                                class="absolute h-9 rounded-md transition-all duration-300 ease-in-out shadow-sm"
                                                :class="[
                                                    student.status === 'present' ? 'bg-emerald-500 left-1 w-[76px]' :
                                                    student.status === 'absent' ? 'bg-rose-500 left-[81.5px] w-[76px]' :
                                                    'bg-amber-500 left-[162px] w-[76px]'
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
</style>
