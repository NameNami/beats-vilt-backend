<template>
    <AdminLayout>

        <div class="flex justify-between items-end mb-8">
            <div>
                <p class="text-xs font-bold tracking-widest text-amber-700 uppercase mb-2">Curriculum Setup</p>
                <h2 class="text-4xl font-black text-slate-900 tracking-tight leading-tight">
                    Course <span class="text-amber-600 italic">Administration</span>
                </h2>
                <p class="text-slate-600 mt-3">Define academic subjects, manage faculties, and configure lab capacities.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 col-span-1 h-fit">
                <h2 class="text-lg font-black text-slate-800 mb-5 border-b border-gray-100 pb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    {{ isEditing ? 'Edit Course' : 'Create New Course' }}
                </h2>

                <form @submit.prevent="submitCourse" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Course Code</label>
                        <input type="text" v-model="form.code" placeholder="e.g. IPD39806" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5 uppercase" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Course Name</label>
                        <input type="text" v-model="form.name" placeholder="e.g. Final Year Project" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Department / Faculty</label>
                        <select v-model="form.faculty" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-amber-600 focus:border-amber-600 text-sm py-2.5 font-medium" required>
                            <option value="IT">Information Technology (MIIT)</option>
                            <option value="BUSINESS">Business & Management</option>
                            <option value="ENGINEERING">Engineering</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 mt-4">
                        <button v-if="isEditing" type="button" @click="cancelEdit" class="bg-gray-100 hover:bg-gray-200 text-slate-700 px-4 py-2 rounded-lg font-bold transition">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="bg-amber-700 hover:bg-amber-800 text-white px-6 py-2.5 rounded-lg font-bold transition disabled:opacity-50 w-full shadow-sm">
                            {{ isEditing ? 'Update Course' : 'Save Course' }}
                        </button>
                    </div>
                </form>

                <!-- Lab Management Section (Only visible when editing a course) -->
                <div v-if="isEditing" class="mt-8 pt-8 border-t border-gray-100">
                    <h2 class="text-lg font-black text-slate-800 mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Manage Labs
                    </h2>
                    
                    <form @submit.prevent="submitLab" class="space-y-4 mb-6 bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Lab Name</label>
                            <input type="text" v-model="labForm.name" placeholder="e.g. L01" class="w-full bg-white border-gray-200 rounded-lg text-sm py-2" required>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Capacity</label>
                                <input type="number" v-model="labForm.capacity" class="w-full bg-white border-gray-200 rounded-lg text-sm py-2" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Lecturer</label>
                                <select v-model="labForm.lecturer_id" class="w-full bg-white border-gray-200 rounded-lg text-sm py-2" required>
                                    <option value="">Select...</option>
                                    <option v-for="l in lecturers" :key="l.id" :value="l.id">{{ l.name }}</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" :disabled="labForm.processing" class="w-full bg-teal-700 hover:bg-teal-800 text-white py-2 rounded-lg font-bold text-xs transition">
                            + Add Lab Group
                        </button>
                    </form>

                    <div class="space-y-2">
                        <div v-for="lab in editingCourseLabs" :key="lab.id" class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg shadow-sm hover:border-teal-200 transition">
                            <div>
                                <p class="font-bold text-slate-800 text-sm">{{ lab.name }}</p>
                                <p class="text-[10px] text-slate-500">Cap: {{ lab.capacity }} | ID: {{ lab.id }}</p>
                            </div>
                            <button @click="deleteLab(lab.id)" class="text-slate-300 hover:text-rose-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden col-span-1 lg:col-span-2">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
                    <h2 class="text-lg font-black text-slate-800">Academic Catalog</h2>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100 text-[11px] uppercase tracking-widest text-gray-500 font-bold">
                        <th class="px-6 py-4">Course Details</th>
                        <th class="px-6 py-4">Department</th>
                        <th class="px-6 py-4">Labs</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                    <tr v-for="course in courses" :key="course.id" class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-[#fffbf5] text-amber-700 flex items-center justify-center font-black text-xs border border-amber-100">
                                    {{ course.code }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900">{{ course.name }}</p>
                                    <p class="text-[11px] text-gray-500 font-medium">Course ID: {{ course.id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 bg-sky-50 text-sky-700 border border-sky-100 rounded-full text-[10px] font-bold tracking-widest uppercase">{{ course.faculty || 'IT' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-[10px] font-black uppercase">{{ course.labs?.length || 0 }} Labs</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-3">
                            <button @click="editCourse(course)" class="text-slate-400 hover:text-amber-600 transition"><svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></button>
                            <button @click="deleteCourse(course.id)" class="text-slate-400 hover:text-rose-600 transition"><svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                        </td>
                    </tr>
                    <tr v-if="!courses || courses.length === 0">
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 font-medium">No courses found. Add your first course to get started.</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    courses: Array,
    lecturers: Array,
});

const isEditing = ref(false);

const form = useForm({
    id: null,
    code: '',
    name: '',
    faculty: 'IT',
});

const labForm = useForm({
    course_id: '',
    lecturer_id: '',
    name: '',
    capacity: 30
});

const editingCourseLabs = computed(() => {
    if (!form.id) return [];
    const course = props.courses.find(c => c.id === form.id);
    return course ? course.labs : [];
});

const submitCourse = () => {
    if (isEditing.value) {
        form.post(`/admin/courses/update/${form.id}`, {
            preserveScroll: true,
            onSuccess: () => resetForm()
        });
    } else {
        form.post('/admin/courses', {
            preserveScroll: true,
            onSuccess: () => resetForm()
        });
    }
};

const submitLab = () => {
    if (!form.id) {
        alert("Error: Please select a course first.");
        return;
    }
    labForm.course_id = form.id;
    labForm.post(route('admin.labs.store'), {
        preserveScroll: true,
        onSuccess: () => labForm.reset('name', 'capacity', 'lecturer_id')
    });
};

const deleteLab = (id) => {
    if (!id) {
        alert("Error: Lab ID is missing.");
        return;
    }
    if (confirm('Are you sure you want to remove this lab?')) {
        router.post(`/admin/labs/delete/${id}`, { preserveScroll: true });
    }
};

const editCourse = (course) => {
    isEditing.value = true;
    form.id = course.id;
    form.code = course.code;
    form.name = course.name;
    form.faculty = course.faculty || 'IT';
    form.clearErrors();
};

const cancelEdit = () => {
    resetForm();
};

const deleteCourse = (id) => {
    if (!id) {
        alert("Error: Course ID is missing.");
        return;
    }
    if(confirm('Are you sure you want to delete this course? All associated labs will also be removed.')) {
        router.post(`/admin/courses/delete/${id}`, { preserveScroll: true });
    }
};

const resetForm = () => {
    isEditing.value = false;
    form.id = null;
    form.reset();
    form.clearErrors();
};
</script>