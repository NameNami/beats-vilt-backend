<template>
    <AdminLayout>
        <div class="max-w-4xl space-y-6">
            <div class="mb-8">
                <p class="text-xs font-bold tracking-widest text-amber-700 uppercase mb-2">Platform Configuration</p>
                <h2 class="text-4xl font-black text-slate-900 tracking-tight leading-tight">
                    Global System <span class="text-amber-600 italic">Settings</span>
                </h2>
                <p class="text-slate-600 mt-3">Configure attendance parameters, security refresh rates, and hardware integration thresholds.</p>
            </div>

            <div v-if="$page.props.flash?.success" class="p-4 bg-teal-50 text-teal-800 rounded-xl shadow-sm border border-teal-100 font-medium">
                {{ $page.props.flash.success }}
            </div>

            <form @submit.prevent="submitSettings" class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 space-y-8">

                <div>
                    <h2 class="text-lg font-bold text-slate-800 border-b border-gray-100 pb-3 mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Attendance Parameters
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700">Early Check-In Window (Mins)</label>
                            <p class="text-[11px] text-gray-500 mb-2 mt-1">Minutes before class a student can scan in.</p>
                            <input type="number" v-model="form.early_window_minutes" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-amber-600 focus:border-amber-600">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700">Late Cutoff (Mins)</label>
                            <p class="text-[11px] text-gray-500 mb-2 mt-1">Minutes after start time to be marked Late.</p>
                            <input type="number" v-model="form.late_cutoff_minutes" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-amber-600 focus:border-amber-600">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700">Min. Attendance Threshold (%)</label>
                            <p class="text-[11px] text-gray-500 mb-2 mt-1">Triggers At-Risk intervention warnings.</p>
                            <input type="number" v-model="form.min_attendance_threshold" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-amber-600 focus:border-amber-600">
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-lg font-bold text-slate-800 border-b border-gray-100 pb-3 mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        Hardware & Security Controls
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700">Dynamic QR Refresh Rate (Secs)</label>
                            <p class="text-[11px] text-gray-500 mb-2 mt-1">How often the lecturer's screen generates a new token.</p>
                            <input type="number" v-model="form.qr_refresh_seconds" class="w-full bg-[#f8fafc] border-gray-200 rounded-lg shadow-sm focus:ring-amber-600 focus:border-amber-600">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100">
                    <button type="submit" :disabled="form.processing" class="bg-amber-700 hover:bg-amber-800 text-white px-8 py-3 rounded-xl font-bold transition disabled:opacity-50 shadow-sm">
                        {{ form.processing ? 'Saving...' : 'Save Configuration' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object
});

const form = useForm({
    early_window_minutes: props.settings.early_window_minutes,
    late_cutoff_minutes: props.settings.late_cutoff_minutes,
    min_attendance_threshold: props.settings.min_attendance_threshold,
    qr_refresh_seconds: props.settings.qr_refresh_seconds,
});

const submitSettings = () => {
    form.post(route('admin.settings.update'), { preserveScroll: true });
};
</script>
