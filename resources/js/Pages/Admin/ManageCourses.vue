<template>
    <div class="min-h-screen bg-gray-100 p-8">
        <div class="max-w-6xl mx-auto space-y-6">
            <h1 class="text-2xl font-bold text-gray-800">Course & Lab Management</h1>

            <div v-if="$page.props.flash.success" class="p-4 bg-green-100 text-green-700 rounded shadow-sm">
                {{ $page.props.flash.success }}
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Create Course -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-lg font-semibold mb-4">Add New Course</h2>
                    <form @submit.prevent="submitCourse" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Course Code</label>
                            <input v-model="courseForm.code" type="text" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="e.g. SWE101" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Course Name</label>
                            <input v-model="courseForm.name" type="text" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Faculty</label>
                            <input v-model="courseForm.faculty" type="text" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition">Create Course</button>
                    </form>
                </div>

                <!-- Create Lab -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-lg font-semibold mb-4">Add New Lab Group</h2>
                    <form @submit.prevent="submitLab" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Select Course</label>
                            <select v-model="labForm.course_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                                <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} - {{ course.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Select Lab Lecturer</label>
                            <select v-model="labForm.lecturer_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                                <option v-for="lecturer in lecturers" :key="lecturer.id" :value="lecturer.id">{{ lecturer.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Lab Name / Group</label>
                            <input v-model="labForm.name" type="text" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="e.g. Lab Group A" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Capacity</label>
                            <input v-model="labForm.capacity" type="number" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 transition">Create Lab</button>
                    </form>
                </div>
            </div>

            <!-- List Courses -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-6 py-3">Code</th>
                            <th class="px-6 py-3">Course Name</th>
                            <th class="px-6 py-3">Labs</th>
                            <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="course in courses" :key="course.id">
                            <td class="px-6 py-4 font-bold">{{ course.code }}</td>
                            <td class="px-6 py-4">{{ course.name }} ({{ course.faculty }})</td>
                            <td class="px-6 py-4">
                                <div v-for="lab in course.labs" :key="lab.id" class="flex justify-between bg-gray-50 mb-1 p-1 rounded text-xs border">
                                    <span>{{ lab.name }}</span>
                                    <button @click="deleteLab(lab.id)" class="text-red-500 font-bold px-1">X</button>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <button @click="deleteCourse(course.id)" class="text-red-600 hover:underline">Delete</button>
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
    courses: Array,
    lecturers: Array
});

const courseForm = useForm({
    code: '',
    name: '',
    faculty: ''
});

const labForm = useForm({
    course_id: '',
    lecturer_id: '',
    name: '',
    capacity: 30
});

const submitCourse = () => {
    courseForm.post('/admin/courses', {
        onSuccess: () => courseForm.reset()
    });
};

const submitLab = () => {
    labForm.post('/admin/labs', {
        onSuccess: () => labForm.reset()
    });
};

const deleteCourse = (id) => {
    if(confirm('Delete course?')) router.delete(`/admin/courses/${id}`);
};

const deleteLab = (id) => {
    if(confirm('Delete lab?')) router.delete(`/admin/labs/${id}`);
};
</script>
