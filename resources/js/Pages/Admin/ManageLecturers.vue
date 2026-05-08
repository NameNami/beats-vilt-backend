<template>
    <div class="min-h-screen bg-gray-100 p-8">
        <div class="max-w-5xl mx-auto space-y-6">

            <h1 class="text-2xl font-bold text-gray-800">Manage Lecturer Assignments</h1>

            <div v-if="$page.props.flash.success" class="p-4 bg-green-100 text-green-700 rounded">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="errors.message" class="p-4 bg-red-100 text-red-700 rounded">
                {{ errors.message }}
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Assign Lecturer to Course</h2>

                <form @submit.prevent="submitAssignment" class="flex items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Lecturer</label>
                        <select v-model="form.user_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="" disabled>Choose a lecturer...</option>
                            <option v-for="lecturer in lecturers" :key="lecturer.id" :value="lecturer.id">
                                {{ lecturer.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Course</label>
                        <select v-model="form.course_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="" disabled>Choose a course...</option>
                            <option v-for="course in availableCourses" :key="course.id" :value="course.id">
                                {{ course.code }} - {{ course.name }}
                            </option>
                        </select>
                    </div>

                    <button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition disabled:opacity-50">
                        Assign
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="px-6 py-3">Lecturer Name</th>
                        <th class="px-6 py-3">Assigned Courses</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    <tr v-for="lecturer in lecturers" :key="lecturer.id">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ lecturer.name }}</td>
                        <td class="px-6 py-4 space-y-2">
                            <div v-for="enrollment in lecturer.course_enrollments" :key="enrollment.id" class="flex justify-between items-center bg-gray-50 p-2 rounded border">
                                <span>{{ enrollment.course.code }} - {{ enrollment.course.name }}</span>

                                <button @click="removeAssignment(enrollment.id)" class="text-red-500 hover:text-red-700 text-xs font-bold uppercase">
                                    Remove
                                </button>
                            </div>
                            <span v-if="lecturer.course_enrollments.length === 0" class="text-gray-400 italic">No courses assigned.</span>
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

// Props passed from AdminController
const props = defineProps({
    lecturers: Array,
    availableCourses: Array,
    errors: Object
});

// Inertia Form helper for POST request
const form = useForm({
    user_id: '',
    course_id: ''
});

// Submit function
const submitAssignment = () => {
    form.post('/admin/lecturers/assign', {
        preserveScroll: true,
        onSuccess: () => form.reset() // Clears the form if successful
    });
};

// Delete function using Inertia router
const removeAssignment = (enrollmentId) => {
    if (confirm('Are you sure you want to remove this lecturer from the course?')) {
        router.delete(`/admin/lecturers/assignment/${enrollmentId}`, {
            preserveScroll: true
        });
    }
};
</script>
