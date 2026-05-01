<template>
    <div class="min-h-screen bg-gray-100 p-8">
        <div class="max-w-7xl mx-auto space-y-6">
            <h1 class="text-2xl font-bold text-gray-800">Class Session Management</h1>

            <div v-if="$page.props.flash.success" class="p-4 bg-green-100 text-green-700 rounded shadow">
                {{ $page.props.flash.success }}
            </div>

            <!-- Create Session Form -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Schedule New Session</h2>
                <form @submit.prevent="submitSession" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Course</label>
                        <select v-model="form.course_id" class="w-full border-gray-300 rounded-md" required>
                            <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.code }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Lab (Optional)</label>
                        <select v-model="form.lab_id" class="w-full border-gray-300 rounded-md">
                            <option :value="null">General Lecture</option>
                            <option v-for="l in labs" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Lecturer</label>
                        <select v-model="form.lecturer_id" class="w-full border-gray-300 rounded-md" required>
                            <option v-for="lec in lecturers" :key="lec.id" :value="lec.id">{{ lec.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Room</label>
                        <select v-model="form.room_id" class="w-full border-gray-300 rounded-md" required>
                            <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Start Time</label>
                        <input v-model="form.start_time" type="datetime-local" class="w-full border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">End Time</label>
                        <input v-model="form.end_time" type="datetime-local" class="w-full border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Mode</label>
                        <select v-model="form.mode" class="w-full border-gray-300 rounded-md">
                            <option value="lecture">Lecture</option>
                            <option value="lab">Lab</option>
                            <option value="tutorial">Tutorial</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Check-in</label>
                        <select v-model="form.checkin_method" class="w-full border-gray-300 rounded-md">
                            <option value="qr">QR Code</option>
                            <option value="beacon">Beacon</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>
                    <div class="md:col-span-4 text-right">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">Create Session</button>
                    </div>
                </form>
            </div>

            <!-- Sessions Table -->
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-6 py-3">Course</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Lecturer</th>
                            <th class="px-6 py-3">Room</th>
                            <th class="px-6 py-3">Time</th>
                            <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="session in sessions" :key="session.id">
                            <td class="px-6 py-4">{{ session.course.code }} <span v-if="session.lab" class="text-xs text-blue-600">({{ session.lab.name }})</span></td>
                            <td class="px-6 py-4 capitalize">{{ session.mode }}</td>
                            <td class="px-6 py-4">{{ session.lecturer.name }}</td>
                            <td class="px-6 py-4">{{ session.room.name }}</td>
                            <td class="px-6 py-4 text-xs">
                                {{ new Date(session.start_time).toLocaleString() }} -<br>
                                {{ new Date(session.end_time).toLocaleTimeString() }}
                            </td>
                            <td class="px-6 py-4">
                                <button @click="deleteSession(session.id)" class="text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    sessions: Array,
    courses: Array,
    labs: Array,
    lecturers: Array,
    rooms: Array
});

const form = useForm({
    course_id: '',
    lab_id: null,
    lecturer_id: '',
    room_id: '',
    start_time: '',
    end_time: '',
    mode: 'lecture',
    checkin_method: 'qr'
});

const submitSession = () => {
    form.post('/admin/sessions', {
        onSuccess: () => form.reset()
    });
};

const deleteSession = (id) => {
    if(confirm('Delete session?')) router.delete(`/admin/sessions/${id}`);
};
</script>
