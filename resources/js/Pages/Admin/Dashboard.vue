<template>
    <AdminLayout>
        <div class="space-y-8">

            <div class="flex justify-between items-end">
                <div>
                    <p class="text-xs font-bold tracking-widest text-amber-700 uppercase mb-2">Command Center</p>
                    <h2 class="text-4xl font-black text-slate-900 tracking-tight leading-tight">
                        System <span class="text-amber-600 italic">Overview</span>
                    </h2>
                    <p class="text-slate-600 mt-3">Monitor vital statistics and access core management modules.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between group hover:shadow-md transition">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Students</p>
                        <p class="text-3xl font-black text-slate-900">{{ stats.total_students }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-sky-50 flex items-center justify-center text-sky-600 group-hover:bg-sky-100 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between group hover:shadow-md transition">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Lecturers</p>
                        <p class="text-3xl font-black text-slate-900">{{ stats.total_lecturers }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center text-amber-700 group-hover:bg-amber-100 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between group hover:shadow-md transition">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Courses</p>
                        <p class="text-3xl font-black text-slate-900">{{ stats.total_courses }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-teal-50 flex items-center justify-center text-teal-600 group-hover:bg-teal-100 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between group hover:shadow-md transition">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Active Rooms</p>
                        <p class="text-3xl font-black text-slate-900">{{ stats.total_active_rooms }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center text-rose-700 group-hover:bg-rose-100 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-2 h-6 bg-amber-600 rounded-full"></div>
                    <h2 class="text-xl font-bold text-slate-800">Core Management Hub</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link href="/admin/users" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-amber-200 group transition duration-200">
                        <div class="w-10 h-10 rounded-lg bg-slate-50 text-slate-700 flex items-center justify-center mb-4 group-hover:bg-amber-100 group-hover:text-amber-700 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-amber-700 transition">User Accounts</h3>
                        <p class="text-sm text-gray-500 mt-2 leading-relaxed">Manage system access, create administrator profiles, and handle password resets.</p>
                    </Link>

                    <Link href="/admin/students" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-amber-200 group transition duration-200">
                        <div class="w-10 h-10 rounded-lg bg-slate-50 text-slate-700 flex items-center justify-center mb-4 group-hover:bg-amber-100 group-hover:text-amber-700 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-amber-700 transition">Student Database</h3>
                        <p class="text-sm text-gray-500 mt-2 leading-relaxed">View student profiles, track individual attendance, and manage enrollment records.</p>
                    </Link>

                    <Link href="/admin/lecturers" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-amber-200 group transition duration-200">
                        <div class="w-10 h-10 rounded-lg bg-slate-50 text-slate-700 flex items-center justify-center mb-4 group-hover:bg-amber-100 group-hover:text-amber-700 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-amber-700 transition">Faculty Directory</h3>
                        <p class="text-sm text-gray-500 mt-2 leading-relaxed">Assign lecturers to courses, manage availability, and review teaching schedules.</p>
                    </Link>

                    <Link href="/admin/courses" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-amber-200 group transition duration-200">
                        <div class="w-10 h-10 rounded-lg bg-slate-50 text-slate-700 flex items-center justify-center mb-4 group-hover:bg-amber-100 group-hover:text-amber-700 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-amber-700 transition">Courses & Labs</h3>
                        <p class="text-sm text-gray-500 mt-2 leading-relaxed">Define academic subjects, manage faculties, and configure specific lab capacities.</p>
                    </Link>

                    <Link href="/admin/sessions" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-amber-200 group transition duration-200">
                        <div class="w-10 h-10 rounded-lg bg-slate-50 text-slate-700 flex items-center justify-center mb-4 group-hover:bg-amber-100 group-hover:text-amber-700 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-amber-700 transition">Class Sessions</h3>
                        <p class="text-sm text-gray-500 mt-2 leading-relaxed">Schedule lectures, assign physical rooms, and generate BLE/QR attendance links.</p>
                    </Link>

                    <Link href="/admin/analytics" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-amber-200 group transition duration-200">
                        <div class="w-10 h-10 rounded-lg bg-slate-50 text-slate-700 flex items-center justify-center mb-4 group-hover:bg-amber-100 group-hover:text-amber-700 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-amber-700 transition">System Analytics</h3>
                        <p class="text-sm text-gray-500 mt-2 leading-relaxed">Review institution-wide attendance metrics and identify students requiring intervention.</p>
                    </Link>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    stats: Object
});
</script>
