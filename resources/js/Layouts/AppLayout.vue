<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Grab the user globally from Inertia (explained in Step 3)
const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div class="flex h-screen bg-gray-50 text-gray-800 font-sans">

        <aside class="w-64 bg-gray-50 border-r border-gray-200 flex flex-col">

            <div class="h-15 flex items-center px-6 mt-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded flex items-center justify-center overflow-hidden">
                        <img :src="'/images/Logo.png'" alt="BEATS Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h1 class="font-bold text-lg text-orange-500 leading-tight">BEATS</h1>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Attendance Portal</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-4 mt-8 space-y-1 overflow-y-auto">
                <a href="#" class="flex items-center gap-3 px-3 py-2 text-gray-600 rounded-md hover:bg-gray-100">
                    <font-awesome-icon icon="fa-solid fa-table-columns" class="text-lg" />
                    Dashboard
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2 text-gray-600 rounded-md hover:bg-gray-100">
                    <font-awesome-icon icon="fa-solid fa-calendar" class="text-lg" />
                    Timetable
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2 text-gray-600 rounded-md hover:bg-gray-100">
                    <font-awesome-icon icon="fa-solid fa-scroll" class="text-lg" />
                    Leave Applications
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-2 text-gray-600 rounded-md hover:bg-gray-100">
                    <font-awesome-icon icon="fa-solid fa-user-clock" class="text-lg" />
                    Attendance
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-2 text-gray-600 rounded-md hover:bg-gray-100">
                    <font-awesome-icon icon="fa-solid fa-chart-simple" class="text-lg" />
                    Reports
                </a>
            </nav>

            <div class="p-4 space-y-1 mb-4">
                <a href="#" class="flex items-center gap-3 px-3 py-2 text-gray-600 rounded-md hover:bg-gray-100">
                    <font-awesome-icon icon="fa-solid fa-gear" class="text-lg" />
                    Settings
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-2 text-gray-600 rounded-md hover:bg-gray-100">
                    <font-awesome-icon icon="fa-solid fa-right-from-bracket" class="text-lg" />
                    Logout
                </a>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">

            <header class="h-20 bg-gray-50 flex items-center justify-between px-8 border-b border-gray-200">

                <div class="w-96">
                    <div class="relative">

                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center text-gray-500">
                        <button class="hover:text-gray-700 relative">
                            <font-awesome-icon icon="fa-solid fa-bell" class="text-lg" />
                            <span class="absolute top-0 right-0 w-2 h-2 bg-blue-500 rounded-full"></span>
                        </button>
                        <button class="hover:text-gray-700">
                        </button>
                    </div>

                    <div class="h-8 w-px bg-gray-300"></div>

                    <div class="flex items-center gap-3 cursor-pointer">
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-800">{{ user.name }}</p>
                            <p class="text-xs text-gray-500">LECTURER</p>
                        </div>
                        <img
                            :src="'/images/'+user.profile_photo_path || '/images/default-avatar.png'"
                            alt="Profile"
                            class="w-10 h-10 rounded-lg object-cover"
                        >
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto bg-white p-8">
                <slot />
            </main>

        </div>
    </div>
</template>

<style scoped>

</style>
