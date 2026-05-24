<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import dayjs from 'dayjs';

const props = defineProps({
    sessions: { type: Array, default: () => [] },
    courses: { type: Array, default: () => [] },
    labs: { type: Array, default: () => [] },
    lecturers: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] }
});

const showForm = ref(false);
const isEditing = ref(false);

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

const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
const hours = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

const filteredLabs = computed(() => {
    if (!form.course_id) return [];
    return props.labs.filter(lab => lab.course_id === form.course_id);
});

const organizedSessions = computed(() => {
    const grid = {};
    (props.sessions || []).forEach(session => {
        if (!session.start_time) return;
        const date = dayjs(session.start_time);
        const day = date.format('ddd');
        const hour = date.format('HH:00');
        if (!grid[day]) grid[day] = {};
        if (!grid[day][hour]) grid[day][hour] = [];
        grid[day][hour].push(session);
    });
    return grid;
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
    if (!id) {
        alert("Error: Session ID is missing.");
        return;
    }
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
};

const getSessionColor = (mode) => {
    switch (mode?.toLowerCase()) {
        case 'online': return 'bg-[#e0f2fe] border-sky-600 text-sky-700';
        case 'physical': return 'bg-[#faebd7] border-amber-700 text-amber-800';
        default: return 'bg-[#f0fdf4] border-teal-600 text-teal-700';
    }
};

const formatTime = (time) => time ? dayjs(time).format('h:mm A') : 'N/A';
const formatDate = (time) => time ? dayjs(time).format('ddd, D MMM') : 'N/A';
</script>

<template>
    <AdminLayout>
        <Head title="Timetable Management" />
        
        <div class="flex justify-between items-end mb-8">
            <div>
                <p class="text-xs font-bold tracking-widest text-amber-700 uppercase mb-2">Scheduling Engine</p>
                <h2 class="text-4xl font-black text-slate-900 tracking-tight leading-tight">
                    Timetable <span class="text-amber-600 italic">Management</span>
                </h2>
                <p class="text-slate-600 mt-3">Orchestrate classroom schedules and lecturer availability with tactile precision.</p>
            </div>
            <button @click="isEditing = false; showForm = true" class="px-6 py-2.5 bg-amber-700 text-white font-bold rounded-lg shadow-sm hover:bg-amber-800 flex items-center gap-2 transition">
                + NEW SLOT
            </button>
        </div>

        <!-- Modal -->
        <div v-if="showForm" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-xl font-black text-slate-900">{{ isEditing ? 'Edit' : 'Add' }} Session</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form @submit.prevent="submitSession" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Course</label>
                            <select v-model="form.course_id" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-sm" required>
                                <option value="">Select Course</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} - {{ course.name }}</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Lab Group</label>
                            <select v-model="form.lab_id" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-sm" required>
                                <option value="">Select Lab</option>
                                <option v-for="lab in filteredLabs" :key="lab.id" :value="lab.id">{{ lab.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Lecturer</label>
                            <select v-model="form.lecturer_id" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-sm">
                                <option value="">Select...</option>
                                <option v-for="l in lecturers" :key="l.id" :value="l.id">{{ l.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Room</label>
                            <select v-model="form.room_id" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-sm">
                                <option value="">Select...</option>
                                <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Start</label>
                            <input type="datetime-local" v-model="form.start_time" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-sm" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">End</label>
                            <input type="datetime-local" v-model="form.end_time" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-sm" required>
                        </div>
                    </div>
                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="px-6 py-2 text-slate-600 font-bold hover:text-slate-800 transition">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-amber-700 text-white rounded-xl font-bold shadow-sm hover:bg-amber-800 transition disabled:opacity-50">
                            {{ isEditing ? 'Update' : 'Save' }} Session
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Timetable Grid -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-12">
            <div class="grid grid-cols-6 border-b border-gray-100 bg-white">
                <div class="p-4 border-r border-gray-50 bg-[#f8fafc]"></div>
                <div v-for="day in days" :key="day" class="p-4 text-center border-r border-gray-50">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">{{ day }}</p>
                </div>
            </div>

            <div class="overflow-y-auto max-h-[600px]">
                <div v-for="hour in hours" :key="hour" class="grid grid-cols-6 border-b border-gray-50 min-h-[120px] relative">
                    <div class="border-r border-gray-50 bg-[#f8fafc] flex items-start justify-end p-2">
                        <span class="text-[10px] font-bold text-gray-400">{{ hour }}</span>
                    </div>
                    <div v-for="day in days" :key="day" class="border-r border-gray-50 relative p-1 group hover:bg-gray-50/50 transition">
                        <div v-if="organizedSessions[day] && organizedSessions[day][hour]">
                            <div v-for="session in organizedSessions[day][hour]" :key="session.id"
                                 @click="editSession(session)"
                                 :class="[getSessionColor(session.mode), 'border-l-4 rounded-lg p-2 shadow-sm mb-1 hover:shadow-md transition cursor-pointer group/session']">
                                <div class="flex justify-between items-start">
                                    <span class="text-[9px] font-black uppercase">{{ session.room?.name || '??' }}</span>
                                    <button @click.stop="deleteSession(session.id)" class="opacity-0 group-hover/session:opacity-100 text-red-400 hover:text-red-600 transition">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                    </button>
                                </div>
                                <p class="text-[11px] font-black text-slate-900 mt-1 leading-tight line-clamp-1">{{ session.course?.code }}</p>
                                <p class="text-[9px] font-bold opacity-70 uppercase truncate">{{ session.lab?.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
                <h2 class="text-lg font-black text-slate-900">Session Directory</h2>
            </div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100 text-[11px] uppercase tracking-widest text-gray-400 font-bold">
                        <th class="px-6 py-4">Session Detail</th>
                        <th class="px-6 py-4">Lecturer / Room</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="session in sessions" :key="session.id" class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-900">{{ session.course?.code }} - {{ session.course?.name }}</p>
                            <p class="text-[11px] text-gray-500 font-medium">
                                {{ formatDate(session.start_time) }} | {{ formatTime(session.start_time) }}
                            </p>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <p class="font-medium text-slate-600">{{ session.lecturer?.name || 'N/A' }}</p>
                            <p class="text-xs text-amber-700 font-bold uppercase">{{ session.room?.name || 'No Room' }}</p>
                        </td>
                        <td class="px-6 py-4 text-right space-x-3">
                            <button @click="editSession(session)" class="text-slate-400 hover:text-amber-600 transition">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                            <button @click="deleteSession(session.id)" class="text-slate-400 hover:text-rose-600 transition">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>