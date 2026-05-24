<template>
    <AdminLayout>
        <Head title="Student Management" />

        <!-- Enrollment Modal -->
        <div v-if="showEnrollModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-xl font-black text-slate-900">Enroll Student</h3>
                    <button @click="showEnrollModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form @submit.prevent="submitEnrollment" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Select Student</label>
                        <select v-model="form.user_id" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-sm" required>
                            <option value="">Select a student...</option>
                            <option v-for="student in students" :key="student.id" :value="student.id">
                                {{ student.name }} ({{ student.student_id || 'N/A' }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Select Course</label>
                        <select v-model="form.course_id" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-sm" required>
                            <option value="">Select a course...</option>
                            <option v-for="course in availableCourses" :key="course.id" :value="course.id">
                                {{ course.code }} - {{ course.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Select Lab / Group (Optional)</label>
                        <select v-model="form.lab_id" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg text-sm">
                            <option value="">None / Open Class</option>
                            <option v-for="lab in filteredLabs" :key="lab.id" :value="lab.id">
                                {{ lab.name }} (Capacity: {{ lab.capacity }})
                            </option>
                        </select>
                    </div>
                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="showEnrollModal = false" class="px-6 py-2 text-slate-600 font-bold hover:text-slate-800 transition">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-amber-700 text-white rounded-xl font-bold shadow-sm hover:bg-amber-800 transition">Enroll Student</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="flex justify-between items-end mb-8">
            <div>
                <h2 class="text-4xl font-black text-slate-900 tracking-tight leading-tight">
                    Student Database
                </h2>
                <p class="text-slate-600 mt-2">Manage student accounts and course enrollments with precision.</p>
            </div>
            <div class="flex gap-3">
                <Link :href="route('admin.users.index')" class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-3 rounded-xl font-bold shadow-sm flex items-center gap-2 transition">
                    Manage Accounts
                </Link>
                <button @click="showEnrollModal = true" class="bg-amber-700 hover:bg-amber-800 text-white px-6 py-3 rounded-xl font-bold shadow-sm flex items-center gap-2 transition">
                    Enroll Student
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100 text-[11px] uppercase tracking-widest text-gray-400 font-bold">
                    <th class="px-8 py-4">Student</th>
                    <th class="px-8 py-4">ID</th>
                    <th class="px-8 py-4">Enrollments</th>
                    <th class="px-8 py-4 text-right">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                <tr v-for="student in students" :key="student.id" class="hover:bg-gray-50/50 transition">
                    <td class="px-8 py-4">
                        <div class="font-bold text-slate-900">{{ student.name }}</div>
                        <div class="text-xs text-gray-500">{{ student.email }}</div>
                    </td>
                    <td class="px-8 py-4 text-sm font-bold text-sky-600">{{ student.student_id || 'N/A' }}</td>
                    <td class="px-8 py-4">
                        <div class="flex flex-wrap gap-2">
                            <div v-for="enrollment in student.course_enrollments" :key="enrollment.id" 
                                class="flex items-center gap-2 bg-sky-50 text-sky-800 px-3 py-1 rounded-full text-[10px] font-bold border border-sky-100">
                                {{ enrollment.course?.code }} <span v-if="enrollment.lab" class="text-sky-400">({{ enrollment.lab.name }})</span>
                                <button @click="removeEnrollment(enrollment.id)" class="text-sky-300 hover:text-rose-600 transition">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                </button>
                            </div>
                            <span v-if="!student.course_enrollments?.length" class="text-gray-400 italic text-xs">No enrollments</span>
                        </div>
                    </td>
                    <td class="px-8 py-4 text-right">
                        <Link :href="route('admin.users.index')" class="text-slate-400 hover:text-amber-700 transition inline-block">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        </Link>
                    </td>
                </tr>
                <tr v-if="!students || students.length === 0">
                    <td colspan="4" class="px-8 py-10 text-center text-gray-500 font-medium">No students found in the system.</td>
                </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    students: Array,
    availableCourses: Array,
    availableLabs: Array
});

const showEnrollModal = ref(false);

const form = useForm({
    user_id: '',
    course_id: '',
    lab_id: '',
});

const filteredLabs = computed(() => {
    if (!form.course_id) return [];
    return props.availableLabs.filter(lab => lab.course_id === form.course_id);
});

const submitEnrollment = () => {
    form.post(route('admin.students.assign'), {
        preserveScroll: true,
        onSuccess: () => {
            showEnrollModal.value = false;
            form.reset();
        }
    });
};

const removeEnrollment = (id) => {
    if (!id) {
        alert("Error: Enrollment ID is missing.");
        return;
    }
    if (confirm('Are you sure you want to remove this student from the course?')) {
        // We reuse the remove assignment endpoint since it targets CourseEnrollment
        router.post(`/admin/lecturers/assignment/${id}`, { preserveScroll: true });
    }
};
</script>