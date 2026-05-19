<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Calendar,
    Scroll,
    UserCheck,
    BarChart3,
    Settings,
    LogOut,
    Bell,
    AlertTriangle,
    Clock,
    Info
} from 'lucide-vue-next';

// Grab the user globally from Inertia (explained in Step 3)
const page = usePage();
const user = computed(() => page.props.auth.user);

// Get globally shared notification data
const notifications = computed(() => page.props.notifications);
const unreadCount = computed(() => page.props.unread_count);

const profilePhoto = computed(() => {
    return user.value?.profile_photo_path
        ? `/images/${user.value.profile_photo_path}`
        : '/images/default-avatar.png';
});

defineProps({
    noPadding: {
        type: Boolean,
        default: false
    },
    noBackground: {
        type: Boolean,
        default: false
    }
});

// Dropdown state
const showNotifications = ref(false);

const markAsRead = (id, isRead) => {
    if (isRead) return; // Don't ping the server if already read

    // Inertia request that updates data without scrolling or reloading
    router.post(`/notifications/${id}/read`, {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const markAllAsRead = () => {
    router.post('/notifications/mark-all-read', {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

let polling = null;

onMounted(() => {
    // Check for new notifications every 30 seconds (30000 milliseconds)
    polling = setInterval(() => {
        router.reload({
            // ONLY fetch the notification data from the server, ignore everything else
            only: ['notifications', 'unread_count'],
            // Keep the user's current scroll position and typed data safe
            preserveState: true,
            preserveScroll: true,
        });
    }, 30000);
});

onUnmounted(() => {
    // Clean up the timer when the user leaves the application
    clearInterval(polling);
});
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
                <Link
                    href="/lecturer/dashboard"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors"
                    :class="[ $page.url.startsWith('/lecturer/dashboard') ? 'bg-gray-100 text-orange-400 font-medium border-r-3 border-orange-400' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <LayoutDashboard class="w-5 h-5" />
                    Dashboard
                </Link>

                <Link
                    href="/lecturer/timetable"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors"
                    :class="[ $page.url.startsWith('/lecturer/timetable') ? 'bg-gray-100 text-orange-400 font-medium border-r-3 border-orange-400' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <Calendar class="w-5 h-5" />
                    Timetable
                </Link>

                <Link
                    href="/lecturer/leave"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors"
                    :class="[ $page.url.startsWith('/lecturer/leave') ? 'bg-gray-100 text-orange-400 font-medium border-r-3 border-orange-400' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <Scroll class="w-5 h-5" />
                    Leave Applications
                </Link>

                <Link
                    href="/lecturer/attendance"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors"
                    :class="[ $page.url.startsWith('/lecturer/attendance') ? 'bg-gray-100 text-orange-400 font-medium border-r-3 border-orange-400' : 'text-gray-600 hover:bg-gray-100 border-transparent' ]"
                >
                    <UserCheck class="w-5 h-5" />
                    Attendance
                </Link>

                <Link
                    href="/lecturer/reports"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors"
                    :class="[ $page.url.startsWith('/lecturer/reports') ? 'bg-gray-100 text-orange-400 font-medium border-r-3 border-orange-400' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <BarChart3 class="w-5 h-5" />
                    Reports
                </Link>
            </nav>

            <div class="p-4 space-y-1 mb-4">
                <Link href="/lecturer/settings" class="flex items-center gap-3 px-3 py-2 text-gray-600 rounded-md hover:bg-gray-100">
                    <Settings class="w-5 h-5" />
                    Settings
                </Link>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="flex w-full items-center gap-3 px-3 py-2 text-gray-600 rounded-md transition-all cursor-pointer hover:bg-gray-100 hover:text-gray-900 active:bg-gray-200 active:scale-[0.98]"
                >
                    <LogOut class="w-5 h-5" />
                    Logout
                </Link>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">

            <header class="h-15 bg-gray-50 flex items-center justify-between px-8 border-b border-gray-200">

                <div class="w-96">
                    <div class="relative">

                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="relative flex items-center">
                        <button @click="showNotifications = !showNotifications" class="hover:text-gray-700 text-gray-500 relative focus:outline-none transition-colors p-2 rounded-full hover:bg-gray-200 cursor-pointer">
                            <Bell class="w-5 h-5" />
                            <span v-if="unreadCount > 0" class="absolute top-1 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>

                        <div v-if="showNotifications" @click="showNotifications = false" class="fixed inset-0 z-40"></div>

                        <div v-if="showNotifications" class="absolute top-full right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-100 z-50 overflow-hidden">
                            <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                                <h3 class="font-semibold text-gray-800 text-sm">Notifications</h3>
                                <button v-if="unreadCount > 0" @click="markAllAsRead" class="text-xs text-orange-500 hover:text-orange-600 font-medium cursor-pointer transition-colors">
                                    Mark all read
                                </button>
                            </div>

                            <div class="max-h-80 overflow-y-auto">
                                <div v-if="notifications.length === 0" class="p-6 text-center text-gray-500 text-sm">
                                    No new notifications.
                                </div>

                                <div
                                    v-for="notification in notifications"
                                    :key="notification.id"
                                    @click="markAsRead(notification.id, notification.is_read)"
                                    class="p-4 border-b border-gray-50 cursor-pointer transition-colors hover:bg-gray-50 flex gap-3"
                                    :class="{ 'bg-orange-50/30': !notification.is_read }"
                                >
                                    <div class="mt-1 flex-shrink-0">
                                        <AlertTriangle v-if="notification.type === 'risk'" class="w-5 h-5 text-red-500" />
                                        <Clock v-else-if="notification.type === 'reminder'" class="w-5 h-5 text-blue-500" />
                                        <Info v-else class="w-5 h-5 text-gray-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-800" :class="{ 'font-semibold': !notification.is_read }">{{ notification.title }}</p>
                                        <p class="text-xs text-gray-600 mt-0.5">{{ notification.body }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="h-8 w-px bg-gray-300"></div>

                    <div class="flex items-center gap-3 cursor-pointer">
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-800">{{ user.name }}</p>
                            <p class="text-xs text-gray-500">{{ user.email }}</p>
                        </div>
                        <img
                            :src="profilePhoto"
                            alt="Profile"
                            class="w-10 h-10 rounded-lg object-cover"
                        >
                    </div>
                </div>
            </header>

            <main :class="['flex-1 overflow-y-auto', noPadding ? '' : 'p-8', noBackground ? '' : 'bg-white']">
                <slot />
            </main>

        </div>
    </div>
</template>

<style scoped>

</style>
