<script setup>
import AppLayout from '../Layouts/AppLayout.vue';
import {Head, Link, usePage, router} from '@inertiajs/vue3';
import {computed, ref} from 'vue';

const props = defineProps({
    overallAttendance: String,
    classTodayCount: String,
    pendingLeaveCount: String,
    atRiskStudentCount: String,
    scheduleItems: Array,
});

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

const toggleCancel = (id) => {
    if (confirm('Are you sure you want to change the status of this class?')) {
        router.post(route('lecturer.sessions.toggle-cancel', id));
    }
};

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
                        :class="item.status === 'ongoing' ? 'border-amber-500 bg-white shadow-sm' : (item.status === 'cancelled' ? 'border-gray-200 bg-gray-50 opacity-75' : 'border-gray-200 bg-white')"
                    >
                        <div class="flex items-center gap-5">
                            <div
                                class="flex flex-col items-center justify-center w-20 h-16 rounded-xl border flex-shrink-0"
                                :class="item.status === 'ongoing' ? 'bg-orange-50 border-orange-200 text-amber-700' : 'bg-slate-50 border-slate-200 text-slate-600'"
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
                                        <font-awesome-icon icon="fa-solid fa-location-dot" class="text-slate-400" />
                                        {{ item.location }}
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <font-awesome-icon icon="fa-solid fa-user-group" class="text-slate-400" />
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
                                class="w-full sm:w-auto bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2.5 px-6 rounded-lg transition-colors focus:ring-4 focus:ring-amber-600/20 cursor-pointer"
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
                        <font-awesome-icon icon="fa-solid fa-calendar-day" class="text-2xl" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-1">No classes scheduled for today</h3>
                    <p class="text-slate-500 text-sm max-w-xs mx-auto">
                        Your schedule is clear!
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
