<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Calendar, Clock, FileText, Check, X, FileQuestion, CheckCircle, Inbox, Filter, Undo, ChevronDown } from 'lucide-vue-next';

const props = defineProps({
    initialApplications: Array
});

const applicationsData = ref(props.initialApplications);

// --- Dropdown State ---
const isSubjectDropdownOpen = ref(false);
const subjectDropdownRef = ref(null);

const selectedSubjectLabel = computed(() => {
    if (subjectFilter.value === 'all') return 'All Subjects';
    const subject = uniqueSubjects.value.find(s => s.code === subjectFilter.value);
    return subject ? `${subject.code} - ${subject.name}` : subjectFilter.value;
});

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

// Sync with props when they change (Inertia reloads)
watch(() => props.initialApplications, (newVal) => {
    applicationsData.value = newVal;
}, { deep: true });

const statusFilter = ref('pending'); // 'all', 'pending', 'approved', 'rejected'
const subjectFilter = ref('all');

// --- Computed Values ---
const stats = computed(() => {
    return {
        total: applicationsData.value.length,
        pending: applicationsData.value.filter(app => app.status === 'pending').length,
        approved: applicationsData.value.filter(app => app.status === 'approved').length,
        rejected: applicationsData.value.filter(app => app.status === 'rejected').length,
    };
});

const uniqueSubjects = computed(() => {
    const codes = [...new Set(applicationsData.value.map(app => app.courseCode))];
    return codes.map(code => {
        return {
            code,
            name: applicationsData.value.find(app => app.courseCode === code).courseName
        };
    });
});

const filteredApplications = computed(() => {
    return applicationsData.value.filter(app => {
        const matchStatus = statusFilter.value === 'all' || app.status === statusFilter.value;
        const matchSubject = subjectFilter.value === 'all' || app.courseCode === subjectFilter.value;
        return matchStatus && matchSubject;
    });
});

// --- Methods ---
const updateStatus = (id, newStatus) => {
    router.post(route('lecturer.leave.status', id), {
        status: newStatus
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // The local state will be updated via the watcher on props.initialApplications
        }
    });
};

const getLeaveTypeStyle = (type) => {
    switch(type) {
        case 'Medical': return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'Emergency': return 'bg-rose-50 text-rose-700 border-rose-200';
        default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};

</script>

<template>
    <Head>
        <title>Leave Application</title>
    </Head>
    <AppLayout>
        <div class="min-h-screen bg-white text-slate-900 font-sans p-0">
            <div>

                <!-- Header Area -->
                <div class="mb-8">
                    <h1 class="text-2xl font-semibold mb-2 text-gray-900">Leave Applications</h1>
                    <p class="text-slate-500 text-sm font-medium mt-1">Review and manage student absence requests.</p>
                </div>

                <!-- Stats Section -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white border border-slate-200 rounded-xl p-4">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total</p>
                        <p class="text-2xl font-bold text-slate-900">{{ stats.total }}</p>
                    </div>
                    <div class="bg-white border border-orange-200 rounded-xl p-4 flex flex-col justify-between relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-16 h-16 bg-orange-50 rounded-bl-full -z-10"></div>
                        <p class="text-xs font-semibold text-orange-600 uppercase tracking-wider mb-1">Pending</p>
                        <p class="text-2xl font-bold text-orange-700">{{ stats.pending }}</p>
                    </div>
                    <div class="bg-white border border-emerald-200 rounded-xl p-4 flex flex-col justify-between relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-16 h-16 bg-emerald-50 rounded-bl-full -z-10"></div>
                        <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider mb-1">Approved</p>
                        <p class="text-2xl font-bold text-emerald-700">{{ stats.approved }}</p>
                    </div>
                    <div class="bg-white border border-rose-300 rounded-xl p-4 flex flex-col justify-between relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-16 h-16 bg-rose-50 rounded-bl-full -z-10"></div>
                        <p class="text-xs font-semibold text-rose-600 uppercase tracking-wider mb-1">Rejected</p>
                        <p class="text-2xl font-bold text-rose-700">{{ stats.rejected }}</p>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 bg-white p-2 border border-slate-200 rounded-xl">

                    <!-- Status Tabs -->
                    <div class="flex gap-1 overflow-x-auto w-full sm:w-auto">
                        <button
                            v-for="statusOption in ['pending', 'all', 'approved', 'rejected']"
                            :key="statusOption"
                            @click="statusFilter = statusOption"
                            :class="[
              'px-4 py-2 text-sm font-semibold rounded-lg capitalize transition-colors whitespace-nowrap cursor-pointer',
              statusFilter === statusOption
                ? 'bg-orange-500 text-white'
                : 'text-slate-600 hover:bg-slate-100'
            ]"
                        >
                            {{ statusOption }}
                        </button>
                    </div>

                    <!-- Subject Dropdown -->
                    <div class="relative w-full sm:w-auto" ref="subjectDropdownRef">
                        <button
                            @click="isSubjectDropdownOpen = !isSubjectDropdownOpen"
                            class="w-full sm:w-auto inline-flex items-center justify-between text-white bg-orange-500 hover:bg-orange-600 focus:ring-4 focus:ring-orange-500/20 font-medium rounded-xl text-sm px-5 py-2 transition-all outline-none cursor-pointer"
                            type="button"
                        >
                            <div class="flex items-center gap-2">
                                <Filter class="w-4 h-4" />
                                <span class="truncate max-w-[150px]">{{ selectedSubjectLabel }}</span>
                            </div>
                            <ChevronDown class="w-4 h-4 ms-2 -me-1 transition-transform duration-200" :class="{'rotate-180': isSubjectDropdownOpen}" />
                        </button>

                        <!-- Dropdown menu -->
                        <div
                            v-if="isSubjectDropdownOpen"
                            class="absolute right-0 top-full mt-2 z-30 bg-white border border-slate-200 rounded-xl shadow-xl w-64 overflow-hidden animate-in fade-in zoom-in-95 duration-100"
                        >
                            <ul class="p-1.5 text-sm text-slate-700 font-medium max-h-60 overflow-y-auto space-y-1">
                                <li>
                                    <button
                                        @click="subjectFilter = 'all'; isSubjectDropdownOpen = false"
                                        class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                        :class="{'text-orange-400 bg-orange-50/50': subjectFilter === 'all'}"
                                    >
                                        All Subjects
                                    </button>
                                </li>
                                <li v-for="subject in uniqueSubjects" :key="subject.code">
                                    <button
                                        @click="subjectFilter = subject.code; isSubjectDropdownOpen = false"
                                        class="flex items-center w-full p-2.5 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors text-left cursor-pointer"
                                        :class="{'text-orange-400 bg-orange-50/50': subjectFilter === subject.code}"
                                    >
                                        {{ subject.code }} - {{ subject.name }}
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Content List -->
                <div class="space-y-4">

                    <!-- Empty State -->
                    <div v-if="filteredApplications.length === 0" class="text-center py-16 bg-white rounded-xl border border-slate-200 border-dashed">
                        <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <Inbox class="w-6 h-6 text-slate-400" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">No applications found</h3>
                        <p class="text-slate-500 font-medium mt-1">
                            Try adjusting your filters to see more results.
                        </p>
                    </div>

                    <!-- Application Cards -->
                    <div
                        v-for="app in filteredApplications"
                        :key="app.id"
                        :class="[
            'bg-white rounded-2xl border p-6 hover:shadow-md transition-shadow',
            app.status === 'pending' ? 'border-orange-200' : 'border-slate-200'
          ]"
                    >
                        <div class="flex flex-col md:flex-row gap-6">

                            <!-- Left Column: Student & Session Info -->
                            <div class="flex-1 space-y-4">
                                <!-- Header -->
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="flex items-center gap-3 mb-1">
                                            <h3 class="text-lg font-bold text-slate-900">{{ app.studentName }}</h3>
                                            <span :class="['px-2.5 py-0.5 text-xs font-bold rounded border', getLeaveTypeStyle(app.leaveType)]">
                      {{ app.leaveType }}
                    </span>
                                        </div>
                                        <p class="text-sm font-medium text-slate-500">{{ app.studentId }}</p>
                                    </div>

                                    <!-- Status Badge -->
                                    <div :class="[
                  'px-3 py-1 rounded-full text-xs font-bold tracking-wide border capitalize',
                  app.status === 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                  app.status === 'rejected' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                  'bg-orange-50 text-orange-700 border-orange-200'
                ]">
                                        {{ app.status }}
                                    </div>
                                </div>

                                <!-- Session Details -->
                                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Class Session</p>
                                        <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-white border border-slate-200 text-slate-700 text-xs font-bold rounded">
                      {{ app.courseCode }}
                    </span>
                                            <span class="text-sm font-semibold text-slate-800 truncate">{{ app.courseName }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Date & Time</p>
                                        <div class="flex items-center gap-1.5 text-sm font-semibold text-slate-800">
                                            <Calendar class="w-4 h-4 text-slate-400" />
                                            {{ app.sessionDate }} <span class="text-slate-400 font-normal mx-1">|</span> {{ app.sessionTime }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Reason -->
                                <div>
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Reason provided</p>
                                    <p class="text-sm text-slate-700 leading-relaxed bg-white border-l-2 border-slate-200 pl-3">
                                        {{ app.reason }}
                                    </p>
                                </div>

                                <!-- Timestamp -->
                                <div class="text-xs text-slate-400 font-medium flex items-center gap-1">
                                    <Clock class="w-3.5 h-3.5" />
                                    Submitted on {{ app.submittedAt }}
                                </div>
                            </div>

                            <!-- Right Column: Attachment & Actions -->
                            <div class="md:w-64 flex flex-col justify-between border-t md:border-t-0 md:border-l border-slate-100 pt-4 md:pt-0 md:pl-6">

                                <!-- Document Attachment -->
                                <div>
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Supporting Document</p>
                                    <a
                                        v-if="app.hasDocument"
                                        :href="app.documentUrl"
                                        target="_blank"
                                        class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-orange-300 hover:bg-orange-50 transition-colors group cursor-pointer"
                                    >
                                        <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center shrink-0 group-hover:bg-orange-500 group-hover:text-white transition-colors">
                                            <FileText class="w-4 h-4" />
                                        </div>
                                        <div class="overflow-hidden">
                                            <p class="text-sm font-semibold text-slate-800 truncate group-hover:text-orange-700">document.pdf</p>
                                            <p class="text-xs text-slate-500 mt-0.5">Click to view</p>
                                        </div>
                                    </a>
                                    <div v-else class="flex items-center gap-2 p-3 rounded-xl bg-slate-50 border border-slate-100 text-slate-400 text-sm font-medium">
                                        <FileQuestion class="w-4 h-4" />
                                        No document provided
                                    </div>
                                </div>

                                <!-- Action Buttons (Only for Pending) -->
                                <div v-if="app.status === 'pending'" class="grid grid-cols-2 gap-2 mt-6 md:mt-0">
                                    <button
                                        @click="updateStatus(app.id, 'rejected')"
                                        class="flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border-2 border-rose-100 bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white hover:border-rose-500 text-sm font-bold transition-all cursor-pointer"
                                    >
                                        <X class="w-4 h-4" />
                                        Reject
                                    </button>
                                    <button
                                        @click="updateStatus(app.id, 'approved')"
                                        class="flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border-2 border-emerald-500 bg-emerald-500 text-white hover:bg-emerald-600 hover:border-emerald-600 text-sm font-bold transition-all shadow-emerald-500/20 cursor-pointer"
                                    >
                                        <Check class="w-4 h-4" />
                                        Approve
                                    </button>
                                </div>

                                <!-- Review Info (For History) -->
                                <div v-else class="mt-4 md:mt-0 bg-slate-50 p-3 rounded-xl border border-slate-100 flex justify-between items-center">
                                    <div>
                                        <p class="text-xs text-slate-500 font-medium">Reviewed on</p>
                                        <p class="text-xs font-bold text-slate-700 mt-1">{{ app.reviewedAt }}</p>
                                    </div>
                                    <button
                                        @click="updateStatus(app.id, 'pending')"
                                        class="flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-orange-600 transition-colors bg-white px-2.5 py-1.5 rounded-md border border-slate-200 cursor-pointer"
                                    >
                                        <Undo class="w-3 h-3" />
                                        Undo
                                    </button>
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

</style>
