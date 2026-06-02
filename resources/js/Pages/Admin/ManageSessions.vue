<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import {
    Calendar,
    List,
    MapPin,
    User,
    Users,
    Globe,
    Building2,
    ChevronLeft,
    ChevronRight,
    Plus,
    Filter,
    X,
    AlertTriangle,
    Check
} from 'lucide-vue-next';

const props = defineProps({
    sessions: { type: Array, default: () => [] },
    courses: { type: Array, default: () => [] },
    labs: { type: Array, default: () => [] },
    lecturers: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    weekStartDate: String,
    currentWeek: Number,
    semesterStart: String,
    semesterEnd: String,
    filters: Object
});

const showForm = ref(false);
const isEditing = ref(false);
const view = ref('week');

// --- Filter State ---
const activeFilters = ref({
    course_id: props.filters?.course_id || '',
    faculty: props.filters?.faculty || '',
    room_id: ''
});

const applyFilters = () => {
    router.get(route('admin.sessions.index'), {
        date: props.weekStartDate,
        course_id: activeFilters.value.course_id,
        faculty: activeFilters.value.faculty,
        room_id: activeFilters.value.room_id
    }, { preserveState: true, preserveScroll: true });
};

const clearFilters = () => {
    activeFilters.value.course_id = '';
    activeFilters.value.faculty = '';
    activeFilters.value.room_id = '';
    applyFilters();
};

const faculties = ['IT', 'BUSINESS', 'ENGINEERING'];

// --- Timetable Logic ---
const DAYS_NAMES = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const HOURS = Array.from({ length: 16 }, (_, i) => i + 8); // 8:00 to 23:00

const today = dayjs().format('YYYY-MM-DD');
const selectedRoomDate = ref(today);

const weekDates = computed(() => {
    const start = dayjs(props.weekStartDate);
    return DAYS_NAMES.map((day, index) => {
        const date = start.add(index, 'day');
        return {
            name: day,
            date: date.format('YYYY-MM-DD'),
            displayDate: date.format('D MMM')
        };
    });
});

const groupedSessions = computed(() => {
    return weekDates.value.reduce((acc, day) => {
        let daySessions = sessionsWithConflicts.value.filter(s => s.date === day.date);

        // Apply local room filter if set
        if (activeFilters.value.room_id) {
            daySessions = daySessions.filter(s => s.room_id == activeFilters.value.room_id);
        }

        // Sort sessions by start time for consistent lane assignment
        const sorted = [...daySessions].sort((a, b) => a.start.localeCompare(b.start));

        const lanes = [];
        const result = sorted.map(session => {
            let laneIndex = lanes.findIndex(laneEnd => session.start >= laneEnd);
            if (laneIndex === -1) {
                lanes.push(session.end);
                laneIndex = lanes.length - 1;
            } else {
                lanes[laneIndex] = session.end;
            }
            return { ...session, laneIndex };
        });

        acc[day.name] = {
            sessions: result,
            maxLanes: lanes.length
        };
        return acc;
    }, {});
});

const roomMatrixSessions = computed(() => {
    const targetDate = selectedRoomDate.value;
    return props.rooms.reduce((acc, room) => {
        const roomSessions = sessionsWithConflicts.value.filter(s => s.date === targetDate && s.room_id === room.id);
        acc[room.id] = roomSessions;
        return acc;
    }, {});
});

const sessionsWithConflicts = computed(() => {
    return props.sessions.map(s1 => {
        const hardConflicts = [];
        props.sessions.forEach(s2 => {
            if (s1.id === s2.id) return;
            if (s1.date !== s2.date) return;

            // Time overlap check
            const overlap = (s1.start < s2.end && s1.end > s2.start);
            if (!overlap) return;

            if (s1.room_id && s1.room_id === s2.room_id) hardConflicts.push('Room');
            if (s1.lecturer_id && s1.lecturer_id === s2.lecturer_id) hardConflicts.push('Lecturer');
            if (s1.lab_id && s1.lab_id === s2.lab_id) hardConflicts.push('Lab Group');
        });

        return {
            ...s1,
            hardConflicts: [...new Set(hardConflicts)]
        };
    });
});

const totalHardConflicts = computed(() => {
    return sessionsWithConflicts.value.filter(s => s.hardConflicts.length > 0).length / 2; // Divided by 2 because each conflict is counted twice (A vs B and B vs A)
});

const COLOR_MAP = {
    blue: 'bg-blue-50 border-blue-200 text-blue-800',
    emerald: 'bg-emerald-50 border-emerald-200 text-emerald-800',
    purple: 'bg-purple-50 border-purple-200 text-purple-800',
    orange: 'bg-orange-50 border-orange-200 text-orange-800',
    rose: 'bg-rose-50 border-rose-200 text-rose-800',
    slate: 'bg-slate-50 border-slate-200 text-slate-800',
    indigo: 'bg-indigo-50 border-indigo-200 text-indigo-800',
    cyan: 'bg-cyan-50 border-cyan-200 text-cyan-800',
};

const displayHours = computed(() => HOURS.slice(0, -1));

const weekRangeDisplay = computed(() => {
    const start = dayjs(weekDates.value[0].date);
    const end = dayjs(weekDates.value[6].date);
    return `${start.format('MMM D')} - ${end.format('MMM D, YYYY')}`;
});

const canNavigatePrev = computed(() => dayjs(props.weekStartDate).isAfter(dayjs(props.semesterStart)));
const canNavigateNext = computed(() => dayjs(weekDates.value[6].date).isBefore(dayjs(props.semesterEnd)));

const timeToPercent = (timeStr) => {
    const [hours, mins] = timeStr.split(':').map(Number);
    const totalMins = (hours - 8) * 60 + mins;
    const maxMins = 15 * 60;
    return (totalMins / maxMins) * 100;
};

const getDurationPercent = (start, end) => timeToPercent(end) - timeToPercent(start);

const navigateWeek = (direction) => {
    let targetDate;
    if (direction === 'prev') {
        targetDate = dayjs(props.weekStartDate).subtract(7, 'day');
    } else if (direction === 'next') {
        targetDate = dayjs(props.weekStartDate).add(7, 'day');
    } else {
        targetDate = dayjs();
    }
    router.get(route('admin.sessions.index'), {
        date: targetDate.format('YYYY-MM-DD'),
        course_id: activeFilters.value.course_id,
        faculty: activeFilters.value.faculty
    }, { preserveState: true });
};

// --- CRUD Handlers ---
const form = useForm({
    id: null,
    course_id: '',
    lab_id: '',
    lecturer_id: '',
    room_id: '',
    start_time: '',
    end_time: '',
    mode: 'physical',
    checkin_method: 'qr'
});

const filteredLabs = computed(() => {
    if (!form.course_id) return [];
    return props.labs.filter(lab => lab.course_id === form.course_id);
});

const editSession = (session) => {
    isEditing.value = true;
    form.id = session.id;
    form.course_id = session.course_id;
    form.lab_id = session.lab_id;
    form.lecturer_id = session.lecturer_id || '';
    form.room_id = session.room_id || '';
    form.start_time = dayjs(session.start_time).format('YYYY-MM-DDTHH:mm');
    form.end_time = dayjs(session.end_time).format('YYYY-MM-DDTHH:mm');
    form.mode = session.mode;
    form.checkin_method = session.checkin_method;
    showForm.value = true;
};

const deleteSession = (id) => {
    if (confirm('Are you sure you want to remove this session?')) {
        router.post(`/admin/sessions/${id}`, { preserveScroll: true });
    }
};

const submitSession = () => {
    if (isEditing.value) {
        form.post(`/admin/sessions/update/${form.id}`, {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('admin.sessions.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    }
};

const closeModal = () => {
    showForm.value = false;
    isEditing.value = false;
    form.reset();
    form.clearErrors();
};

const formatTime = (time) => dayjs(time).format('h:mm A');
const formatDate = (time) => dayjs(time).format('ddd, D MMM');
</script>

<template>
    <AdminLayout>
        <Head title="Timetable Management" />

        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-6 mb-8">
            <div>
                <h1 class="text-2xl font-semibold mb-2 text-gray-900">Timetable Management</h1>
                <p class="text-slate-500 text-sm font-medium mt-0.5">Orchestrate classroom schedules and lecturer availability.</p>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-4 w-full lg:w-auto">
                <!-- Navigation -->
                <div class="flex items-center bg-slate-100/80 p-1 rounded-lg border border-slate-200/60">
                    <button @click="navigateWeek('prev')" :disabled="!canNavigatePrev" class="p-1.5 rounded-md transition-all text-slate-500 hover:text-slate-900 hover:bg-white disabled:opacity-30 disabled:cursor-not-allowed">
                        <ChevronLeft class="w-5 h-5" />
                    </button>
                    <button @click="navigateWeek('today')" class="px-3 py-1.5 text-xs font-bold text-slate-600 hover:text-orange-600 transition-colors uppercase tracking-wider">
                        Week {{ currentWeek }}
                    </button>
                    <button @click="navigateWeek('next')" :disabled="!canNavigateNext" class="p-1.5 rounded-md transition-all text-slate-500 hover:text-slate-900 hover:bg-white disabled:opacity-30 disabled:cursor-not-allowed">
                        <ChevronRight class="w-5 h-5" />
                    </button>
                </div>

                <div class="text-sm font-bold text-slate-700 bg-slate-50 px-4 py-2 rounded-lg border border-slate-200">
                    {{ weekRangeDisplay }}
                </div>

                <button @click="isEditing = false; showForm = true" class="bg-orange-600 hover:bg-orange-700 text-white px-5 py-2 rounded-lg font-bold  transition flex items-center gap-2 text-sm">
                    <Plus class="w-4 h-4" />
                    NEW SLOT
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 mb-6 flex flex-wrap items-center gap-4 ">
            <div class="flex items-center gap-2 text-slate-500 mr-2">
                <Filter class="w-4 h-4" />
                <span class="text-xs font-bold uppercase tracking-widest">Filters</span>
            </div>

            <div class="flex-1 min-w-[200px]">
                <select v-model="activeFilters.faculty" @change="applyFilters" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-xs focus:ring-orange-600 focus:border-orange-600 py-2">
                    <option value="">All Faculties</option>
                    <option v-for="f in faculties" :key="f" :value="f">{{ f }}</option>
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <select v-model="activeFilters.course_id" @change="applyFilters" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-xs focus:ring-orange-600 focus:border-orange-600 py-2">
                    <option value="">All Subjects</option>
                    <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.code }} - {{ c.name }}</option>
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <select v-model="activeFilters.room_id" @change="applyFilters" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-xs focus:ring-orange-600 focus:border-orange-600 py-2">
                    <option value="">All Rooms</option>
                    <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
            </div>

            <button v-if="activeFilters.course_id || activeFilters.faculty || activeFilters.room_id" @click="clearFilters" class="flex items-center gap-1.5 px-3 py-2 text-rose-600 hover:bg-rose-50 rounded-lg transition text-xs font-bold">
                <X class="w-3.5 h-3.5" />
                CLEAR
            </button>
        </div>

        <!-- Conflict Summary Alert -->
        <div v-if="Math.ceil(totalHardConflicts) > 0" class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-xl flex items-center justify-between  animate-pulse">
            <div class="flex items-center gap-3">
                <div class="bg-rose-100 p-2 rounded-lg">
                    <AlertTriangle class="w-5 h-5 text-rose-600" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-rose-900">Scheduling Errors Detected</h3>
                    <p class="text-xs text-rose-700">There are {{ Math.ceil(totalHardConflicts) }} detected conflicts where rooms or lecturers are double-booked.</p>
                </div>
            </div>
            <button @click="view = 'list'" class="text-xs font-bold bg-rose-600 text-white px-3 py-1.5 rounded-lg hover:bg-rose-700 transition">
                REVIEW LIST
            </button>
        </div>

        <!-- Timetable View Selector -->
        <div class="flex bg-slate-100/80 p-1 rounded-lg border border-slate-200/60 w-fit mb-6">
            <button @click="view = 'week'" :class="['flex items-center gap-2 px-5 py-2 rounded-md text-sm font-semibold transition-all', view === 'week' ? 'bg-white text-orange-600 border border-slate-200/50 ' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-200/50']">
                <Calendar class="w-4 h-4" /> Week
            </button>
            <button @click="view = 'room'" :class="['flex items-center gap-2 px-5 py-2 rounded-md text-sm font-semibold transition-all', view === 'room' ? 'bg-white text-orange-600 border border-slate-200/50 ' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-200/50']">
                <MapPin class="w-4 h-4" /> Room Matrix
            </button>
            <button @click="view = 'list'" :class="['flex items-center gap-2 px-5 py-2 rounded-md text-sm font-semibold transition-all', view === 'list' ? 'bg-white text-orange-600 border border-slate-200/50 ' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-200/50']">
                <List class="w-4 h-4" /> List
            </button>
        </div>

        <!-- Room Matrix View -->
        <div v-if="view === 'room'" class="space-y-4 mb-12">
            <div class="flex items-center gap-4 bg-white p-4 rounded-xl border border-slate-200 ">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Select Date:</span>
                <input type="date" v-model="selectedRoomDate" class="bg-slate-50 border-slate-200 rounded-lg text-sm px-3 py-1.5 focus:ring-orange-500 focus:border-orange-500">
            </div>

            <div class="border border-slate-200 rounded-xl bg-white overflow-hidden ">
                <div class="overflow-x-auto">
                    <div class="min-w-[1200px]">
                        <div class="flex border-b border-slate-200 bg-slate-50/50">
                            <div class="w-32 flex-shrink-0 border-r border-slate-200 p-2 bg-white flex items-center justify-center">
                                <span class="text-[10px] font-black text-slate-400 uppercase">Room</span>
                            </div>
                            <div class="flex-1 relative flex">
                                <div v-for="hour in displayHours" :key="'room-h-'+hour" class="flex-1 p-2 text-[11px] font-bold text-slate-400 border-r border-slate-200 last:border-r-0 text-center uppercase tracking-tighter">
                                    {{ String(hour).padStart(2, '0') }}:00
                                </div>
                            </div>
                        </div>

                        <div class="relative">
                            <div class="absolute inset-0 flex ml-32 pointer-events-none">
                                <div v-for="hour in displayHours" :key="'room-line-' + hour" class="flex-1 border-r border-slate-100 border-dashed last:border-r-0"></div>
                            </div>

                            <div v-for="room in rooms" :key="'matrix-room-'+room.id" class="flex group border-b border-slate-100 last:border-b-0 min-h-[80px]">
                                <div class="w-32 flex-shrink-0 border-r border-slate-200 p-3 flex flex-col items-center justify-center z-10 bg-white group-hover:bg-slate-50 transition-colors">
                                    <span class="text-xs font-bold text-slate-800 text-center leading-tight">{{ room.name }}</span>
                                    <span class="text-[9px] text-slate-400 mt-1 uppercase font-bold tracking-widest">Cap: {{ room.capacity || 'N/A' }}</span>
                                </div>

                                <div class="flex-1 relative h-[80px] group-hover:bg-slate-50/30 transition-colors">
                                    <div v-for="session in roomMatrixSessions[room.id]" :key="'matrix-sess-'+session.id"
                                         @click="editSession(session)"
                                         :class="['absolute top-2 bottom-2 rounded-lg border p-2 overflow-hidden hover:shadow-md transition-all cursor-pointer hover:z-20 hover:scale-[1.01]',
                                                  session.hardConflicts.length > 0 ? 'bg-rose-50 border-rose-300 text-rose-900 border-2' : COLOR_MAP[session.color]]"
                                         :style="{
                                             left: timeToPercent(session.start) + '%',
                                             width: `calc(${getDurationPercent(session.start, session.end)}% - 4px)`,
                                             marginLeft: '2px'
                                         }">
                                        <div class="flex justify-between items-start">
                                            <div class="font-bold text-[10px] leading-tight truncate mr-2">{{ session.courseCode }}</div>
                                            <AlertTriangle v-if="session.hardConflicts.length > 0" class="w-3 h-3 text-rose-600" />
                                        </div>
                                        <div class="text-[9px] opacity-90 truncate font-medium mt-0.5">{{ session.instructor }}</div>
                                        <div class="text-[8px] opacity-70 truncate mt-1">{{ formatTime(session.start_time) }} - {{ formatTime(session.end_time) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!rooms.length" class="p-12 text-center text-slate-400 text-sm font-medium">
                                No rooms configured in the system.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Week View (Horizontal Grid) -->
        <div v-if="view === 'week'" class="border border-slate-200 rounded-xl bg-white overflow-hidden mb-12 ">
            <div class="overflow-x-auto">
                <div class="min-w-[1200px]">
                    <div class="flex border-b border-slate-200 bg-slate-50/50">
                        <div class="w-24 flex-shrink-0 border-r border-slate-200 p-2 bg-white"></div>
                        <div class="flex-1 relative flex">
                            <div v-for="hour in displayHours" :key="hour" class="flex-1 p-2 text-[11px] font-bold text-slate-400 border-r border-slate-200 last:border-r-0 text-center uppercase tracking-tighter">
                                {{ String(hour).padStart(2, '0') }}:00
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="absolute inset-0 flex ml-24 pointer-events-none">
                            <div v-for="hour in displayHours" :key="'line-' + hour" class="flex-1 border-r border-slate-100 border-dashed last:border-r-0"></div>
                        </div>

                        <div v-for="day in weekDates" :key="day.date" class="flex group border-b border-slate-100 last:border-b-0" :style="{ minHeight: Math.max(110, groupedSessions[day.name].maxLanes * 60) + 'px' }">
                            <div :class="['w-24 flex-shrink-0 border-r border-slate-200 p-2 flex flex-col items-center justify-center z-10 bg-white transition-colors relative', day.date === today ? 'text-orange-600 bg-orange-50/10' : 'text-slate-600 group-hover:bg-slate-50/50']">
                                <div v-if="day.date === today" class="absolute left-0 top-0 bottom-0 w-1 bg-orange-500 rounded-r"></div>
                                <span class="text-[10px] font-bold uppercase tracking-tight opacity-60">{{ day.name.slice(0, 3) }}</span>
                                <span class="text-sm font-bold">{{ day.displayDate.split(' ')[0] }}</span>
                                <span class="text-[10px] font-medium opacity-60">{{ day.displayDate.split(' ')[1] }}</span>
                            </div>

                            <div class="flex-1 relative group-hover:bg-slate-50/30 transition-colors">
                                <div v-for="session in groupedSessions[day.name].sessions" :key="session.id"
                                     @click="editSession(session)"
                                     :class="['absolute rounded-lg border p-2 overflow-hidden hover:shadow-md transition-all cursor-pointer hover:z-20 hover:scale-[1.01]',
                                              session.hardConflicts.length > 0 ? 'bg-rose-50 border-rose-300 text-rose-900 border-2' : COLOR_MAP[session.color],
                                              session.isOngoing ? 'border-2 border-orange-400 z-30 shadow-md shadow-orange-500/20' : '']"
                                     :style="{
                                         left: timeToPercent(session.start) + '%',
                                         width: `calc(${getDurationPercent(session.start, session.end)}% - 4px)`,
                                         marginLeft: '2px',
                                         top: (session.laneIndex * 55 + 8) + 'px',
                                         height: '50px'
                                     }">
                                    <div class="flex justify-between items-start">
                                        <div class="font-bold text-[10px] leading-tight truncate mr-2">{{ session.courseCode }}</div>
                                        <div v-if="session.hardConflicts.length > 0" class="flex gap-0.5">
                                            <AlertTriangle class="w-3.5 h-3.5 text-rose-600 animate-bounce" />
                                        </div>
                                    </div>
                                    <div class="text-[9px] opacity-80 truncate font-medium mt-0.5">{{ session.title }}</div>
                                    <div class="flex items-center gap-2 mt-1 opacity-90">
                                        <div class="text-[8px] font-bold flex items-center gap-0.5 uppercase">
                                            <Building2 class="w-2.5 h-2.5" /> {{ session.location }}
                                        </div>
                                        <div class="text-[8px] font-medium flex items-center gap-0.5 truncate">
                                            <User class="w-2.5 h-2.5" /> {{ session.instructor }}
                                        </div>
                                    </div>

                                    <!-- Conflict Tooltip-like Info -->
                                    <div v-if="session.hardConflicts.length > 0" class="absolute inset-0 bg-rose-600/10 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                        <span class="bg-rose-600 text-white text-[8px] px-1 rounded font-bold uppercase tracking-tighter">
                                            Double Booked: {{ session.hardConflicts.join(', ') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- List View (Session Directory) -->
        <div v-else class="bg-white rounded-xl  border border-gray-200 overflow-hidden mb-12">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
                <h2 class="text-lg font-bold text-slate-900">Session Directory</h2>
                <div class="text-xs font-bold text-slate-500 px-3 py-1 bg-slate-100 rounded-full">
                    {{ sessions.length }} Sessions Total
                </div>
            </div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100 text-[11px] uppercase tracking-widest text-gray-500 font-bold">
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Session Detail</th>
                        <th class="px-6 py-4">Lecturer / Room</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="session in sessionsWithConflicts" :key="session.id" :class="['hover:bg-gray-50/50 transition', session.hardConflicts.length > 0 ? 'bg-rose-50/30' : '']">
                        <td class="px-6 py-4">
                            <div v-if="session.hardConflicts.length > 0" class="flex items-center gap-1.5 text-rose-600">
                                <AlertTriangle class="w-4 h-4" />
                                <span class="text-[10px] font-black uppercase tracking-widest">Conflict</span>
                            </div>
                            <div v-else class="flex items-center gap-1.5 text-emerald-600">
                                <Check class="w-4 h-4" />
                                <span class="text-[10px] font-black uppercase tracking-widest">Valid</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-900">{{ session.courseCode }} - {{ session.title }}</p>
                            <p class="text-[11px] text-gray-500 font-medium">
                                {{ formatDate(session.start_time) }} | {{ formatTime(session.start_time) }}
                            </p>
                            <div v-if="session.hardConflicts.length > 0" class="mt-1 flex gap-1">
                                <span v-for="c in session.hardConflicts" :key="c" class="text-[8px] bg-rose-600 text-white px-1.5 py-0.5 rounded font-black uppercase">{{ c }} Conflict</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <p class="font-medium text-slate-600">{{ session.instructor }}</p>
                            <p class="text-xs text-orange-700 font-bold uppercase">{{ session.location }}</p>
                        </td>
                        <td class="px-6 py-4 text-right space-x-3">
                            <button @click="editSession(session)" class="text-slate-400 hover:text-orange-600 transition">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                            <button @click="deleteSession(session.id)" class="text-slate-400 hover:text-rose-600 transition">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!sessions.length">
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500 font-medium">No sessions scheduled for this week.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showForm" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="text-xl font-bold text-slate-900">{{ isEditing ? 'Edit' : 'Add' }} Class Session</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div v-if="$page.props.errors?.conflict" class="m-6 p-4 bg-rose-50 text-rose-800 rounded-xl border border-rose-100  flex items-start gap-3">
                    <svg class="w-5 h-5 text-rose-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <p class="font-bold text-sm">Scheduling Conflict</p>
                        <p class="text-xs mt-1">{{ $page.props.errors.conflict }}</p>
                    </div>
                </div>

                <form @submit.prevent="submitSession" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Course</label>
                            <select v-model="form.course_id" class="w-full bg-[#f8fafc] border-gray-200 rounded-xl text-sm focus:ring-orange-600 focus:border-orange-600 py-2.5" required>
                                <option value="">Select Course</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} - {{ course.name }}</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Lab Group</label>
                            <select v-model="form.lab_id" class="w-full bg-[#f8fafc] border-gray-200 rounded-xl text-sm focus:ring-orange-600 focus:border-orange-600 py-2.5" required>
                                <option value="">Select Lab</option>
                                <option v-for="lab in filteredLabs" :key="lab.id" :value="lab.id">{{ lab.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Lecturer</label>
                            <select v-model="form.lecturer_id" class="w-full bg-[#f8fafc] border-gray-200 rounded-xl text-sm focus:ring-orange-600 focus:border-orange-600 py-2.5">
                                <option value="">Select...</option>
                                <option v-for="l in lecturers" :key="l.id" :value="l.id">{{ l.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Room</label>
                            <select v-model="form.room_id" class="w-full bg-[#f8fafc] border-gray-200 rounded-xl text-sm focus:ring-orange-600 focus:border-orange-600 py-2.5">
                                <option value="">Select...</option>
                                <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Start Time</label>
                            <input type="datetime-local" v-model="form.start_time" class="w-full bg-[#f8fafc] border-gray-200 rounded-xl text-sm focus:ring-orange-600 focus:border-orange-600 py-2.5" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">End Time</label>
                            <input type="datetime-local" v-model="form.end_time" class="w-full bg-[#f8fafc] border-gray-200 rounded-xl text-sm focus:ring-orange-600 focus:border-orange-600 py-2.5" required>
                        </div>
                    </div>
                    <div class="pt-4 flex justify-end gap-3 border-t border-gray-50 mt-4">
                        <button type="button" @click="closeModal" class="px-6 py-2.5 text-slate-600 font-bold hover:text-slate-800 transition">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-orange-600 text-white rounded-xl font-bold  hover:bg-orange-700 transition disabled:opacity-50">
                            {{ isEditing ? 'Update' : 'Save' }} Session
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
