<template>
    <div class="min-h-screen bg-gray-50 p-8">
        <div class="max-w-7xl mx-auto space-y-8">

            <header class="bg-white rounded-lg shadow p-6 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">BEATS Lecturer Portal</h1>
                    <p class="text-gray-500">Welcome back, {{ lecturer.name }}</p>
                </div>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="bg-white rounded-lg shadow p-6 col-span-1 border-t-4 border-indigo-600">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">My Courses</h2>
                    <ul class="space-y-3">
                        <li v-for="course in courses" :key="course.id" class="p-3 bg-gray-50 rounded border">
                            <p class="font-bold text-indigo-700">{{ course.code }}</p>
                            <p class="text-sm text-gray-600">{{ course.name }}</p>
                        </li>
                        <li v-if="courses.length === 0" class="text-sm text-gray-500">
                            You are not assigned to any courses yet.
                        </li>
                    </ul>
                </div>

                <div class="bg-white rounded-lg shadow p-6 col-span-1 lg:col-span-2 border-t-4 border-emerald-500">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Upcoming Classes</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-100 text-gray-700 uppercase">
                            <tr>
                                <th class="px-4 py-3">Time</th>
                                <th class="px-4 py-3">Course</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Location</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="session in schedule" :key="session.id" class="border-b">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ new Date(session.start_time).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3">{{ session.course.code }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="session.lab_id" class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">Lab</span>
                                    <span v-else class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs">Lecture</span>
                                </td>
                                <td class="px-4 py-3">{{ session.room ? session.room.name : 'Online' }}</td>
                            </tr>
                            <tr v-if="schedule.length === 0">
                                <td colspan="4" class="px-4 py-4 text-center text-gray-500">No upcoming classes scheduled.</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup>
// Define the props coming from the Laravel Controller
defineProps({
    lecturer: Object,
    courses: Array,
    schedule: Array
});
</script>
