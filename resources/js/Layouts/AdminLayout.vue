<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Calendar,
    Users,
    UserCircle,
    BookOpen,
    BarChart3,
    Settings,
    LogOut,
    Bell,
    AlertTriangle,
    Clock,
    Info,
    GraduationCap,
    Briefcase,
    Radio,
    Award,
    Gift,
    FileText
} from 'lucide-vue-next';

// Grab the user globally from Inertia
const page = usePage();
const user = computed(() => page.props.auth?.user || {});

// Get globally shared notification data
const notifications = computed(() => page.props.notifications || []);
const unreadCount = computed(() => page.props.unread_count || 0);

const profilePhoto = computed(() => {
    if (user.value?.profile_photo_path) {
        return user.value.profile_photo_path.startsWith('http')
            ? user.value.profile_photo_path
            : `/storage/${user.value.profile_photo_path}`;
    }
    return '/images/default-avatar.png';
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
    if (isRead) return; 

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
    polling = setInterval(() => {
        router.reload({
            only: ['notifications', 'unread_count'],
            preserveState: true,
            preserveScroll: true,
        });
    }, 30000);
});

onUnmounted(() => {
    clearInterval(polling);
});
</script>

<template>
    <div class="flex h-screen text-gray-800 font-sans">

        <aside class="w-64 border-r border-gray-200 flex flex-col">

            <div class="h-15 flex items-center px-6 mt-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded flex items-center justify-center overflow-hidden">
                        <img :src="'/images/Logo.png'" alt="BEATS Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h1 class="font-bold text-lg text-orange-500 leading-tight">BEATS</h1>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Admin Portal</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-4 mt-8 space-y-1 overflow-y-auto">
                <Link
                    href="/admin/dashboard"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors cursor-pointer"
                    :class="[ $page.url === '/admin/dashboard' ? ' text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <LayoutDashboard class="w-5 h-5" />
                    Dashboard
                </Link>

                <div class="px-3 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-4">User Management</div>

                <Link
                    href="/admin/users"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors cursor-pointer"
                    :class="[ $page.url.startsWith('/admin/users') ? ' text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <UserCircle class="w-5 h-5" />
                    User Accounts
                </Link>

                <Link
                    href="/admin/students"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors cursor-pointer"
                    :class="[ $page.url.startsWith('/admin/students') ? ' text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <GraduationCap class="w-5 h-5" />
                    Students
                </Link>

                <Link
                    href="/admin/lecturers"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors cursor-pointer"
                    :class="[ $page.url.startsWith('/admin/lecturers') ? ' text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <Briefcase class="w-5 h-5" />
                    Lecturers
                </Link>

                <div class="px-3 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-4">Academic Ops</div>

                <Link
                    href="/admin/courses"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors cursor-pointer"
                    :class="[ $page.url.startsWith('/admin/courses') ? ' text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <BookOpen class="w-5 h-5" />
                    Courses & Labs
                </Link>

                <Link
                    href="/admin/sessions"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors cursor-pointer"
                    :class="[ $page.url.startsWith('/admin/sessions') ? ' text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <Calendar class="w-5 h-5" />
                    Class Sessions
                </Link>

                <Link
                    href="/admin/leave-management"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors cursor-pointer"
                    :class="[ $page.url.startsWith('/admin/leave-management') ? ' text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Leave Management
                </Link>

                <Link
                    href="/admin/ble-devices"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors cursor-pointer"
                    :class="[ $page.url.startsWith('/admin/ble-devices') ? ' text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <Radio class="w-5 h-5" />
                    BLE Ecosystem
                </Link>

                <Link
                    href="/admin/analytics"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors cursor-pointer"
                    :class="[ $page.url.startsWith('/admin/analytics') ? ' text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <BarChart3 class="w-5 h-5" />
                    Global Analytics
                </Link>

                <Link
                    href="/admin/audit-logs"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors cursor-pointer"
                    :class="[ $page.url.startsWith('/admin/audit-logs') ? ' text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <FileText class="w-5 h-5" />
                    Audit Logs
                </Link>

                <div class="px-3 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-4">Communication</div>

                <Link
                    href="/admin/broadcasts"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors cursor-pointer"
                    :class="[ $page.url.startsWith('/admin/broadcasts') ? ' text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                    Broadcasts
                </Link>

                <div class="px-3 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-4">Gamification</div>

                <Link
                    href="/admin/badges"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors cursor-pointer"
                    :class="[ $page.url.startsWith('/admin/badges') ? ' text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <Award class="w-5 h-5" />
                    Manage Badges
                </Link>

                <Link
                    href="/admin/redemptions"
                    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors cursor-pointer"
                    :class="[ $page.url.startsWith('/admin/redemptions') ? ' text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100' ]"
                >
                    <Gift class="w-5 h-5" />
                    Redemptions
                </Link>
            </nav>

            <div class="p-4 space-y-1 mb-4 border-t border-gray-100">
                <div class="px-3 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">System</div>
                <Link
                    href="/admin/settings"
                    class="flex items-center gap-3 px-3 py-2 text-gray-600 rounded-md hover:bg-gray-100 cursor-pointer"
                    :class="[ $page.url.startsWith('/admin/settings') ? 'text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100 border-transparent' ]"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Settings
                </Link>

                <Link
                    href="/admin/system-health"
                    class="flex items-center gap-3 px-3 py-2 text-gray-600 rounded-md hover:bg-gray-100 cursor-pointer"
                    :class="[ $page.url.startsWith('/admin/system-health') ? 'text-orange-400 font-medium ' : 'text-gray-600 hover:bg-gray-100 border-transparent' ]"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    System Health & Backup
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

            <header class="h-15 flex items-center justify-between px-8 border-b border-gray-200 bg-white">

                <div class="w-96">
                </div>

                <div class="flex items-center gap-6">
                    <div class="relative flex items-center">
                        <button @click="showNotifications = !showNotifications" class="hover:text-gray-700 text-gray-500 relative focus:outline-none transition-colors p-2 rounded-full hover:bg-gray-200 cursor-pointer">
                            <Bell class="w-5 h-5" />
                            <span v-if="unreadCount > 0" class="absolute top-1 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>

                        <div v-if="showNotifications" @click="showNotifications = false" class="fixed inset-0 z-40 cursor-pointer"></div>

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
