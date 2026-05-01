<template>
    <div class="min-h-screen bg-gray-100 p-8">
        <div class="max-w-6xl mx-auto space-y-6">

            <h1 class="text-2xl font-bold text-gray-800">Manage Student Enrollments</h1>

            <div v-if="$page.props.flash.success" class="p-4 bg-green-100 text-green-700 rounded">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="errors.message" class="p-4 bg-red-100 text-red-700 rounded">
                {{ errors.message }}
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Enroll Student in Course</h2>

                <form @submit.prevent="submitEnrollment" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                        <select v-model="form.user_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="" disabled>Select student...</option>
                            <option v-for="student in students" :key="student.id" :value="student.id">
                                {{ student.name }} ({{ student.student_id }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                        <select v-model="form.course_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="" disabled>Select course...</option>
                            <option v-for="course in availableCourses" :key="course.id" :value="course.id">
                                {{ course.code }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lab (Optional)</label>
                        <select v-model="form.lab_id" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option :value="null">No specific lab</option>
                            <option v-for="lab in availableLabs" :key="lab.id" :value="lab.id">
                                {{ lab.name }}
                            </option>
                        </select>
                    </div>

                    <button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition disabled:opacity-50">
                        Enroll Student
                    </button>
                </form>
            </div>

        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    students: Array,
    availableCourses: Array,
    availableLabs: Array,
    errors: Object
});

const form = useForm({
    user_id: '',
    course_id: '',
    lab_id: null // Null by default since labs are optional
});

const submitEnrollment = () => {
    form.post('/admin/students/assign', {
        preserveScroll: true,
        onSuccess: () => form.reset()
    });
};
</script>
