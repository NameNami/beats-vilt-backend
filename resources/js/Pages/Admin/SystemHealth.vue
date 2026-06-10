<template>
    <Head title="System Health & Backups" />
    <AdminLayout>
        <div class="max-w-6xl mx-auto space-y-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-semibold mb-2 text-gray-900">System Health & Backups</h1>
                    <p class="text-slate-600 text-sm font-medium">Monitor server environment and generate database backups.</p>
                </div>
            </div>

            <div v-if="$page.props.errors?.message" class="p-4 bg-red-50 text-red-800 rounded-xl border border-red-100 font-bold flex items-center gap-3">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                {{ $page.props.errors.message }}
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Status Card -->
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden ">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Environment Health
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Application State</p>
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full" :class="health.environment === 'production' ? 'bg-teal-500' : 'bg-amber-500'"></div>
                                    <p class="text-sm font-bold text-slate-800 capitalize">{{ health.environment }}</p>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Debug Mode</p>
                                <p class="text-sm font-bold" :class="health.debug_mode ? 'text-rose-600' : 'text-teal-600'">
                                    {{ health.debug_mode ? 'ENABLED (Warning)' : 'DISABLED (Secure)' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">PHP Version</p>
                                <p class="text-sm font-bold text-slate-800">{{ health.php_version }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Laravel Framework</p>
                                <p class="text-sm font-bold text-slate-800">v{{ health.laravel_version }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Database Driver</p>
                                <p class="text-sm font-bold text-slate-800 uppercase">{{ health.db_driver }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Database Size</p>
                                <p class="text-sm font-bold text-slate-800">{{ health.db_size_mb }} MB</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Backup Card -->
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden ">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            Database Backup
                        </h2>
                    </div>
                    <div class="p-6 flex flex-col justify-between h-[calc(100%-60px)]">
                        <div>
                            <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                                Generate and download a raw SQL dump (or SQLite copy) of the current database. This is a manual backup containing all user data, attendance records, and system settings.
                            </p>
                            <p class="text-xs font-medium text-slate-500 bg-slate-50 p-3 rounded-lg border border-slate-100">
                                <strong>Note:</strong> Generating backups can take several seconds depending on database size. Do not navigate away while downloading.
                            </p>
                        </div>
                        <div class="mt-6">
                            <button @click="downloadBackup" :disabled="isDownloading" class="w-full bg-slate-900 hover:bg-slate-800 text-white px-6 py-3 rounded-xl font-bold transition disabled:opacity-50 flex items-center justify-center gap-2">
                                <svg v-if="isDownloading" class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                {{ isDownloading ? 'Generating Backup...' : 'Generate & Download Backup' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Semester Rollover Card -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-rose-200 overflow-hidden ">
                    <div class="px-6 py-4 border-b border-rose-100 bg-rose-50/50">
                        <h2 class="text-lg font-bold text-rose-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Semester Rollover (DANGER ZONE)
                        </h2>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-slate-600 mb-6 leading-relaxed">
                            This action will move all current Class Sessions, Student Enrollments, Leave Applications, and Attendance Records into dedicated Archive tables. It will then wipe the active tables and reset all student gamification streaks to prepare the system for a fresh semester.
                        </p>

                        <form @submit.prevent="submitRollover" class="bg-rose-50 p-6 rounded-xl border border-rose-100 space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">New Semester Name</label>
                                    <input v-model="form.new_semester" type="text" placeholder="e.g., 2026/2027-1" class="w-full bg-white border-rose-200 rounded-xl focus:ring-rose-500 focus:border-rose-500 text-sm py-2.5">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">New Start Date</label>
                                    <input v-model="form.new_start_date" type="date" class="w-full bg-white border-rose-200 rounded-xl focus:ring-rose-500 focus:border-rose-500 text-sm py-2.5">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Confirmation</label>
                                <p class="text-xs text-rose-600 mb-2 font-medium">Type <strong>CONFIRM</strong> to verify this destructive action.</p>
                                <input v-model="form.confirmation" type="text" class="w-full bg-white border-rose-200 rounded-xl focus:ring-rose-500 focus:border-rose-500 text-sm py-2.5 uppercase text-rose-600 font-bold tracking-widest text-center" placeholder="CONFIRM">
                            </div>

                            <button type="submit" :disabled="form.processing || form.confirmation !== 'CONFIRM'" class="w-full bg-rose-600 hover:bg-rose-700 text-white px-6 py-3 rounded-xl font-bold transition disabled:opacity-50 flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                {{ form.processing ? 'Archiving Data...' : 'Execute Semester Rollover' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    health: Object
});

const isDownloading = ref(false);

const downloadBackup = () => {
    isDownloading.value = true;
    window.location.href = route('admin.system.backup');

    // Re-enable button after a generous timeout since download doesn't trigger a JS callback
    setTimeout(() => {
        isDownloading.value = false;
    }, 5000);
};

const form = useForm({
    confirmation: '',
    new_semester: '',
    new_start_date: ''
});

const submitRollover = () => {
    if (form.confirmation !== 'CONFIRM') return;

    if (confirm('FINAL WARNING: This will permanently archive current data and clear active tables. This cannot be undone. Are you sure?')) {
        form.post(route('admin.system.rollover'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
            }
        });
    }
};
</script>
