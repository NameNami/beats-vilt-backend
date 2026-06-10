<template>
    <Head title="Dashboard"></Head>
    <AdminLayout>
        <div class="mb-6">
            <h2 class="text-2xl font-semibold mb-2 text-gray-900">System Overview</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col justify-between">
                    <div>
                        <h3 class="text-slate-600 font-medium text-sm leading-snug mb-2">Total<br>Students</h3>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-3xl leading-none font-bold text-slate-900">{{ stats.total_students }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col justify-between">
                    <div>
                        <h3 class="text-slate-600 font-medium text-sm leading-snug mb-2">Total<br>Lecturers</h3>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-3xl leading-none font-bold text-slate-900">{{ stats.total_lecturers }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col justify-between">
                    <div>
                        <h3 class="text-slate-600 font-medium text-sm leading-snug mb-2">Total<br>Courses</h3>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-3xl leading-none font-bold text-slate-900">{{ stats.total_courses }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-rose-200 p-4 flex flex-col justify-between">
                    <div>
                        <h3 class="text-rose-800 font-medium text-sm leading-snug mb-2">Active<br>Rooms</h3>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-3xl leading-none font-bold text-rose-800">{{ stats.total_active_rooms }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-8">
            <h2 class="text-xl font-bold text-slate-900 mb-4">Core Management Hub</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link href="/admin/users" class="bg-white rounded-xl p-5 border border-gray-200 hover:border-orange-500 hover: group transition-all duration-200 cursor-pointer">
                    <div class="w-10 h-10 rounded-lg bg-slate-50 text-slate-700 flex items-center justify-center mb-4 group-hover:bg-orange-50 group-hover:text-orange-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 group-hover:text-orange-600 transition">User Accounts</h3>
                    <p class="text-sm text-slate-500 mt-2 leading-relaxed">Manage system access, create administrator profiles, and handle password resets.</p>
                </Link>

                <Link href="/admin/students" class="bg-white rounded-xl p-5 border border-gray-200 hover:border-orange-500 hover: group transition-all duration-200 cursor-pointer">
                    <div class="w-10 h-10 rounded-lg bg-slate-50 text-slate-700 flex items-center justify-center mb-4 group-hover:bg-orange-50 group-hover:text-orange-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 group-hover:text-orange-600 transition">Student Database</h3>
                    <p class="text-sm text-slate-500 mt-2 leading-relaxed">View student profiles, track individual attendance, and manage enrollment records.</p>
                </Link>

                <Link href="/admin/lecturers" class="bg-white rounded-xl p-5 border border-gray-200 hover:border-orange-500 hover: group transition-all duration-200 cursor-pointer">
                    <div class="w-10 h-10 rounded-lg bg-slate-50 text-slate-700 flex items-center justify-center mb-4 group-hover:bg-orange-50 group-hover:text-orange-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 group-hover:text-orange-600 transition">Faculty Directory</h3>
                    <p class="text-sm text-slate-500 mt-2 leading-relaxed">Assign lecturers to courses, manage availability, and review teaching schedules.</p>
                </Link>

                <Link href="/admin/courses" class="bg-white rounded-xl p-5 border border-gray-200 hover:border-orange-500 hover: group transition-all duration-200 cursor-pointer">
                    <div class="w-10 h-10 rounded-lg bg-slate-50 text-slate-700 flex items-center justify-center mb-4 group-hover:bg-orange-50 group-hover:text-orange-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 group-hover:text-orange-600 transition">Courses & Labs</h3>
                    <p class="text-sm text-slate-500 mt-2 leading-relaxed">Define academic subjects, manage faculties, and configure specific lab capacities.</p>
                </Link>

                <Link href="/admin/sessions" class="bg-white rounded-xl p-5 border border-gray-200 hover:border-orange-500 hover: group transition-all duration-200 cursor-pointer">
                    <div class="w-10 h-10 rounded-lg bg-slate-50 text-slate-700 flex items-center justify-center mb-4 group-hover:bg-orange-50 group-hover:text-orange-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 group-hover:text-orange-600 transition">Class Sessions</h3>
                    <p class="text-sm text-slate-500 mt-2 leading-relaxed">Schedule lectures, assign physical rooms, and generate BLE/QR attendance links.</p>
                </Link>

                <Link href="/admin/analytics" class="bg-white rounded-xl p-5 border border-gray-200 hover:border-orange-500 hover: group transition-all duration-200 cursor-pointer">
                    <div class="w-10 h-10 rounded-lg bg-slate-50 text-slate-700 flex items-center justify-center mb-4 group-hover:bg-orange-50 group-hover:text-orange-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 group-hover:text-orange-600 transition">System Analytics</h3>
                    <p class="text-sm text-slate-500 mt-2 leading-relaxed">Review institution-wide attendance metrics and identify students requiring intervention.</p>
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import {Head, Link} from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    stats: Object
});
</script>
