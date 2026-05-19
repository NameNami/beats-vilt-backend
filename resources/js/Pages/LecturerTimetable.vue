<script setup>
import AppLayout from '../Layouts/AppLayout.vue';
import {Head, Link, usePage} from '@inertiajs/vue3';
import {computed, ref} from 'vue';
import { Calendar, List, Clock, MapPin, User, Tag, Users, Globe, Building2, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    sessions: Array,
    weekStartDate: String,
    currentWeek: Number,
    semesterStart: String,
    semesterEnd: String
});

// TODO: add new pop up window when user clicks on a class
// TODO: add labs to the schedule
    // --- State & Helpers ---
const DAYS_NAMES = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const HOURS = Array.from({ length: 16 }, (_, i) => i + 8); // 8:00 to 23:00 (15 hours)

const today = new Date();
const TODAY_DATE = today.toISOString().split('T')[0];
const CURRENT_DAY = new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(today);

const weekDates = computed(() => {
    const start = new Date(props.weekStartDate);
    return DAYS_NAMES.map((day, index) => {
        const date = new Date(start);
        date.setDate(start.getDate() + index);
        return {
            name: day,
            date: date.toISOString().split('T')[0],
            displayDate: date.toLocaleDateString('en-US', { day: 'numeric', month: 'short' })
        };
    });
});

const SCHEDULE_DATA = computed(() => props.sessions || []);

const groupedSessions = computed(() => {
    return weekDates.value.reduce((acc, day) => {
        acc[day.name] = SCHEDULE_DATA.value.filter(event => event.date === day.date);
        return acc;
    }, {});
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

    // --- State ---
const view = ref('week');
const selectedDay = ref(CURRENT_DAY);

    // --- Computed Properties ---
const displayHours = computed(() => HOURS.slice(0, -1));

const selectedDayData = computed(() => weekDates.value.find(d => d.name === selectedDay.value));

const isCurrentDay = computed(() => selectedDayData.value?.date === TODAY_DATE);

const dayEvents = computed(() => {
    const date = selectedDayData.value?.date;
    return SCHEDULE_DATA.value
    .filter((e) => e.date === date)
    .sort((a, b) => a.start.localeCompare(b.start));
});

const weekRangeDisplay = computed(() => {
    const start = new Date(weekDates.value[0].date);
    const end = new Date(weekDates.value[6].date);
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    return `${start.toLocaleDateString('en-US', options)} - ${end.toLocaleDateString('en-US', options)}`;
});

const canNavigatePrev = computed(() => {
    const currentStart = new Date(props.weekStartDate);
    const semesterStart = new Date(props.semesterStart);
    return currentStart > semesterStart;
});

const canNavigateNext = computed(() => {
    const currentEnd = new Date(weekDates.value[6].date);
    const semesterEnd = new Date(props.semesterEnd);
    return currentEnd < semesterEnd;
});

    // --- Methods ---
const timeToPercent = (timeStr) => {
    const [hours, mins] = timeStr.split(':').map(Number);
    const totalMins = (hours - 8) * 60 + mins;
    const maxMins = 15 * 60; // 8:00 to 23:00 is 15 hours
    return (totalMins / maxMins) * 100;
};

const getDurationPercent = (start, end) => {
    return timeToPercent(end) - timeToPercent(start);
};

const navigateWeek = (direction) => {
    if (direction === 'prev' && !canNavigatePrev.value) return;
    if (direction === 'next' && !canNavigateNext.value) return;

    const current = new Date(props.weekStartDate);
    if (direction === 'prev') {
        current.setDate(current.getDate() - 7);
    } else if (direction === 'next') {
        current.setDate(current.getDate() + 7);
    } else if (direction === 'today') {
        router.get(route('lecturer.timetable'), {}, { preserveState: true });
        return;
    }

    router.get(route('lecturer.timetable'), { date: current.toISOString().split('T')[0] }, { preserveState: true });
};

</script>

<template>
    <Head>
        <title>Dashboard</title>
    </Head>
    <AppLayout>
        <div class="min-h-screen bg-white text-slate-900 font-sans p-0">
            <div class="w-full">

                <!-- Header Area -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-[#0f172a]">Timetable</h1>
                        <p class="text-slate-500 text-sm font-medium mt-0.5">Manage your weekly schedule and classes.</p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto">
                        <!-- Week Navigation -->
                        <div class="flex items-center bg-slate-100/80 p-1 rounded-lg border border-slate-200/60 shadow-sm">
                            <button
                                @click="navigateWeek('prev')"
                                :disabled="!canNavigatePrev"
                                :class="[
                                    'p-1.5 rounded-md transition-all',
                                    canNavigatePrev 
                                        ? 'text-slate-500 hover:text-slate-900 hover:bg-white hover:shadow-sm' 
                                        : 'text-slate-300 cursor-not-allowed'
                                ]"
                                title="Previous Week"
                            >
                                <ChevronLeft class="w-5 h-5" />
                            </button>
                            
                            <button
                                @click="navigateWeek('today')"
                                class="px-3 py-1.5 text-xs font-bold text-slate-600 hover:text-orange-600 transition-colors uppercase tracking-wider"
                            >
                                Week {{ currentWeek }}
                            </button>

                            <button
                                @click="navigateWeek('next')"
                                :disabled="!canNavigateNext"
                                :class="[
                                    'p-1.5 rounded-md transition-all',
                                    canNavigateNext 
                                        ? 'text-slate-500 hover:text-slate-900 hover:bg-white hover:shadow-sm' 
                                        : 'text-slate-300 cursor-not-allowed'
                                ]"
                                title="Next Week"
                            >
                                <ChevronRight class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="text-sm font-bold text-slate-700 bg-slate-50 px-4 py-2 rounded-lg border border-slate-200">
                            {{ weekRangeDisplay }}
                        </div>

                        <!-- View Toggles -->
                        <div class="flex bg-slate-100/80 p-1 rounded-lg border border-slate-200/60">
                            <button
                                @click="view = 'week'"
                                :class="[
                  'flex items-center gap-2 px-5 py-2 rounded-md text-sm font-semibold transition-all',
                  view === 'week'
                    ? 'bg-white text-orange-600 shadow-sm border border-slate-200/50'
                    : 'text-slate-500 hover:text-slate-900 hover:bg-slate-200/50 border border-transparent'
                ]"
                            >
                                <Calendar class="w-4 h-4" />
                                Week
                            </button>
                            <button
                                @click="view = 'day'"
                                :class="[
                  'flex items-center gap-2 px-5 py-2 rounded-md text-sm font-semibold transition-all',
                  view === 'day'
                    ? 'bg-white text-orange-600 shadow-sm border border-slate-200/50'
                    : 'text-slate-500 hover:text-slate-900 hover:bg-slate-200/50 border border-transparent'
                ]"
                            >
                                <List class="w-4 h-4" />
                                Day
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Content Area -->

                <!-- Week View -->
                <div v-if="view === 'week'" class="mt-4 border border-slate-200 rounded-xl bg-white overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <div class="min-w-[1200px]">
                            <!-- Header Row (Time Slots) -->
                            <div class="flex border-b border-slate-200 bg-slate-50/50">
                                <div class="w-24 flex-shrink-0 border-r border-slate-200 p-2 text-sm font-semibold text-slate-500 bg-white">
                                    <!-- Empty corner cell -->
                                </div>
                                <div class="flex-1 relative flex">
                                    <div v-for="hour in displayHours" :key="hour" class="flex-1 p-2 text-sm font-medium text-slate-500 border-r border-slate-200 last:border-r-0 text-center">
                                        {{ String(hour).padStart(2, '0') }}:00
                                    </div>
                                </div>
                            </div>

                            <!-- Grid Body -->
                            <div class="relative">
                                <!-- Background Vertical Grid Lines -->
                                <div class="absolute inset-0 flex ml-24 pointer-events-none">
                                    <div v-for="hour in displayHours" :key="'line-' + hour" class="flex-1 border-r border-slate-100 border-dashed last:border-r-0"></div>
                                </div>

                                <!-- Day Rows -->
                                <div v-for="day in weekDates" :key="day.date" class="flex group border-b border-slate-100 last:border-b-0">
                                    <!-- Day Label -->
                                    <div :class="[
                  'w-24 flex-shrink-0 border-r border-slate-200 p-2 flex flex-col items-center justify-center z-10 bg-white transition-colors relative',
                  day.date === TODAY_DATE ? 'text-orange-600 bg-orange-50/10' : 'text-slate-600 group-hover:bg-slate-50/50'
                ]">
                                        <div v-if="day.date === TODAY_DATE" class="absolute left-0 top-0 bottom-0 w-1 bg-orange-300 rounded-r"></div>
                                        <span class="text-[10px] font-bold uppercase tracking-tight opacity-60">{{ day.name.slice(0, 3) }}</span>
                                        <span class="text-sm font-bold">{{ day.displayDate.split(' ')[0] }}</span>
                                        <span class="text-[10px] font-medium opacity-60">{{ day.displayDate.split(' ')[1] }}</span>
                                    </div>

                                    <!-- Event Container for the Day -->
                                    <div class="flex-1 relative h-[110px] group-hover:bg-slate-50/30 transition-colors">
                                        <div
                                            v-for="event in groupedSessions[day.name]"
                                            :key="event.id"
                                            :class="[
                      'absolute top-2 bottom-2 rounded-lg border p-2 overflow-hidden shadow-sm hover:shadow-md transition-all cursor-pointer hover:z-20 hover:scale-[1.01]',
                      COLOR_MAP[event.color],
                      event.isOngoing ? 'border-2 border-orange-300 z-30 shadow-md shadow-orange-50/50' : ''
                    ]"
                                            :style="{
                      left: timeToPercent(event.start) + '%',
                      width: `calc(${getDurationPercent(event.start, event.end)}% - 4px)`,
                      marginLeft: '2px'
                    }"
                                            :title="`${event.title}\n${event.start} - ${event.end}\n${event.location}`"
                                        >
                                            <div :class="['font-semibold text-[13px] leading-tight whitespace-nowrap truncate', event.isCancelled ? 'line-through opacity-50' : '']">{{ event.title }}</div>
                                            <div class="flex items-center gap-1.5 mt-1">
                                                <span :class="[
                                                    'px-1.5 py-0.5 text-[9px] font-bold rounded uppercase flex items-center gap-1',
                                                    event.mode === 'online' ? 'bg-blue-100 text-blue-600' : 'bg-emerald-100 text-emerald-600'
                                                ]">
                                                    <Globe v-if="event.mode === 'online'" class="w-2.5 h-2.5" />
                                                    <Building2 v-else class="w-2.5 h-2.5" />
                                                    {{ event.mode }}
                                                </span>
                                                <div v-if="event.isCancelled" class="px-1.5 py-0.5 bg-red-100 text-red-600 text-[9px] font-bold rounded uppercase">
                                                    Cancelled
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-1 mt-1.5 opacity-90">
                                                <div class="text-[10px] font-medium flex items-center gap-1.5">
                                                    <Clock class="w-3 h-3" />
                                                    {{ event.start }} - {{ event.end }}
                                                </div>
                                                <div class="text-[10px] font-medium flex items-center gap-1.5 truncate">
                                                    <MapPin class="w-3 h-3" />
                                                    {{ event.location }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Day View -->
                <div v-else class="mt-4">
                    <!-- Day Selector Tabs -->
                    <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                        <button
                            v-for="day in weekDates"
                            :key="day.date"
                            @click="selectedDay = day.name"
                            :class="[
              'relative px-5 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap flex flex-col items-center min-w-[100px]',
              selectedDay === day.name
                ? 'bg-orange-500 text-white shadow-md shadow-orange-500/20'
                : 'bg-white text-slate-600 border border-slate-200 hover:border-slate-300 hover:bg-slate-50'
            ]"
                        >
                            <span class="text-[10px] uppercase tracking-wider opacity-80">{{ day.name }}</span>
                            <span class="text-base font-bold">{{ day.displayDate }}</span>
                            <span v-if="day.date === TODAY_DATE && selectedDay !== day.name" class="absolute top-2 right-2 w-2 h-2 rounded-full bg-orange-500"></span>
                        </button>
                    </div>

                    <!-- Event List -->
                    <div class="space-y-3 mt-4">
                        <h2 v-if="isCurrentDay && dayEvents.length > 0" class="text-2xl font-extrabold text-slate-900 tracking-tight mb-4 mt-1">
                            Today's Schedule
                        </h2>

                        <div v-if="dayEvents.length === 0" class="text-center py-16 bg-slate-50 rounded-xl border border-slate-200 border-dashed">
                            <p class="text-slate-500 font-medium">No classes scheduled for {{ selectedDay }}.</p>
                        </div>

                        <div
                            v-else
                            v-for="event in dayEvents"
                            :key="event.id"
                            :class="[
              'flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 p-4 sm:p-5 bg-white rounded-2xl border transition-all hover:shadow-md',
              event.isOngoing ? 'border-orange-500 ring-1 ring-orange-500 shadow-lg shadow-orange-100' : (isCurrentDay ? 'border-orange-200' : 'border-slate-200')
            ]"
                        >
                            <!-- Time Box -->
                            <div :class="[
              'w-full sm:w-24 sm:h-24 py-3 sm:py-0 rounded-2xl border flex flex-row sm:flex-col justify-center items-center shrink-0',
              isCurrentDay ? 'border-orange-200/70 bg-orange-50/50 text-orange-700' : 'border-slate-200 bg-slate-50 text-slate-600'
            ]">
                                <span class="text-[14px] font-bold">{{ event.start }}</span>
                                <div :class="['hidden sm:block w-6 border-b-2 my-1.5', isCurrentDay ? 'border-orange-200' : 'border-slate-200']"></div>
                                <span class="text-[14px] font-bold sm:hidden mx-2">-</span>
                                <span class="text-[14px] font-bold">{{ event.end }}</span>
                            </div>

                            <!-- Details Column -->
                            <div class="flex-1 w-full">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 mb-3 sm:mb-2">
                <span class="w-fit px-2.5 py-1 bg-slate-100 text-slate-700 text-xs font-bold rounded-md tracking-wide">
                  {{ event.courseCode }}
                </span>
                                    <h3 :class="['text-xl font-bold text-slate-900', event.isCancelled ? 'line-through opacity-50' : '']">{{ event.title }}</h3>
                                    <div class="flex items-center gap-2">
                                        <span :class="[
                                            'px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase flex items-center gap-1',
                                            event.mode === 'online' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'
                                        ]">
                                            <Globe v-if="event.mode === 'online'" class="w-3 h-3" />
                                            <Building2 v-else class="w-3 h-3" />
                                            {{ event.mode }}
                                        </span>
                                        <span v-if="event.isCancelled" class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded-full">CANCELLED</span>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-5 text-sm text-slate-500 mt-1">
                                    <div class="flex items-center gap-2">
                                        <MapPin class="w-4 h-4 text-slate-400" />
                                        {{ event.location }}
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Users class="w-4 h-4 text-slate-400" />
                                        {{ event.students }} Students
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <User class="w-4 h-4 text-slate-400" />
                                        {{ event.instructor }}
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
/* Optional: Hide scrollbar for the day tabs to match the React setup */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
