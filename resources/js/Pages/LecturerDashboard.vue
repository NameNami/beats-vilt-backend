<script setup>
import AppLayout from '../Layouts/AppLayout.vue';
import {Head, Link, usePage, router} from '@inertiajs/vue3';
import {computed, ref, onUnmounted} from 'vue';
import {
    MapPin,
    Users,
    CalendarDays,
    ChevronDown,
    ChevronUp,
    Calendar,
    Clock,
    X,
    QrCode,
    Activity,
    CheckCircle2,
    RefreshCw,
    Search
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    overallAttendance: String,
    classTodayCount: String,
    pendingLeaveCount: String,
    atRiskStudentCount: String,
    scheduleItems: Array,
});

// --- Attendance Modal State ---
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

const nextClassTime = computed(() => {
    if (!props.scheduleItems || props.scheduleItems.length === 0) return null;

    // Use Asia/Kuala_Lumpur timezone for current time comparison
    const currentTime = new Date().toLocaleTimeString('en-GB', {
        timeZone: 'Asia/Kuala_Lumpur',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    });

    // Find the first class where the start time is greater than current time and not cancelled or past
    const nextClass = props.scheduleItems.find(item => item.time > currentTime && item.status !== 'cancelled' && item.status !== 'past');
    return nextClass ? nextClass.time : null;
});

const filteredStudents = computed(() => {
    if (!searchQuery.value) return studentsList.value;
    const q = searchQuery.value.toLowerCase();
    return studentsList.value.filter(s =>
        s.name.toLowerCase().includes(q) ||
        s.student_id.toLowerCase().includes(q)
    );
});

const toggleCancel = (id) => {
    if (confirm('Are you sure you want to change the status of this class?')) {
        router.post(route('lecturer.sessions.toggle-cancel', id));
    }
};

// --- Attendance Handlers ---
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

const generateQr = () => {
    isQrGenerated.value = true;
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
        <title>Dashboard</title>
    </Head>
    <AppLayout>
        <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-900 mb-4">Overview</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">

                <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col justify-between">
                    <div>
                        <h3 class="text-slate-600 font-medium text-sm leading-snug mb-2">Overall<br>Attendance</h3>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-3xl leading-none font-bold text-slate-900">{{ overallAttendance }}%</span>
                            <span class="flex items-center text-xs font-semibold text-green-600">
            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col justify-between">
                    <div>
                        <h3 class="text-slate-600 font-medium text-sm leading-snug mb-2">Classes<br>Today</h3>
                        <span class="text-3xl leading-none font-bold text-slate-900">{{ classTodayCount }}</span>
                    </div>
                    <p class="text-xs text-slate-400 font-medium mt-3">
                        {{ nextClassTime ? `Next class at ${nextClassTime}` : 'No more classes today' }}
                    </p>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col justify-between">
                    <div>
                        <h3 class="text-slate-600 font-medium text-sm leading-snug mb-2">Pending<br>Leaves</h3>
                        <span class="text-3xl leading-none font-bold text-slate-900">{{ pendingLeaveCount }}</span>
                    </div>
                    <Link href="/lecturer/leave" class="text-xs font-medium text-orange-600 hover:text-orange-700 transition-colors mt-3 inline-block">
                        Review applications
                    </Link>
                </div>

                <div class="bg-white rounded-xl border border-rose-200 p-4 flex flex-col justify-between">
                    <div>
                        <h3 class="text-rose-800 font-medium text-sm leading-snug mb-2">At-Risk<br>Students</h3>
                        <span class="text-3xl leading-none font-bold text-rose-800">{{ atRiskStudentCount }}</span>
                    </div>
                    <Link href="/lecturer/attendance" class="text-xs font-medium text-rose-800 hover:text-rose-900 transition-colors mt-3 flex items-center gap-1">
                        View list
                        <span aria-hidden="true" class="text-sm leading-none">&rarr;</span>
                    </Link>
                </div>

            </div>
        </div>
        <div class="mb-8">
            <h2 class="text-xl font-bold text-slate-900 mb-4">Today's Schedule</h2>

            <div class="space-y-4">
                <template v-if="scheduleItems && scheduleItems.length > 0">
                    <div
                        v-for="item in scheduleItems"
                        :key="item.id"
                        class="flex flex-col md:flex-row md:items-center justify-between p-5 rounded-xl border transition-all"
                        :class="item.status === 'ongoing' ? 'border-orange-500 bg-white shadow-sm' : (item.status === 'cancelled' ? 'border-gray-200 bg-gray-50 opacity-75' : 'border-gray-200 bg-white')"
                    >
                        <div class="flex items-center gap-5">
                            <div
                                class="flex flex-col items-center justify-center w-20 h-16 rounded-xl border flex-shrink-0"
                                :class="item.status === 'ongoing' ? 'bg-orange-50 border-orange-200 text-orange-700' : 'bg-slate-50 border-slate-200 text-slate-600'"
                            >
                                <span class="text-[11px] font-bold leading-none">{{ item.time }}</span>
                                <div class="w-4 h-px bg-current my-1.5 opacity-20"></div>
                                <span class="text-[11px] font-bold leading-none">{{ item.end_time }}</span>
                            </div>

                            <div>
                                <div class="flex items-center mb-1">
                                    <span class="bg-slate-100 text-slate-700 text-xs font-bold px-2 py-1 rounded-md mr-3">
                                        {{ item.code }}
                                    </span>
                                    <h3 class="text-lg font-bold text-slate-900 leading-tight">
                                        {{ item.title }}
                                    </h3>
                                    <span v-if="item.status === 'cancelled'" class="ml-3 bg-rose-100 text-rose-700 text-[10px] uppercase tracking-wider font-bold px-2 py-0.5 rounded-full">
                                        Cancelled
                                    </span>
                                </div>

                                <div class="flex items-center text-sm text-slate-500 gap-4 mt-1.5">
                                    <span class="flex items-center gap-1.5">
                                        <MapPin class="w-4 h-4 text-slate-400" />
                                        {{ item.location }}
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <Users class="w-4 h-4 text-slate-400" />
                                        {{ item.students }} Students
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 md:mt-0 flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                            <button
                                :disabled="!item.is_in_class_time"
                                @click="toggleCancel(item.id)"
                                class="w-full sm:w-auto font-medium py-2.5 px-6 rounded-lg border transition-colors capitalize"
                                :class="!item.is_in_class_time
                                    ? 'bg-slate-50 text-slate-500 border-slate-200 cursor-not-allowed'
                                    : (item.is_cancelled
                                        ? 'bg-green-50 text-green-600 border-green-200 cursor-pointer hover:bg-green-100 focus:ring-4 focus:ring-green-100'
                                        : 'bg-red-50 text-red-600 border-red-200 cursor-pointer hover:bg-red-100 focus:ring-4 focus:ring-red-100')"
                            >
                                {{ item.is_cancelled ? 'Uncancel Class' : 'Cancel Class' }}
                            </button>

                            <button
                                v-if="item.status === 'ongoing' || item.status === 'past'"
                                @click="openAttendanceWindow(item)"
                                class="w-full sm:w-auto bg-orange-600 hover:bg-orange-700 text-white font-semibold py-2.5 px-6 rounded-lg transition-colors focus:ring-4 focus:ring-orange-600/20 cursor-pointer"
                            >
                                {{ item.status === 'ongoing' ? 'Start Attendance' : 'Attendance' }}
                            </button>

                            <button
                                v-else
                                disabled
                                class="w-full sm:w-auto bg-slate-50 text-slate-500 font-medium py-2.5 px-6 rounded-lg border border-slate-200 cursor-not-allowed capitalize"
                            >
                                {{ item.status }}
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 text-slate-400 mb-4">
                        <CalendarDays class="w-8 h-8" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-1">No classes scheduled for today</h3>
                    <p class="text-slate-500 text-sm max-w-xs mx-auto">
                        Your schedule is clear!
                    </p>
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
                                </div>
                            </div>
                        </div>

                        <!-- Live Stats -->
                        <div class="flex flex-col items-center justify-center text-center mt-4">
                            <div class="flex items-baseline gap-4">
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
                                            student.isPending ? 'bg-slate-400' : (
                                                student.status === 'present' ? 'bg-emerald-500' :
                                                student.status === 'absent' ? 'bg-rose-500' :
                                                'bg-orange-500'
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
    </AppLayout>
</template>

<style scoped>
.zoom-in-95 {
    transform: scale(0.95);
}
</style>
